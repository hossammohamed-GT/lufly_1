<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CategoryAdminService;
use App\Services\MediaService;
use Core\Http\JsonResponse;
use Core\Http\RedirectResponse;
use Core\Http\Request;
use Core\Http\Response;
use Core\Localization\Translator;
use Core\View\View;
use InvalidArgumentException;
use Throwable;

/**
 * Categories are managed entirely in place: the index page renders the tree
 * as editable rows and every mutation (inline fields, details section,
 * image, quick-create, reorder, delete) answers with JSON or an HTML
 * fragment so the page never reloads. There is no separate editor page.
 */
final class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryAdminService $categories,
        private readonly MediaService $media,
        private readonly Translator $translator,
    ) {
    }

    public function index(): Response
    {
        return $this->view('admin.categories.index', [
            'title' => trans('common.categories'),
            'tree' => $this->tree(),
            'locale' => $this->translator->getLocale(),
        ]);
    }

    /* ------------------------------------------------------------------ reads */

    /** @return array<int, array<string, mixed>> */
    private function tree(): array
    {
        $fallback = (string) config('localization.fallback', 'en');

        return $this->categories->flatTree($this->translator->getLocale(), $fallback);
    }

    private function rowHtml(int $id): string
    {
        foreach ($this->tree() as $node) {
            if ((int) $node['id'] === $id) {
                return app(View::class)->render('admin.categories._row', [
                    'node' => $node,
                    'locale' => $this->translator->getLocale(),
                ]);
            }
        }

        return '';
    }

    public function treeFragment(): JsonResponse
    {
        $html = '';
        foreach ($this->tree() as $node) {
            $html .= app(View::class)->render('admin.categories._row', [
                'node' => $node,
                'locale' => $this->translator->getLocale(),
            ]);
        }

        return $this->json(['success' => true, 'html' => $html]);
    }

    public function row(int $id): JsonResponse
    {
        $html = $this->rowHtml($id);
        if ($html === '') {
            return $this->json(['success' => false, 'message' => trans('common.no_results')], 404);
        }

        return $this->json(['success' => true, 'html' => $html]);
    }

    public function editor(int $id): Response
    {
        $category = $this->categories->find($id);
        $seo = json_decode((string) ($category['seo'] ?? ''), true);

        $html = app(View::class)->render('admin.categories._editor', [
            'category' => $category,
            'translations' => $this->categories->translations($id),
            'tree' => $this->tree(),
            'seoTitle' => (string) (is_array($seo) ? ($seo['title'] ?? '') : ''),
        ]);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /* ----------------------------------------------------------------- writes */

    public function inline(Request $request, int $id): JsonResponse
    {
        try {
            $result = $this->categories->inlineUpdate(
                $id,
                (string) $request->input('field', ''),
                $request->input('value'),
                (string) ($request->input('locale') ?: $this->translator->getLocale()),
            );
        } catch (InvalidArgumentException | Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return $this->json(['success' => true, 'data' => $result]);
    }

    public function quickStore(Request $request): JsonResponse
    {
        try {
            $id = $this->categories->quickCreate(
                (string) $request->input('name', ''),
                max(0, (int) $request->input('parent_id', 0)),
                $this->translator->getLocale(),
            );
        } catch (InvalidArgumentException | Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return $this->json([
            'success' => true,
            'message' => trans('common.saved'),
            'id' => $id,
            'html' => $this->rowHtml($id),
        ]);
    }

    /** Full details save from the expanded row editor (image excluded). */
    public function section(Request $request, int $id): JsonResponse
    {
        $data = $this->validatedDetails($request);

        try {
            $this->categories->update($id, $this->coreFields($data), $this->translationsFrom($data));
        } catch (InvalidArgumentException | Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return $this->json(['success' => true, 'message' => trans('common.saved')]);
    }

    /** Replace or clear the category image; retired files go to the trash. */
    public function image(Request $request, int $id): JsonResponse
    {
        $category = $this->categories->find($id);
        $previous = trim((string) ($category['image'] ?? ''));
        $segments = explode('.', (string) ($category['slug'] ?? ''));
        $label = (string) (end($segments) ?: 'category');

        try {
            if ((string) $request->input('action', '') === 'delete') {
                $this->categories->setImage($id, null);
                if ($previous !== '') {
                    $this->media->trashByPath($previous, $label);
                }

                return $this->json(['success' => true, 'message' => trans('common.deleted'), 'path' => '']);
            }

            $file = $request->hasFile('image') ? $request->file('image') : null;
            if ($file === null) {
                return $this->json(['success' => false, 'message' => trans('products.media_file_required')], 422);
            }

            $media = $this->media->storeFromUpload($file, 'categories', $id);
            $this->categories->setImage($id, (string) $media->path);
            if ($previous !== '' && $previous !== (string) $media->path) {
                $this->media->trashByPath($previous, $label);
            }
        } catch (InvalidArgumentException | Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return $this->json([
            'success' => true,
            'message' => trans('common.saved'),
            'path' => (string) $media->path,
            'url' => asset((string) $media->path),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        try {
            $this->categories->delete($id);
        } catch (InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return $this->redirect(route('admin.categories.index'))
                ->with('_errors', ['category' => [$e->getMessage()]]);
        }

        if ($request->expectsJson()) {
            return $this->json(['success' => true, 'message' => trans('common.deleted')]);
        }

        return $this->redirect(route('admin.categories.index'))
            ->with('_success', trans('common.deleted'));
    }

    public function move(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $direction = (string) $request->input('direction', 'up');
        $this->categories->move($id, $direction === 'down' ? 'down' : 'up');

        if ($request->expectsJson()) {
            return $this->json(['success' => true, 'message' => trans('common.saved')]);
        }

        return $this->redirect(route('admin.categories.index'))
            ->with('_success', trans('common.saved'));
    }

    /* --------------------------------------------------------------- helpers */

    /** @return array<string, mixed> */
    private function validatedDetails(Request $request): array
    {
        $rules = [
            'parent_id' => 'nullable|integer',
            'slug_segment' => 'nullable|string|max:80',
            'status' => 'required|in:active,inactive',
            'is_featured' => 'nullable|in:0,1',
            'sort_order' => 'nullable|integer',
            'icon' => 'nullable|string|max:255',
            'seo_title' => 'nullable|string|max:255',
        ];
        $fallback = (string) config('localization.fallback', 'en');
        foreach ($this->translator->locales() as $locale) {
            $rules['name_' . $locale] = ($locale === $fallback ? 'required|' : 'nullable|') . 'string|max:255';
            $rules['description_' . $locale] = 'nullable|string';
        }

        return $request->validate($rules);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed> never carries an "image" key: the image is
     *                              managed by its own endpoint and update()
     *                              would treat an empty value as "clear it".
     */
    private function coreFields(array $data): array
    {
        return [
            'parent_id' => ($data['parent_id'] ?? '') !== '' ? (int) $data['parent_id'] : null,
            'slug_segment' => (string) ($data['slug_segment'] ?? ''),
            'icon' => (string) ($data['icon'] ?? ''),
            'is_featured' => (string) ($data['is_featured'] ?? '0') === '1',
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'status' => (string) $data['status'],
            'seo_title' => (string) ($data['seo_title'] ?? ''),
        ];
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
