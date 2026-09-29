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
            // Requests authenticated purely via Bearer tokens are not vulnerable to browser cookie CSRF
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

            /* X-Requested-With and Origin are useful signals, not CSRF secrets.
               Every cookie-authenticated mutation must prove possession of the
               session token. Bearer-only clients were exempted above because
               browsers do not attach that credential automatically. */
            throw (new AppException('CSRF token mismatch.', 419, 'csrf_token_invalid'))
                ->withExtra(['path' => $request->path()]);
        }

        return $next($request);
    }
}
