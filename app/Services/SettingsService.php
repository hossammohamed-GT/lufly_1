<?php

declare(strict_types=1);

namespace App\Services;

use Core\Database\DatabaseManager;

class SettingsService
{
    private array $runtime = [];

    private bool $loaded = false;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly CacheService $cache,
        private readonly AuditService $audit,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->load();

        return array_key_exists($key, $this->runtime) ? $this->runtime[$key] : $default;
    }

    public function set(string $key, mixed $value, int|string|null $userId = null): void
    {
        $before = $this->get($key);

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
        $connection = $this->db->connection();

        $exists = $connection->table('settings')->where('key', $key)->exists();
        if ($exists) {
            $connection->table('settings')->where('key', $key)->update([
                'value' => $encoded,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $connection->insert('settings', [
                'key' => $key,
                'value' => $encoded,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->runtime[$key] = $value;
        $this->cache->forget($this->cacheKey());

        $this->audit->record('setting', $key, 'update', ['key' => $key, 'value' => $before], ['key' => $key, 'value' => $value], $userId);
    }

    public function setMany(array $values, int|string|null $userId = null): void
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $userId);
        }
    }

    public function all(): array
    {
        $this->load();

        return $this->runtime;
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $cached = $this->cache->remember($this->cacheKey(), 300, function (): array {
            try {
                $rows = $this->db->connection()->select('SELECT `key`, `value` FROM settings');
            } catch (\Throwable) {
                return [];
            }

            $out = [];
            foreach ($rows as $row) {
                $out[$row['key']] = json_decode((string) $row['value'], true);
            }

            return $out;
        });

        $this->runtime = is_array($cached) ? $cached : [];
        $this->loaded = true;
    }

    private function cacheKey(): string
    {
        return 'settings:all';
    }
}
