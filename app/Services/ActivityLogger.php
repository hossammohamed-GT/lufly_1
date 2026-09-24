<?php

declare(strict_types=1);

namespace App\Services;

use Core\Auth\Auth;
use Core\Database\DatabaseManager;
use Core\Logging\Log;

class ActivityLogger
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly Auth $auth,
    ) {
    }

    public function log(string $action, string $entity, int|string|null $entityId = null, string $description = '', array $data = [], ?int $userId = null): void
    {
        $userId ??= $this->auth->id() !== null ? (int) $this->auth->id() : null;

        try {
            $this->db->connection()->insert('activity_logs', [
                'user_id' => $userId,
                'action' => $action,
                'entity' => $entity,
                'entity_id' => $entityId === null ? null : (string) $entityId,
                'description' => $description,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {

        }

        Log::info('activity.' . $action, [
            'entity' => $entity,
            'entity_id' => $entityId,
            'user_id' => $userId,
        ]);
    }

    public function created(string $entity, int|string|null $entityId, array $data = []): void
    {
        $this->log('create', $entity, $entityId, '', $data);
    }

    public function updated(string $entity, int|string|null $entityId, array $data = []): void
    {
        $this->log('update', $entity, $entityId, '', $data);
    }

    public function deleted(string $entity, int|string|null $entityId): void
    {
        $this->log('delete', $entity, $entityId);
    }

    public function login(int|string|null $userId, string $email): void
    {
        $this->log('login', 'user', $userId, '', ['email' => $email]);
    }

    public function logout(int|string|null $userId): void
    {
        $this->log('logout', 'user', $userId);
    }

    public function settingsChanged(array $changes): void
    {
        $this->log('settings_change', 'settings', null, '', $changes);
    }
}
