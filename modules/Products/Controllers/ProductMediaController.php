<?php

declare(strict_types=1);

namespace Modules\Products\Controllers;

use App\Http\Controllers\Controller;
use Core\Exceptions\AppException;
use Core\Http\RedirectResponse;
use Core\Http\Request;
use Modules\Products\Services\ProductMediaService;
use Modules\Products\Services\ProductService;

class ProductMediaController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly ProductMediaService $media,
    ) {
    }

    public function store(Request $request, int $id): RedirectResponse
    {
        $this->products->find($id);

        $section = (string) $request->input('section', 'photos');
        $files = $this->uploadedFiles($request);
        $stored = 0;
        $failed = [];

        foreach ($files as $file) {
            try {
                $this->media->upload($id, $file, $section);
                $stored++;
            } catch (AppException $exception) {
                $failed[] = $exception->getMessage();
            } catch (\Throwable $exception) {
                $failed[] = $exception->getMessage();
            }
        }

        $redirect = $this->redirect($this->backTo($id));
        if ($stored > 0) {
            $redirect = $redirect->with('_success', trans('products.media_uploaded'));
        }
        if ($failed !== []) {
            $redirect = $redirect->with('_errors', ['upload' => $failed]);
        }

        return $redirect;
    }

    public function move(Request $request, int $id, int $attachmentId): RedirectResponse
    {
        $this->media->move($attachmentId, (string) $request->input('section', 'photos'));

        return $this->redirect($this->backTo($id))->with('_success', trans('products.media_moved'));
    }

    public function primary(int $id, int $attachmentId): RedirectResponse
    {
        $this->media->makePrimary($attachmentId);

        return $this->redirect($this->backTo($id))->with('_success', trans('products.media_primary_set'));
    }

    public function order(Request $request, int $id, int $attachmentId): RedirectResponse
    {
        $this->media->reorder($attachmentId, (string) $request->input('direction', 'up'));

        return $this->redirect($this->backTo($id))->with('_success', trans('products.media_reordered'));
    }

    public function destroy(int $id, int $attachmentId): RedirectResponse
    {
        $this->media->remove($attachmentId);

        return $this->redirect($this->backTo($id))->with('_success', trans('products.media_removed'));
    }

    private function backTo(int $id): string
    {
        return route('admin.products.edit', ['id' => $id]);
    }

    private function uploadedFiles(Request $request): array
    {
        $entry = $request->file('images');
        if ($entry === null || !isset($entry['name'])) {
            return [];
        }

        if (!is_array($entry['name'])) {
            return $entry['error'] === UPLOAD_ERR_NO_FILE ? [] : [$entry];
        }

        $files = [];
        foreach (array_keys($entry['name']) as $index) {
            $file = [
                'name' => $entry['name'][$index],
                'type' => $entry['type'][$index] ?? '',
                'tmp_name' => $entry['tmp_name'][$index] ?? '',
                'error' => $entry['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $entry['size'][$index] ?? 0,
            ];

            if ((int) $file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $files[] = $file;
        }

        return $files;
    }
}
