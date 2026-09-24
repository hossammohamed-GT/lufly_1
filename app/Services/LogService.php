<?php

declare(strict_types=1);

namespace App\Services;

use Core\Logging\Log;

class LogService
{
    public function __construct(
        private readonly ActivityLogger $activity,
        private readonly AuditService $audit,
        private readonly SecurityLogger $security,
    ) {
    }

    public function activity(): ActivityLogger
    {
        return $this->activity;
    }

    public function audit(): AuditService
    {
        return $this->audit;
    }

    public function security(): SecurityLogger
    {
        return $this->security;
    }

    public function info(string $message, array $context = [], string $channel = 'app'): void
    {
        Log::channel($channel)->info($message, $context);
    }

    public function error(string $message, array $context = [], string $channel = 'error'): void
    {
        Log::channel($channel)->error($message, $context);
    }
}
