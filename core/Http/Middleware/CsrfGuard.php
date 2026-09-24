<?php

declare(strict_types=1);

namespace Core\Http\Middleware;

use Core\Contracts\MiddlewareInterface;
use Core\Exceptions\AppException;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Session;

final class CsrfGuard implements MiddlewareInterface
{
    private const UNSAFE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(private readonly Session $session)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (in_array($request->method(), self::UNSAFE_METHODS, true)) {

            if ($request->bearerToken() !== null) {
                return $next($request);
            }

            $token = $request->input('_token')
                ?? $request->header('X-CSRF-TOKEN')
                ?? $request->header('X-XSRF-TOKEN');

            $expected = $this->session->csrfToken();

            if (is_string($token) && hash_equals($expected, $token)) {
                return $next($request);
            }

            $isAjax = $request->header('X-Requested-With') === 'XMLHttpRequest';
            if ($isAjax) {
                $origin = (string) ($request->header('Origin') ?? $request->header('Referer') ?? '');
                $appHost = parse_url((string) config('app.url', ''), PHP_URL_HOST)
                    ?: parse_url((string) ($request->server('HTTP_HOST') ?? ''), PHP_URL_HOST)
                    ?: (string) ($request->server('HTTP_HOST') ?? '');

                $reqHost = parse_url($origin, PHP_URL_HOST) ?: '';
                if ($reqHost !== '' && $appHost !== '' && strtolower($reqHost) === strtolower($appHost)) {
                    return $next($request);
                }
            }

            throw (new AppException('CSRF token mismatch.', 419, 'csrf_token_invalid'))
                ->withExtra(['path' => $request->path()]);
        }

        return $next($request);
    }
}
