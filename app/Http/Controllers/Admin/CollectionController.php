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
use Core\Http\JsonResponse;
use Core\Http\RedirectResponse;
use Core\Http\Request;
use Core\Http\Response;
use Core\Localization\Translator;
use Core\View\View;
use InvalidArgumentException;
use Throwable;

/**
 * Collections are managed in place: editable rows on the index page, an
 * expanded editor fragment for translations + slug, and a dedicated image
 * endpoint. No separate create/edit pages.
 */
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
        return $this->view('admin.collections.index', [
            'title' => trans('common.collections'),
            'collections' => $this->rows(),
            'locale' => $this->translator->getLocale(),
        ]);
    }

    /* ------------------------------------------------------------------ reads */

    /** @return array<int, array<string, mixed>> */
    private function rows(): array
    {
        $locale = $this->translator->getLocale();
        $fallback = (string) config('localization.fallback', 'en');

        return $this->db->connection()->select(
            "SELECT c.*, COALESCE(ct.name, ctf.name, c.slug) AS display_name,
                    (SELECT COUNT(*) FROM products p WHERE p.collection_id = c.id AND p.deleted_at IS NULL) AS products_total
             FROM collections c
             LEFT JOIN collection_translations ct ON ct.collection_id = c.id AND ct.locale = ?
             LEFT JOIN collection_translations ctf ON ctf.collection_id = c.id AND ctf.locale = ?
             WHERE c.deleted_at IS NULL
             ORDER BY c.sort_order ASC, c.id ASC",
            [$locale, $fallback],
        );
    }

    private function rowHtml(int $id): string
    {
        foreach ($this->rows() as $row) {
            if ((int) $row['id'] === $id) {
                return app(View::class)->render('admin.collections._row', [
                    'collection' => $row,
                    'locale' => $this->translator->getLocale(),
                ]);
            }
        }

        return '';
    }

    public function editor(int $id): Response
    {
        $html = app(View::class)->render('admin.collections._editor', [
            'collection' => $this->find($id),
            'translations' => $this->translationsOf($id),
        ]);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /* ----------------------------------------------------------------- writes */

    public function inline(Request $request, int $id): JsonResponse
    {
        $field = (string) $request->input('field', '');
        $value = $request->input('value');
        $locale = (string) ($request->input('locale') ?: $this->translator->getLocale());

        try {
            $this->find($id);
            $connection = $this->db->connection();
            $resultValue = null;

            switch ($field) {
                case 'name':
                    $name = trim((string) $value);
                    if ($name === '') {
                        throw new InvalidArgumentException(trans('categories.slug_required'));
                    }
                    $translations = $this->translationsOf($id);
                    $translations[$locale]['name'] = $name;
                    $this->localization->syncTranslations('collection', $id, $translations);
                    $resultValue = $name;
                    break;

                case 'status':
                    $status = trim((string) $value);
                    if (!in_array($status, ['active', 'inactive'], true)) {
                        throw new InvalidArgumentException(trans('categories.invalid_parent'));
                    }
                    $connection->table('collections')->where('id', $id)->update([
                        'status' => $status,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $resultValue = $status;
                    break;

                case 'sort_order':
                    $sort = max(0, (int) $value);
                    $connection->table('collections')->where('id', $id)->update([
                        'sort_order' => $sort,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $resultValue = $sort;
                    break;

                default:
                    throw new InvalidArgumentException(trans('admin.common.invalid_field'));
            }
        } catch (InvalidArgumentException | Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->activity->updated('collection', $id);

        return $this->json(['success' => true, 'data' => [
            'id' => $id,
            'field' => $field,
            'value' => $resultValue,
            'slug' => (string) $this->find($id)['slug'],
        ]]);
    }

    public function quickStore(Request $request): JsonResponse
    {
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            return $this->json(['success' => false, 'message' => trans('categories.slug_required')], 422);
        }

        $fallback = (string) config('localization.fallback', 'en');
        $locale = $this->translator->getLocale();
        $now = date('Y-m-d H:i:s');

        try {
            $max = $this->db->connection()->selectOne(
                'SELECT MAX(sort_order) AS max_sort FROM collections WHERE deleted_at IS NULL',
            );
            $slug = $this->slugs->generate($name, 'collections');
            $id = (int) $this->db->connection()->transaction(function () use ($slug, $max, $now, $name, $locale, $fallback): int {
                $id = (int) $this->db->connection()->table('collections')->insert([
                    'slug' => $slug,
                    'image' => null,
                    'status' => 'active',
                    'sort_order' => (int) ($max['max_sort'] ?? 0) + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->localization->syncTranslations('collection', $id, [
                    $locale => ['name' => $name],
                    $fallback => ['name' => $name],
                ]);

                return $id;
            });
        } catch (Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->activity->created('collection', $id, ['slug' => $slug]);

        return $this->json([
            'success' => true,
            'message' => trans('common.saved'),
            'id' => $id,
            'html' => $this->rowHtml($id),
        ]);
    }

    /** Translations + slug (+ status/sort) from the expanded editor. */
    public function section(Request $request, int $id): JsonResponse
    {
        $current = $this->find($id);
        $data = $this->validatedDetails($request);
        $translations = $this->translationsFrom($data);
        $now = date('Y-m-d H:i:s');

        try {
            $slug = (string) $current['slug'];
            $requested = trim((string) ($data['slug'] ?? ''));
            if ($requested !== '' && $requested !== $slug) {
                $slug = $this->slugs->generate($requested, 'collections', 'slug', $id);
            }
            /* The "image" column is intentionally absent: it is managed by
               the image endpoint and must survive a details save. */
            $this->db->connection()->transaction(function () use ($id, $data, $translations, $slug, $now): void {
                $this->db->connection()->table('collections')->where('id', $id)->update([
                    'slug' => $slug,
                    'status' => (string) $data['status'],
                    'sort_order' => (int) ($data['sort_order'] ?? 0),
                    'updated_at' => $now,
                ]);
                $this->localization->syncTranslations('collection', $id, $translations);
            });
        } catch (Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->activity->updated('collection', $id);

        return $this->json(['success' => true, 'message' => trans('common.saved')]);
    }

    /** Replace or clear the image; retired files go to the trash folder. */
    public function image(Request $request, int $id): JsonResponse
    {
        $current = $this->find($id);
        $previous = trim((string) ($current['image'] ?? ''));
        $label = (string) ($current['slug'] ?? 'collection');

        try {
            if ((string) $request->input('action', '') === 'delete') {
                $this->db->connection()->table('collections')->where('id', $id)->update([
                    'image' => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                if ($previous !== '') {
                    $this->media->trashByPath($previous, $label);
                }

                return $this->json(['success' => true, 'message' => trans('common.deleted'), 'path' => '']);
            }

            $file = $request->hasFile('image') ? $request->file('image') : null;
            if ($file === null) {
                return $this->json(['success' => false, 'message' => trans('products.media_file_required')], 422);
            }

            $media = $this->media->storeFromUpload($file, 'collections', $id);
            $this->db->connection()->table('collections')->where('id', $id)->update([
                'image' => (string) $media->path,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            if ($previous !== '' && $previous !== (string) $media->path) {
                $this->media->trashByPath($previous, $label);
            }
        } catch (Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->activity->updated('collection', $id);

        return $this->json([
            'success' => true,
            'message' => trans('common.saved'),
            'path' => (string) $media->path,
            'url' => asset((string) $media->path),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $this->find($id);
        $inUse = $this->db->connection()->selectOne(
            'SELECT COUNT(*) AS total FROM products WHERE collection_id = ? AND deleted_at IS NULL',
            [$id],
        );
        if ((int) ($inUse['total'] ?? 0) > 0) {
            if ($request->expectsJson()) {
                return $this->json(['success' => false, 'message' => trans('categories.collection_in_use')], 422);
            }

            return $this->redirect(route('admin.collections.index'))
                ->with('_errors', ['collection' => [trans('categories.collection_in_use')]]);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->connection()->table('collections')->where('id', $id)->update([
            'deleted_at' => $now,
            'updated_at' => $now,
        ]);
        $this->activity->deleted('collection', $id);

        if ($request->expectsJson()) {
            return $this->json(['success' => true, 'message' => trans('common.deleted')]);
        }

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
    private function validatedDetails(Request $request): array
    {
        $rules = [
            'slug' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer',
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
}
