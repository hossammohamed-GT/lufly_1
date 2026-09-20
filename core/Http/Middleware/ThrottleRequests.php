<?php

declare(strict_types=1);

namespace Core\Http\Middleware;

use App\Services\CacheService;
use Core\Contracts\MiddlewareInterface;
use Core\Exceptions\AppException;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Fixed-window rate limiter backed by the file cache (i.e. server-side,
 * NOT the visitor's session — discarding cookies does not reset it).
 * Optional per-alias parameter "max,seconds" e.g. throttle:60,60.
 */
final class ThrottleRequests implements MiddlewareInterface
{
    public function __construct(
        private readonly CacheService $cache,
        private readonly ?string $parameter = null,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        [$max, $seconds] = $this->limits();

        $window = intdiv(time(), $seconds);
        $key = 'throttle:' . sha1(($this->parameter ?? 'default') . '|' . $request->ip()) . ':' . $window;
        $count = $this->cache->incrementWindow($key, $seconds * 2);

        if ($count > $max) {
            \Core\Logging\Log::channel('security')->warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'limit' => $max . '/' . $seconds . 's',
            ]);

            throw (new AppException('Too many requests. Please slow down and try again shortly.', 429, 'rate_limited'))
                ->withExtra(['retry_after' => $seconds - (time() % $seconds)]);
        }

        $response = $next($request);
        $response->header('X-RateLimit-Limit', (string) $max);
        $response->header('X-RateLimit-Remaining', (string) max(0, $max - $count));

        return $response;
    }

    /** @return array{0: int, 1: int} */
    private function limits(): array
    {
        $max = 120;
        $seconds = 60;

        if ($this->parameter !== null && str_contains($this->parameter, ',')) {
            [$m, $s] = explode(',', $this->parameter, 2);
            $max = max(1, (int) $m);
            $seconds = max(1, (int) $s);
        }

        return [$max, $seconds];
    }
}
