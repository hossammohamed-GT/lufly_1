<?php

declare(strict_types=1);

namespace App\Services;

abstract class LocalizationService
{
    abstract public function languages(): array;

    abstract public function activeLanguages(): array;

    abstract public function storeLanguage(array $data): object;

    abstract public function updateLanguage(int $id, array $data): bool;

    abstract public function toggleLanguage(int $id): bool;

    abstract public function getTranslations(string $entity, int|string $entityId): array;

    abstract public function upsertTranslation(string $entity, int|string $entityId, string $locale, array $fields): void;

    abstract public function syncTranslations(string $entity, int|string $entityId, array $byLocale): void;
}
