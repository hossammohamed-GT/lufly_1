<?php

declare(strict_types=1);

namespace Modules\Media\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MediaService;
use Core\Http\ApiResponse;
use Core\Http\JsonResponse;
use Core\Http\Request;

class MediaApiController extends Controller
{
    public function __construct(private readonly MediaService $media)
    {
    }

    public function index(): JsonResponse
    {
        $items = array_map(static fn ($media) => $media->toArray(), $this->media->all());

        return ApiResponse::success(['media' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file']);

        $file = $request->file('file');
        $media = $this->media->storeFromUpload(
            $file,
            (string) $request->input('collection', 'general'),
            auth()->id(),
        );

        return ApiResponse::success(['media' => $media->toArray()], trans('common.uploaded'), 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->media->delete($id);

        return ApiResponse::success(null, trans('common.deleted'));
    }
}
