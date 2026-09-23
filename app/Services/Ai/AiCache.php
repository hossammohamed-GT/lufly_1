<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Closure;
use Throwable;

/**
 * Cache in front of the AI calls.
 *
 * The rule for a free quota: never ask the same question twice. A visitor who
 * uploads the same picture, or a page that renders the same product summary,
 * should hit the database instead of a model.
 *
 * Failures are cached too, but only briefly (a few minutes), so one quota spike
 * does not turn into a storm of retries — and a fixed model is picked up soon.
 */
class AiCache
{
    /** how long a failed answer is remembered, in minutes */
    private const FAILURE_MINUTES = 5;

    /**
     * Return the cached answer for $fingerprint, or run $callback once and store
     * its result. $callback receives nothing and returns an array payload.
     *
     * @param Closure():array<string, mixed> $callback
     * @return array<string, mixed>
     */
    public function remember(string $scope, string $fingerprint, Closure $callback, ?int $hours = null): array
    {
        $key = $this->hash($fingerprint);
        $hours ??= (int) config('ai.cache_hours', 720);

        $cached = $this->get($scope, $key);
        if ($cached !== null) {
            $this->touch($scope, $key);

            return $cached;
        }

        $payload = $callback();
        $this->put($scope, $key, $payload, $hours);

        return $payload;
    }

    /** @return array<string, mixed>|null */
    public function get(string $scope, string $key): ?array
    {
        try {
            $row = AiCacheEntry::query()
                ->where('scope', $scope)
                ->where('fingerprint', $key)
                ->first();
        } catch (Throwable) {
            return null; /* the cache is never allowed to break a feature */
        }

        if (!$row instanceof AiCacheEntry) {
            return null;
        }

        $expires = (string) ($row->expires_at ?? '');
        if ($expires !== '' && strtotime($expires) !== false && strtotime($expires) < time()) {
            return null;
        }

        $payload = $row->payload;

        return is_array($payload) && $payload !== [] ? $payload : null;
    }

    /** @param array<string, mixed> $payload */
    public function put(string $scope, string $key, array $payload, ?int $hours = null): void
    {
        if ($payload === []) {
            return;
        }

        /* a failed answer is remembered for a few minutes, a good one for the
           cache life. Payloads that are not AI answers at all (the planner's
           own plan, for one) carry no `ok` and are treated as good. */
        $failed = ($payload['ok'] ?? null) === false;
        $hours ??= (int) config('ai.cache_hours', 720);

        $expires = $failed
            ? time() + self::FAILURE_MINUTES * 60
            : time() + max(1, $hours) * 3600;

        try {
            $existing = AiCacheEntry::query()
                ->where('scope', $scope)
                ->where('fingerprint', $key)
                ->first();

            $data = [
                'scope' => $scope,
                'fingerprint' => $key,
                'payload' => $payload,
                'model' => (string) ($payload['model'] ?? ''),
                'expires_at' => date('Y-m-d H:i:s', $expires),
            ];

            if ($existing instanceof AiCacheEntry) {
                $existing->update($data);

                return;
            }

            AiCacheEntry::create($data + ['hits' => 0]);
        } catch (Throwable) {
            /* no table yet (feature not migrated) — the call still worked */
        }
    }

    /** Hash any string (or an uploaded file's bytes) into a cache key. */
    public function hash(string $value): string
    {
        return hash('sha256', $value);
    }

    public function purge(?string $scope = null): int
    {
        try {
            $builder = AiCacheEntry::query();
            if ($scope !== null && $scope !== '') {
                $builder->where('scope', $scope);
            }

            return (int) $builder->delete();
        } catch (Throwable) {
            return 0;
        }
    }

    private function touch(string $scope, string $key): void
    {
        try {
            $row = AiCacheEntry::query()
                ->where('scope', $scope)
                ->where('fingerprint', $key)
                ->first();

            if ($row instanceof AiCacheEntry) {
                $row->update(['hits' => (int) $row->hits + 1]);
            }
        } catch (Throwable) {
            /* hit counting is decoration */
        }
    }
}
