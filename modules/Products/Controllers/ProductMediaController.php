<?php

declare(strict_types=1);

namespace Modules\Products\Controllers;

use App\Http\Controllers\Controller;
use Core\Http\JsonResponse;
use Core\Http\Request;
use Modules\Products\Services\ProductMediaService;
use Modules\Products\Services\ProductService;

/**
 * Admin JSON endpoints behind the image sections of the product card
 * (photos / technical drawings / installed shots). Everything answers
 * JSON — the card UI updates in place and never reloads the page.
 */
class ProductMediaController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly ProductMediaService $media,
    ) {
    }

    /** Upload one or more files into a section. */
    public function store(Request $request, int $id): JsonResponse
    {
        $this->products->find($id);

        $section = (string) $request->input('section', 'photos');
        $attachments = [];
        $failed = [];

        foreach ($this->uploadedFiles($request) as $file) {
            try {
                $attachmentId = $this->media->upload($id, $file, $section);
                if ($attachmentId > 0) {
                    $attachments[] = $this->media->attachmentPayload($attachmentId);
                }
            } catch (\Throwable $exception) {
                $failed[] = $exception->getMessage();
            }
        }

        if ($attachments === [] && $failed !== []) {
            return $this->json(['success' => false, 'message' => $failed[0], 'errors' => $failed], 422);
        }

        return $this->json([
            'success' => true,
            'message' => trans('products.media_uploaded'),
            'data' => ['attachments' => $attachments, 'errors' => $failed],
        ]);
    }

    /** Swap the file behind one attachment; the old file goes to deleted-images/. */
    public function replace(Request $request, int $id, int $attachmentId): JsonResponse
    {
        $this->products->find($id);

        $file = $request->file('image');
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $this->json(['success' => false, 'message' => trans('products.media_file_required')], 422);
        }

        try {
            $payload = $this->media->replace($attachmentId, $file);
        } catch (\Throwable $exception) {
            return $this->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }

        return $this->json([
            'success' => true,
            'message' => trans('products.media_replaced'),
            'data' => $payload,
        ]);
    }

    public function move(Request $request, int $id, int $attachmentId): JsonResponse
    {
        $payload = $request->jsonPayload() ?? $request->all();
        $section = (string) (is_array($payload) ? ($payload['section'] ?? 'photos') : 'photos');

        try {
            $this->media->move($attachmentId, $section);
        } catch (\Throwable $exception) {
            return $this->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }

        return $this->json([
            'success' => true,
            'message' => trans('products.media_moved'),
            'data' => $this->media->attachmentPayload($attachmentId),
        ]);
    }

    public function primary(int $id, int $attachmentId): JsonResponse
    {
        try {
            $this->media->makePrimary($attachmentId);
        } catch (\Throwable $exception) {
            return $this->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }

        return $this->json([
            'success' => true,
            'message' => trans('products.media_primary_set'),
            'data' => $this->media->attachmentPayload($attachmentId),
        ]);
    }

    public function order(Request $request, int $id, int $attachmentId): JsonResponse
    {
        $payload = $request->jsonPayload() ?? $request->all();
        $direction = (string) (is_array($payload) ? ($payload['direction'] ?? 'up') : 'up');

        $this->media->reorder($attachmentId, $direction);

        return $this->json(['success' => true, 'message' => trans('products.media_reordered')]);
    }

    public function destroy(int $id, int $attachmentId): JsonResponse
    {
        $this->media->remove($attachmentId);

        return $this->json(['success' => true, 'message' => trans('products.media_removed')]);
    }

    /**
     * Flatten one or many uploaded files ($_FILES entries) into a plain list.
     *
     * @return array<int, array<string, mixed>>
     */
    private function uploadedFiles(Request $request): array
    {
        $entry = $request->file('images');
        if ($entry === null || !isset($entry['name'])) {
            return [];
        }

        /* single input: ['name' => string, 'tmp_name' => string, ...] */
        if (!is_array($entry['name'])) {
            return $entry['error'] === UPLOAD_ERR_NO_FILE ? [] : [$entry];
        }

        /* multiple input (name="images[]"): every key holds an array */
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
