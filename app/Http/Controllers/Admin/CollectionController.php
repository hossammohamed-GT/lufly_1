<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\LocalizationService;
use App\Services\MediaService;
use App\Services\SlugService;
use Core\Database\DatabaseManager;
use Core\Exceptions\NotFoundException;
use Core\Http\RedirectResponse;
use Core\Http\Request;
use Core\Http\Response;
use Core\Localization\Translator;
use Throwable;

final class CollectionController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LocalizationService $localization,
        private readonly SlugService $slugs,
        private readonly MediaService $media,
        private readonly ActivityLogger $activity,
        private readonly Translator $translator,
    ) {
    }

    public function index(): Response
    {
        $connection = $this->db->connection();
        $fallback = (string) config('localization.fallback', 'en');
        $rows = $connection->select(
            "SELECT c.*, COALESCE(ct.name, c.slug) AS display_name,
                    (SELECT COUNT(*) FROM products p WHERE p.collection_id = c.id AND p.deleted_at IS NULL) AS products_total
             FROM collections c
             LEFT JOIN collection_translations ct ON ct.collection_id = c.id AND ct.locale = ?
             WHERE c.deleted_at IS NULL
             ORDER BY c.sort_order ASC, c.id ASC",
            [$fallback],
        );

        return $this->view('admin.collections.index', [
            'title' => trans('common.collections'),
            'collections' => $rows,
        ]);
    }

    public function create(): Response
    {
        return $this->view('admin.collections.form', [
            'title' => trans('categories.collection_create'),
            'collection' => null,
            'translations' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $translations = $this->translationsFrom($data);
        $fallback = (string) config('localization.fallback', 'en');
        $now = date('Y-m-d H:i:s');

        try {
            $slug = $this->slugs->generate(
                (string) (($data['slug'] ?? '') ?: ($translations[$fallback]['name'] ?? 'collection')),
                'collections',
            );
            $id = (int) $this->db->connection()->transaction(function () use ($data, $translations, $slug, $now): int {
                $id = (int) $this->db->connection()->table('collections')->insert([
                    'slug' => $slug,
                    'image' => $this->nullableString($data['image_path'] ?? null),
                    'status' => (string) $data['status'],
                    'sort_order' => (int) ($data['sort_order'] ?? 0),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->localization->syncTranslations('collection', $id, $translations);

                return $id;
            });
            $this->storeImage($request, $id);
        } catch (Throwable $e) {
            return $this->redirect(route('admin.collections.create'))
                ->with('_errors', ['collection' => [$e->getMessage()]])
                ->withInput($request->all());
        }

        $this->activity->created('collection', $id, ['slug' => $slug]);

        return $this->redirect(route('admin.collections.edit', ['id' => $id]))
            ->with('_success', trans('common.saved'));
    }

    public function edit(int $id): Response
    {
        return $this->view('admin.collections.form', [
            'title' => trans('categories.collection_edit'),
            'collection' => $this->find($id),
            'translations' => $this->translationsOf($id),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $current = $this->find($id);
        $data = $this->validated($request);
        $translations = $this->translationsFrom($data);
        $now = date('Y-m-d H:i:s');

        try {
            $slug = (string) $current['slug'];
            $requested = trim((string) ($data['slug'] ?? ''));
            if ($requested !== '' && $requested !== $slug) {
                $slug = $this->slugs->generate($requested, 'collections', 'slug', $id);
            }
            $this->db->connection()->transaction(function () use ($id, $data, $translations, $slug, $now): void {
                $this->db->connection()->table('collections')->where('id', $id)->update([
                    'slug' => $slug,
                    'image' => $this->nullableString($data['image_path'] ?? null),
                    'status' => (string) $data['status'],
                    'sort_order' => (int) ($data['sort_order'] ?? 0),
                    'updated_at' => $now,
                ]);
                $this->localization->syncTranslations('collection', $id, $translations);
            });
            $this->storeImage($request, $id);
        } catch (Throwable $e) {
            return $this->redirect(route('admin.collections.edit', ['id' => $id]))
                ->with('_errors', ['collection' => [$e->getMessage()]])
                ->withInput($request->all());
        }

        $this->activity->updated('collection', $id);

        return $this->redirect(route('admin.collections.edit', ['id' => $id]))
            ->with('_success', trans('common.saved'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->find($id);
        $inUse = $this->db->connection()->selectOne(
            'SELECT COUNT(*) AS total FROM products WHERE collection_id = ? AND deleted_at IS NULL',
            [$id],
        );
        if ((int) ($inUse['total'] ?? 0) > 0) {
            return $this->redirect(route('admin.collections.index'))
                ->with('_errors', ['collection' => [trans('categories.collection_in_use')]]);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->connection()->table('collections')->where('id', $id)->update([
            'deleted_at' => $now,
            'updated_at' => $now,
        ]);
        $this->activity->deleted('collection', $id);

        return $this->redirect(route('admin.collections.index'))
            ->with('_success', trans('common.deleted'));
    }

    /* --------------------------------------------------------------- helpers */

    /** @return array<string, mixed> */
    private function find(int $id): array
    {
        $row = $this->db->connection()->selectOne(
            'SELECT * FROM collections WHERE id = ? AND deleted_at IS NULL',
            [$id],
        );
        if ($row === null) {
            throw new NotFoundException('Collection not found.');
        }

        return $row;
    }

    /** @return array<string, array<string, mixed>> */
    private function translationsOf(int $id): array
    {
        $out = [];
        foreach ($this->db->connection()->select(
            'SELECT * FROM collection_translations WHERE collection_id = ?',
            [$id],
        ) as $row) {
            $out[(string) $row['locale']] = $row;
        }

        return $out;
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $rules = [
            'slug' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer',
            'image_path' => 'nullable|string|max:255',
            'image' => 'nullable|file',
        ];
        $fallback = (string) config('localization.fallback', 'en');
        foreach ($this->translator->locales() as $locale) {
            $rules['name_' . $locale] = ($locale === $fallback ? 'required|' : 'nullable|') . 'string|max:255';
            $rules['description_' . $locale] = 'nullable|string';
        }

        return $request->validate($rules);
    }

    /** @param array<string, mixed> $data @return array<string, array<string, mixed>> */
    private function translationsFrom(array $data): array
    {
        $translations = [];
        foreach ($this->translator->locales() as $locale) {
            $name = trim((string) ($data['name_' . $locale] ?? ''));
            if ($name === '') {
                continue;
            }
            $translations[$locale] = [
                'name' => $name,
                'description' => (string) ($data['description_' . $locale] ?? ''),
            ];
        }

        return $translations;
    }

    private function storeImage(Request $request, int $id): void
    {
        if (!$request->hasFile('image')) {
            return;
        }
        $file = $request->file('image');
        if ($file === null) {
            return;
        }
        $media = $this->media->storeFromUpload($file, 'collections', $id);
        $this->db->connection()->table('collections')->where('id', $id)->update([
            'image' => (string) $media->path,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
