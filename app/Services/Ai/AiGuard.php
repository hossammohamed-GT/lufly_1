<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Throwable;

/**
 * Guard rails for the public AI endpoints.
 *
 * Five free accounts are a real quota, but a public form can burn it in a
 * minute, so every call is counted per visitor (hashed IP) and per day — the
 * same shape as the saved-list e-mail throttle. Counters live in `ai_usage`,
 * which also feeds the admin overview of how much quota went where.
 */
class AiGuard
{
    private ?string $ipHash = null;

    /** @param array<string, mixed> $meta */
    public function record(string $scope, array $meta = []): void
    {
        try {
            AiUsageLog::create([
                'scope' => $scope,
                'ip_hash' => $this->ipHash(),
                'key_slot' => (int) ($meta['key'] ?? 0),
                'model' => (string) ($meta['model'] ?? ''),
                'prompt_tokens' => (int) ($meta['prompt_tokens'] ?? 0),
                'output_tokens' => (int) ($meta['output_tokens'] ?? 0),
                'duration_ms' => (int) ($meta['ms'] ?? 0),
                'ok' => (bool) ($meta['ok'] ?? true),
                'day' => date('Y-m-d'),
                'error' => mb_substr((string) ($meta['error'] ?? ''), 0, 250),
            ]);
        } catch (Throwable) {
            /* counting must never block a feature */
        }
    }

    /** How many requests this visitor already spent today. */
    public function usedToday(string $scope): int
    {
        try {
            return AiUsageLog::query()
                ->where('ip_hash', $this->ipHash())
                ->where('day', date('Y-m-d'))
                ->where('scope', $scope)
                ->count();
        } catch (Throwable) {
            return 0;
        }
    }

    /** True while the visitor may still ask. */
    public function allows(string $scope, ?int $limit = null): bool
    {
        $limit ??= (int) config('ai.daily_limit_per_ip', 15);

        return $limit <= 0 || $this->usedToday($scope) < $limit;
    }

    /** Remaining requests for this visitor today (-1 = unlimited). */
    public function remaining(string $scope, ?int $limit = null): int
    {
        $limit ??= (int) config('ai.daily_limit_per_ip', 15);

        return $limit <= 0 ? -1 : max(0, $limit - $this->usedToday($scope));
    }

    /** @return array<string, int> tokens spent on a day, whole site */
    public function spendOn(string $day): array
    {
        try {
            $rows = AiUsageLog::query()->where('day', $day)->get();
        } catch (Throwable) {
            return ['calls' => 0, 'failed' => 0, 'prompt_tokens' => 0, 'output_tokens' => 0];
        }

        $out = ['calls' => 0, 'failed' => 0, 'prompt_tokens' => 0, 'output_tokens' => 0];
        foreach ($rows as $row) {
            $out['calls']++;
            $out['failed'] += $row->ok ? 0 : 1;
            $out['prompt_tokens'] += (int) $row->prompt_tokens;
            $out['output_tokens'] += (int) $row->output_tokens;
        }

        return $out;
    }

    public function ipHash(): string
    {
        if ($this->ipHash !== null) {
            return $this->ipHash;
        }

        $ip = request()->ip();

        return $this->ipHash = $ip === ''
            ? 'unknown'
            : hash_hmac('sha256', $ip, (string) config('app.key', 'lufly'));
    }
}
