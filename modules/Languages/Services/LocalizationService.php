<?php

declare(strict_types=1);

namespace Modules\Languages\Services;

use App\Services\ActivityLogger;
use Core\Database\DatabaseManager;
use Modules\Languages\Models\Language;
use Modules\Languages\Repositories\LanguageRepository;

class LocalizationService extends \App\Services\LocalizationService
{
    public function __construct(
        private readonly LanguageRepository $languages,
        private readonly DatabaseManager $db,
        private readonly ActivityLogger $activity,
    ) {
    }

    public function languages(): array
    {
        return $this->languages->all(['sort_order' => 'asc']);
    }

    public function activeLanguages(): array
    {
        return $this->languages->activeLanguages();
    }

    public function storeLanguage(array $data): object
    {
        $language = $this->languages->create($data);
        $this->activity->created('language', $language->id, ['code' => $data['code'] ?? '']);

        return $language;
    }

    public function updateLanguage(int $id, array $data): bool
    {
        $updated = $this->languages->update($id, $data);
        $this->activity->updated('language', $id);

        return $updated;
    }

    public function toggleLanguage(int $id): bool
    {
        $language = $this->languages->find($id);
        if ($language === null) {
            return false;
        }

        return $this->languages->update($id, ['active' => !$language->active]);
    }

    public function getTranslations(string $entity, int|string $entityId): array
    {
        $table = $entity . '_translations';
        $rows = $this->db->connection()->select(
            "SELECT * FROM {$table} WHERE {$entity}_id = ?",
            [(int) $entityId],
        );

        $out = [];
        foreach ($rows as $row) {
            $out[$row['locale']] = $row;
        }

        return $out;
    }

    public function upsertTranslation(string $entity, int|string $entityId, string $locale, array $fields): void
    {
        $table = $entity . '_translations';
        $connection = $this->db->connection();

        $fields[$entity . '_id'] = (int) $entityId;
        $fields['locale'] = $locale;

        $exists = $connection->table($table)
            ->where($entity . '_id', (int) $entityId)
            ->where('locale', $locale)
            ->exists();

        if ($exists) {
            $connection->table($table)
                ->where($entity . '_id', (int) $entityId)
                ->where('locale', $locale)
                ->update($fields);
            return;
        }

        $connection->insert($table, $fields);
    }

    public function syncTranslations(string $entity, int|string $entityId, array $byLocale): void
    {
        foreach ($byLocale as $locale => $fields) {
            if ($fields === []) {
                continue;
            }
            $this->upsertTranslation($entity, $entityId, (string) $locale, $fields);
        }
    }
}
