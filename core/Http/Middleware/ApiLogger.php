<?php

declare(strict_types=1);

namespace Core\Http\Middleware;

use Core\Contracts\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\Response;
use Core\Logging\Log;
use Throwable;

final class ApiLogger implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $start = microtime(true);

        try {
            $response = $next($request);
            $status = $response->getStatus();
        } catch (Throwable $e) {
            $status = method_exists($e, 'httpStatus') ? $e->httpStatus() : 500;
            $this->write($request, $status, microtime(true) - $start);
            throw $e;
        }

        $this->write($request, $status, microtime(true) - $start);

        return $response;
    }

    private function write(Request $request, int $status, float $elapsed): void
    {
        $entry = [
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'status_code' => $status,
            'response_time_ms' => round($elapsed * 1000, 2),
            'ip' => $request->ip(),
        ];

        Log::channel('api')->info('api.request', $entry);

        try {
            \App\Models\ApiLog::create($entry);
        } catch (Throwable) {

        }
    }
}
