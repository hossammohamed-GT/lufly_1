<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CategoryAdminService;
use App\Services\MediaService;
use Core\Http\RedirectResponse;
use Core\Http\Request;
use Core\Http\Response;
use Core\Localization\Translator;
use InvalidArgumentException;
use Throwable;

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
        $fallback = (string) config('localization.fallback', 'en');

        return $this->view('admin.categories.index', [
            'title' => trans('common.categories'),
            'tree' => $this->categories->flatTree($this->translator->getLocale(), $fallback),
        ]);
    }

    public function create(Request $request): Response
    {
        $fallback = (string) config('localization.fallback', 'en');

        return $this->view('admin.categories.form', [
            'title' => trans('categories.create'),
            'category' => null,
            'translations' => [],
            'tree' => $this->categories->flatTree($this->translator->getLocale(), $fallback),
            'selectedParent' => max(0, (int) $request->query('parent', '0')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            $id = $this->categories->create($this->coreFields($data), $this->translationsFrom($data));
            $this->storeImage($request, $id);
        } catch (InvalidArgumentException $e) {
            return $this->backWithError(route('admin.categories.create'), $e->getMessage(), $request);
        } catch (Throwable $e) {
            return $this->backWithError(route('admin.categories.create'), $e->getMessage(), $request);
        }

        return $this->redirect(route('admin.categories.edit', ['id' => $id]))
            ->with('_success', trans('common.saved'));
    }

    public function edit(int $id): Response
    {
        $fallback = (string) config('localization.fallback', 'en');
        $category = $this->categories->find($id);

        return $this->view('admin.categories.form', [
            'title' => trans('categories.edit'),
            'category' => $category,
            'translations' => $this->categories->translations($id),
            'tree' => $this->categories->flatTree($this->translator->getLocale(), $fallback),
            'selectedParent' => (int) ($category['parent_id'] ?? 0),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            $this->categories->update($id, $this->coreFields($data), $this->translationsFrom($data));
            $this->storeImage($request, $id);
        } catch (InvalidArgumentException $e) {
            return $this->backWithError(route('admin.categories.edit', ['id' => $id]), $e->getMessage(), $request);
        } catch (Throwable $e) {
            return $this->backWithError(route('admin.categories.edit', ['id' => $id]), $e->getMessage(), $request);
        }

        return $this->redirect(route('admin.categories.edit', ['id' => $id]))
            ->with('_success', trans('common.saved'));
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->categories->delete($id);
        } catch (InvalidArgumentException $e) {
            return $this->redirect(route('admin.categories.index'))
                ->with('_errors', ['category' => [$e->getMessage()]]);
        }

        return $this->redirect(route('admin.categories.index'))
            ->with('_success', trans('common.deleted'));
    }

    public function move(Request $request, int $id): RedirectResponse
    {
        $direction = (string) $request->input('direction', 'up');
        $this->categories->move($id, $direction === 'down' ? 'down' : 'up');

        return $this->redirect(route('admin.categories.index'))
            ->with('_success', trans('common.saved'));
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        $this->categories->toggleStatus($id);

        return $this->redirect(route('admin.categories.index'))
            ->with('_success', trans('common.saved'));
    }

    /* --------------------------------------------------------------- helpers */

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $rules = [
            'parent_id' => 'nullable|integer',
            'slug_segment' => 'nullable|string|max:80',
            'status' => 'required|in:active,inactive',
            'is_featured' => 'nullable|in:0,1',
            'sort_order' => 'nullable|integer',
            'icon' => 'nullable|string|max:255',
            'image_path' => 'nullable|string|max:255',
            'seo_title' => 'nullable|string|max:255',
            'image' => 'nullable|file',
        ];
        $fallback = (string) config('localization.fallback', 'en');
        foreach ($this->translator->locales() as $locale) {
            $rules['name_' . $locale] = ($locale === $fallback ? 'required|' : 'nullable|') . 'string|max:255';
            $rules['description_' . $locale] = 'nullable|string';
        }

        return $request->validate($rules);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function coreFields(array $data): array
    {
        return [
            'parent_id' => ($data['parent_id'] ?? '') !== '' ? (int) $data['parent_id'] : null,
            'slug_segment' => (string) ($data['slug_segment'] ?? ''),
            'image' => (string) ($data['image_path'] ?? ''),
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

    private function storeImage(Request $request, int $id): void
    {
        if (!$request->hasFile('image')) {
            return;
        }
        $file = $request->file('image');
        if ($file === null) {
            return;
        }
        $media = $this->media->storeFromUpload($file, 'categories', $id);
        $this->categories->setImage($id, (string) $media->path);
    }

    private function backWithError(string $url, string $message, Request $request): RedirectResponse
    {
        return $this->redirect($url)
            ->with('_errors', ['category' => [$message]])
            ->withInput($request->all());
    }
}
