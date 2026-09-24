<?php

declare(strict_types=1);

namespace Core\Contracts;

use Core\Http\Request;
use Core\Http\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
