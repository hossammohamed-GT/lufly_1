<?php

declare(strict_types=1);

namespace App\Services;

use Core\Logging\Log;

class SecurityLogger
{
    public function failedLogin(string $email, string $ip): void
    {
        Log::channel('security')->warning('Failed login', ['email' => $email, 'ip' => $ip]);
    }

    public function permissionDenied(int|string|null $userId, string $permission, string $ip): void
    {
        Log::channel('security')->warning('Permission denied', [
            'user_id' => $userId,
            'permission' => $permission,
            'ip' => $ip,
        ]);
    }

    public function event(string $event, array $context = []): void
    {
        Log::channel('security')->info($event, $context);
    }
}
