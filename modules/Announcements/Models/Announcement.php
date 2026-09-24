<?php

declare(strict_types=1);

namespace Modules\Announcements\Models;

use Core\Database\Model;

class Announcement extends Model
{
    protected static string $table = 'announcements';

    protected static array $fillable = [
        'placement', 'style', 'link_url', 'is_active', 'starts_at', 'ends_at', 'sort_order',
    ];

    protected static array $casts = [
        'id' => 'int',
        'is_active' => 'bool',
        'sort_order' => 'int',
    ];

    private ?array $translationsCache = null;

    public function translations(): array
    {
        if ($this->translationsCache === null) {
            $rows = static::db()->connection()->select(
                'SELECT * FROM announcement_translations WHERE announcement_id = ?',
                [$this->getKey()],
            );

            $out = [];
            foreach ($rows as $row) {
                $out[$row['locale']] = $row;
            }
            $this->translationsCache = $out;
        }

        return $this->translationsCache;
    }

    public function translate(string $locale, string $fallback = 'en'): array
    {
        $translations = $this->translations();
        $translation = $translations[$locale]
            ?? $translations[$fallback]
            ?? ($translations ? reset($translations) : []);

        return array_merge($this->attributes(), [
            'message' => (string) ($translation['message'] ?? ''),
            'cta_label' => $translation['cta_label'] ?? null,
        ]);
    }

    public function isLive(): bool
    {
        if ((int) $this->is_active !== 1) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        if ($this->starts_at !== null && (string) $this->starts_at > $now) {
            return false;
        }

        if ($this->ends_at !== null && (string) $this->ends_at < $now) {
            return false;
        }

        return true;
    }
}
