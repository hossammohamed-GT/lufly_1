<?php

declare(strict_types=1);

namespace App\Services;

use Core\Auth\Auth;
use Core\Database\DatabaseManager;

class AuditService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly Auth $auth,
    ) {
    }

    public function record(string $entity, int|string|null $entityId, string $action, ?array $before, ?array $after, int|string|null $userId = null): void
    {
        $userId ??= $this->auth->id();

        try {
            $this->db->connection()->insert('audits', [
                'user_id' => $userId === null ? null : (string) $userId,
                'entity' => $entity,
                'entity_id' => $entityId === null ? null : (string) $entityId,
                'action' => $action,
                'before' => $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE),
                'after' => $after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {

        }
    }
}
