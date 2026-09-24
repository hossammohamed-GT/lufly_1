<?php

declare(strict_types=1);

namespace Modules\Languages\Repositories;

use App\Repositories\Repository;
use Modules\Languages\Models\Language;

class LanguageRepository extends Repository
{
    protected string $model = Language::class;

    protected bool $auditing = true;

    protected string $auditEntity = 'language';

    public function activeLanguages(): array
    {
        $items = Language::query()
            ->where('active', 1)
            ->orderBy('sort_order', 'asc')
            ->get();

        return $items;
    }

    public function findByCode(string $code): ?Language
    {
        $language = Language::query()->where('code', $code)->first();

        return $language;
    }
}
