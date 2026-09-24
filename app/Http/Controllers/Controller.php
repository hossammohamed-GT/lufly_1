<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Core\Http\JsonResponse;
use Core\Http\RedirectResponse;
use Core\Http\Response;

abstract class Controller
{
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        return Response::view($template, $data, $status);
    }

    protected function json(array $data, int $status = 200): JsonResponse
    {
        return new JsonResponse($data, $status);
    }

    protected function redirect(string $to, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($to, $status);
    }
}
