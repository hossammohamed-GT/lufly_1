<?php

declare(strict_types=1);

namespace Core\Console;

use Core\Foundation\Application;
use Throwable;

class Kernel
{
    /** @var array<string, class-string<Command>> */
    private array $commands = [
        'migrate' => Commands\MigrateCommand::class,
        'rollback' => Commands\RollbackCommand::class,
        'seed' => Commands\SeedCommand::class,
        'schema:dump' => Commands\SchemaDumpCommand::class,
        'erd' => Commands\ErdCommand::class,
        'docs:api' => Commands\DocsApiCommand::class,
        'serve' => Commands\ServeCommand::class,
        'key:generate' => Commands\KeyGenerateCommand::class,
        'backup:db' => Commands\BackupDbCommand::class,
        'list' => Commands\ListCommand::class,
    ];

    public function __construct(private readonly Application $app)
    {
    }

    /** @param string[] $argv */
    public function run(array $argv): int
    {
        $name = $argv[1] ?? 'list';
        [$args, $options] = $this->parse(array_slice($argv, 2));

        if (!isset($this->commands[$name])) {
            fwrite(STDERR, "Unknown command [{$name}]. Run `php cli list`." . PHP_EOL);
            return 1;
        }

        $command = $this->app->make($this->commands[$name]);

        try {
            return $command->handle($args, $options);
        } catch (Throwable $e) {
            fwrite(STDERR, '[' . $e::class . '] ' . $e->getMessage() . PHP_EOL);
            if ($this->app->isDebug()) {
                fwrite(STDERR, $e->getTraceAsString() . PHP_EOL);
            }
            return 1;
        }
    }

    /**
     * @param string[] $argv
     * @return array{0: string[], 1: array<string, string|bool>}
     */
    private function parse(array $argv): array
    {
        $args = [];
        $options = [];

        foreach ($argv as $token) {
            if (str_starts_with($token, '--')) {
                $pair = substr($token, 2);
                [$key, $value] = array_pad(explode('=', $pair, 2), 2, true);
                $options[$key] = $value;
            } else {
                $args[] = $token;
            }
        }

        return [$args, $options];
    }

    /** @return array<string, class-string<Command>> */
    public function commands(): array
    {
        return $this->commands;
    }
}
