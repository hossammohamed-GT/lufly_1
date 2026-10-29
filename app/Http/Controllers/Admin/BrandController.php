<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\MediaService;
use App\Services\SlugService;
use Core\Database\DatabaseManager;
use Core\Exceptions\NotFoundException;
use Core\Http\RedirectResponse;
use Core\Http\Request;
use Core\Http\Response;
use Throwable;

final class BrandController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly SlugService $slugs,
        private readonly MediaService $media,
        private readonly ActivityLogger $activity,
    ) {
    }

    public function index(): Response
    {
        $rows = $this->db->connection()->select(
            "SELECT b.*,
                    (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id AND p.deleted_at IS NULL) AS products_total
             FROM brands b
             WHERE b.deleted_at IS NULL
             ORDER BY b.id ASC",
        );

        return $this->view('admin.brands.index', [
            'title' => trans('common.brands'),
            'brands' => $rows,
        ]);
    }

    public function create(): Response
    {
        return $this->view('admin.brands.form', [
            'title' => trans('categories.brand_create'),
            'brand' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $now = date('Y-m-d H:i:s');

        try {
            $slug = $this->slugs->generate(
                (string) (($data['slug'] ?? '') ?: ($data['name'] ?? 'brand')),
                'brands',
            );
            $id = (int) $this->db->connection()->table('brands')->insert([
                'name' => (string) $data['name'],
                'slug' => $slug,
                'logo' => $this->nullableString($data['logo_path'] ?? null),
                'country' => $this->nullableString($data['country'] ?? null),
                'website' => $this->nullableString($data['website'] ?? null),
                'status' => (string) $data['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->storeLogo($request, $id);
        } catch (Throwable $e) {
            return $this->redirect(route('admin.brands.create'))
                ->with('_errors', ['brand' => [$e->getMessage()]])
                ->withInput($request->all());
        }

        $this->activity->created('brand', $id, ['slug' => $slug]);

        return $this->redirect(route('admin.brands.edit', ['id' => $id]))
            ->with('_success', trans('common.saved'));
    }

    public function edit(int $id): Response
    {
        return $this->view('admin.brands.form', [
            'title' => trans('categories.brand_edit'),
            'brand' => $this->find($id),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $current = $this->find($id);
        $data = $this->validated($request);
        $now = date('Y-m-d H:i:s');

        try {
            $slug = (string) $current['slug'];
            $requested = trim((string) ($data['slug'] ?? ''));
            if ($requested !== '' && $requested !== $slug) {
                $slug = $this->slugs->generate($requested, 'brands', 'slug', $id);
            }
            $this->db->connection()->table('brands')->where('id', $id)->update([
                'name' => (string) $data['name'],
                'slug' => $slug,
                'logo' => $this->nullableString($data['logo_path'] ?? null),
                'country' => $this->nullableString($data['country'] ?? null),
                'website' => $this->nullableString($data['website'] ?? null),
                'status' => (string) $data['status'],
                'updated_at' => $now,
            ]);
            $this->storeLogo($request, $id);
        } catch (Throwable $e) {
            return $this->redirect(route('admin.brands.edit', ['id' => $id]))
                ->with('_errors', ['brand' => [$e->getMessage()]])
                ->withInput($request->all());
        }

        $this->activity->updated('brand', $id);

        return $this->redirect(route('admin.brands.edit', ['id' => $id]))
            ->with('_success', trans('common.saved'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->find($id);
        $inUse = $this->db->connection()->selectOne(
            'SELECT COUNT(*) AS total FROM products WHERE brand_id = ? AND deleted_at IS NULL',
            [$id],
        );
        if ((int) ($inUse['total'] ?? 0) > 0) {
            return $this->redirect(route('admin.brands.index'))
                ->with('_errors', ['brand' => [trans('categories.brand_in_use')]]);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->connection()->table('brands')->where('id', $id)->update([
            'deleted_at' => $now,
            'updated_at' => $now,
        ]);
        $this->activity->deleted('brand', $id);

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
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'country' => 'nullable|string|max:100',
            'website' => 'nullable|string|max:255',
            'logo_path' => 'nullable|string|max:255',
            'logo' => 'nullable|file',
        ]);
    }

    private function storeLogo(Request $request, int $id): void
    {
        if (!$request->hasFile('logo')) {
            return;
        }
        $file = $request->file('logo');
        if ($file === null) {
            return;
        }
        $media = $this->media->storeFromUpload($file, 'brands', $id);
        $this->db->connection()->table('brands')->where('id', $id)->update([
            'logo' => (string) $media->path,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
