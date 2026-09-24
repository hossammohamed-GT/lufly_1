<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;
use Core\Database\DatabaseManager;
use Core\Database\Seeding\Seeder;

final class SeedCommand extends Command
{
    protected string $name = 'seed';

    protected string $description = 'Run database seeders (--class=SpecificSeeder, default: DatabaseSeeder).';

    public function handle(array $args, array $options): int
    {
        $class = $this->option($options, 'class', 'DatabaseSeeder');
        $fqcn = 'Database\\Seeders\\' . (is_string($class) ? $class : 'DatabaseSeeder');

        $path = $this->app->basePath('database/seeders/' . basename(str_replace('\\', '/', $fqcn)) . '.php');
        if (!class_exists($fqcn) && is_file($path)) {
            require $path;
        }
        if (!class_exists($fqcn)) {
            $this->error("Seeder [{$fqcn}] not found.");
            return 1;
        }

        $seeder = new $fqcn();
        $seeder->setContext($this->app->get(DatabaseManager::class)->connection(), $this->app);
        $seeder->run();

        $this->success('Seeded: ' . $fqcn);

        return 0;
    }
}
