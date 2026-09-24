<?php

declare(strict_types=1);

namespace Core\Console;

use Core\Foundation\Application;

abstract class Command
{
    protected string $name = '';

    protected string $description = '';

    public function __construct(protected Application $app)
    {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    abstract public function handle(array $args, array $options): int;

    protected function line(string $text = ''): void
    {
        fwrite(STDOUT, $text . PHP_EOL);
    }

    protected function success(string $text): void
    {
        $this->line("\033[32m" . $text . "\033[0m");
    }

    protected function error(string $text): void
    {
        $this->line("\033[31m" . $text . "\033[0m");
    }

    protected function info(string $text): void
    {
        $this->line("\033[36m" . $text . "\033[0m");
    }

    protected function option(array $options, string $key, string|bool $default = false): string|bool
    {
        return $options[$key] ?? $default;
    }
}
