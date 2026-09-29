<?php

declare(strict_types=1);

namespace Core\Http\Middleware;

use Core\Contracts\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\Response;

final class SecurityHeaders implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        $headers = (array) config('security.headers', [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ]);

        foreach ($headers as $name => $value) {
            if ((string) $value !== '') {
                $response->header((string) $name, (string) $value);
            }
        }

        $requestId = trim((string) $request->header('X-Request-ID', ''));
        if (preg_match('/^[A-Za-z0-9._-]{8,128}$/', $requestId) !== 1) {
            $requestId = bin2hex(random_bytes(16));
        }
        $response->header('X-Request-ID', $requestId);

        $csp = trim((string) config('security.csp.policy', ''));
        if ($csp !== '') {
            $header = (bool) config('security.csp.report_only', true)
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';
            $response->header($header, $csp);
        }

        if ($this->isHttps($request) && (bool) config('security.hsts.enabled', false)) {
            $response->header('Strict-Transport-Security', (string) config(
                'security.hsts.value',
                'max-age=31536000; includeSubDomains'
            ));
        }

        return $response;
    }

    private function isHttps(Request $request): bool
    {
        return strtolower((string) $request->server('HTTPS', '')) === 'on'
            || strtolower((string) $request->header('X-Forwarded-Proto', '')) === 'https'
            || (int) $request->server('SERVER_PORT', 0) === 443;
    }
}
