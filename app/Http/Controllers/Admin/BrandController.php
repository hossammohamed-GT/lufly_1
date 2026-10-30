<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
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
 * Brands are managed in place: editable rows on the index page, an expanded
 * editor fragment for slug + details, and a dedicated logo endpoint. No
 * separate create/edit pages.
 */
final class BrandController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly SlugService $slugs,
        private readonly MediaService $media,
        private readonly ActivityLogger $activity,
        private readonly Translator $translator,
    ) {
    }

    public function index(): Response
    {
        return $this->view('admin.brands.index', [
            'title' => trans('common.brands'),
            'brands' => $this->rows(),
            'locale' => $this->translator->getLocale(),
        ]);
    }

    /* ------------------------------------------------------------------ reads */

    /** @return array<int, array<string, mixed>> */
    private function rows(): array
    {
        return $this->db->connection()->select(
            "SELECT b.*,
                    (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id AND p.deleted_at IS NULL) AS products_total
             FROM brands b
             WHERE b.deleted_at IS NULL
             ORDER BY b.id ASC",
        );
    }

    private function rowHtml(int $id): string
    {
        foreach ($this->rows() as $row) {
            if ((int) $row['id'] === $id) {
                return app(View::class)->render('admin.brands._row', [
                    'brand' => $row,
                    'locale' => $this->translator->getLocale(),
                ]);
            }
        }

        return '';
    }

    public function editor(int $id): Response
    {
        $html = app(View::class)->render('admin.brands._editor', [
            'brand' => $this->find($id),
        ]);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /* ----------------------------------------------------------------- writes */

    public function inline(Request $request, int $id): JsonResponse
    {
        $field = (string) $request->input('field', '');
        $value = $request->input('value');

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
                    $connection->table('brands')->where('id', $id)->update([
                        'name' => $name,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $resultValue = $name;
                    break;

                case 'status':
                    $status = trim((string) $value);
                    if (!in_array($status, ['active', 'inactive'], true)) {
                        throw new InvalidArgumentException(trans('categories.invalid_parent'));
                    }
                    $connection->table('brands')->where('id', $id)->update([
                        'status' => $status,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $resultValue = $status;
                    break;

                case 'country':
                case 'website':
                    $text = $this->nullableString($value);
                    $connection->table('brands')->where('id', $id)->update([
                        $field => $text,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $resultValue = (string) ($text ?? '');
                    break;

                default:
                    throw new InvalidArgumentException(trans('admin.common.invalid_field'));
            }
        } catch (InvalidArgumentException | Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->activity->updated('brand', $id);

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

        $now = date('Y-m-d H:i:s');

        try {
            $slug = $this->slugs->generate($name, 'brands');
            $id = (int) $this->db->connection()->table('brands')->insert([
                'name' => $name,
                'slug' => $slug,
                'logo' => null,
                'country' => null,
                'website' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->activity->created('brand', $id, ['slug' => $slug]);

        return $this->json([
            'success' => true,
            'message' => trans('common.saved'),
            'id' => $id,
            'html' => $this->rowHtml($id),
        ]);
    }

    /** Name + slug + country/website (+ status) from the expanded editor. */
    public function section(Request $request, int $id): JsonResponse
    {
        $current = $this->find($id);
        $data = $this->validatedDetails($request);
        $now = date('Y-m-d H:i:s');

        try {
            $slug = (string) $current['slug'];
            $requested = trim((string) ($data['slug'] ?? ''));
            if ($requested !== '' && $requested !== $slug) {
                $slug = $this->slugs->generate($requested, 'brands', 'slug', $id);
            }
            /* The "logo" column is intentionally absent: it is managed by
               the image endpoint and must survive a details save. */
            $this->db->connection()->table('brands')->where('id', $id)->update([
                'name' => (string) $data['name'],
                'slug' => $slug,
                'country' => $this->nullableString($data['country'] ?? null),
                'website' => $this->nullableString($data['website'] ?? null),
                'status' => (string) $data['status'],
                'updated_at' => $now,
            ]);
        } catch (Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->activity->updated('brand', $id);

        return $this->json(['success' => true, 'message' => trans('common.saved')]);
    }

    /** Replace or clear the logo; retired files go to the trash folder. */
    public function image(Request $request, int $id): JsonResponse
    {
        $current = $this->find($id);
        $previous = trim((string) ($current['logo'] ?? ''));
        $label = (string) ($current['slug'] ?? 'brand');

        try {
            if ((string) $request->input('action', '') === 'delete') {
                $this->db->connection()->table('brands')->where('id', $id)->update([
                    'logo' => null,
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

            $media = $this->media->storeFromUpload($file, 'brands', $id);
            $this->db->connection()->table('brands')->where('id', $id)->update([
                'logo' => (string) $media->path,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            if ($previous !== '' && $previous !== (string) $media->path) {
                $this->media->trashByPath($previous, $label);
            }
        } catch (Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->activity->updated('brand', $id);

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
            'SELECT COUNT(*) AS total FROM products WHERE brand_id = ? AND deleted_at IS NULL',
            [$id],
        );
        if ((int) ($inUse['total'] ?? 0) > 0) {
            if ($request->expectsJson()) {
                return $this->json(['success' => false, 'message' => trans('categories.brand_in_use')], 422);
            }

            return $this->redirect(route('admin.brands.index'))
                ->with('_errors', ['brand' => [trans('categories.brand_in_use')]]);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->connection()->table('brands')->where('id', $id)->update([
            'deleted_at' => $now,
            'updated_at' => $now,
        ]);
        $this->activity->deleted('brand', $id);

        if ($request->expectsJson()) {
            return $this->json(['success' => true, 'message' => trans('common.deleted')]);
        }

        return $this->redirect(route('admin.brands.index'))
            ->with('_success', trans('common.deleted'));
    }

    /* --------------------------------------------------------------- helpers */

    /** @return array<string, mixed> */
    private function find(int $id): array
    {
        $row = $this->db->connection()->selectOne(
            'SELECT * FROM brands WHERE id = ? AND deleted_at IS NULL',
            [$id],
        );
        if ($row === null) {
            throw new NotFoundException('Brand not found.');
        }

        return $row;
    }

    /** @return array<string, mixed> */
    private function validatedDetails(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'country' => 'nullable|string|max:100',
            'website' => 'nullable|string|max:255',
        ]);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
