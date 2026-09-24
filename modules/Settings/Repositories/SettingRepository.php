<?php

declare(strict_types=1);

namespace Modules\Settings\Repositories;

use App\Repositories\Repository;
use Modules\Settings\Models\Setting;

class SettingRepository extends Repository
{
    protected string $model = Setting::class;

    public function findByKey(string $key): ?Setting
    {
        $setting = Setting::query()->where('key', $key)->first();

        return $setting;
    }
}
