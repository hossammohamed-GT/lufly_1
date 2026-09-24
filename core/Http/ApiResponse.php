<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Database\Paginator;

final class ApiResponse
{
    public static function success(?array $data = null, string $message = '', int $status = 200, array $extra = []): JsonResponse
    {
        return new JsonResponse(array_merge([
            'success' => true,
            'message' => $message,
            'data' => $data ?? new \stdClass(),
        ], $extra), $status);
    }

    public static function error(string $message = '', array $errors = [], int $status = 400, string $errorCode = ''): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors !== [] ? $errors : new \stdClass(),
        ] + ($errorCode !== '' ? ['error_code' => $errorCode] : []), $status);
    }

    public static function paginated(array $items, Paginator $paginator, string $message = ''): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'meta' => [
                'page' => $paginator->page(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
