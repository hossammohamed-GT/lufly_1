<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Closure;
use Throwable;

class AiCache
{
    private const FAILURE_MINUTES = 5;

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

    public function get(string $scope, string $key): ?array
    {
        try {
            $row = AiCacheEntry::query()
                ->where('scope', $scope)
                ->where('fingerprint', $key)
                ->first();
        } catch (Throwable) {
            return null; }

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

    public function put(string $scope, string $key, array $payload, ?int $hours = null): void
    {
        if ($payload === []) {
            return;
        }

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
            }
    }

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
            }
    }
}
