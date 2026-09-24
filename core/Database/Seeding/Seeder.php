<?php

declare(strict_types=1);

namespace Core\Database\Seeding;

use Core\Database\Connection;
use Core\Foundation\Application;

abstract class Seeder
{
    protected Connection $db;

    protected Application $app;

    public function setContext(Connection $db, Application $app): void
    {
        $this->db = $db;
        $this->app = $app;
    }

    abstract public function run(): void;

    protected function call(string $seeder): void
    {
        if (!class_exists($seeder)) {
            $file = $this->app->basePath('database/seeders/' . basename(str_replace('\\', '/', $seeder)) . '.php');
            if (is_file($file)) {
                require $file;
            }
        }

        $instance = new $seeder();
        $instance->setContext($this->db, $this->app);
        $instance->run();
    }
}
