<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;

final class ServeCommand extends Command
{
    protected string $name = 'serve';

    protected string $description = 'Start the PHP built-in server for local development (php -S).';

    public function handle(array $args, array $options): int
    {
        $host = is_string($this->option($options, 'host', '0.0.0.0')) ? (string) $this->option($options, 'host', '0.0.0.0') : '0.0.0.0';
        $port = is_string($this->option($options, 'port', '8080')) ? (string) $this->option($options, 'port', '8080') : '8080';
        $router = $this->app->basePath('server.php');

        $this->info("Serving on http://{$host}:{$port}");

        passthru(escapeshellarg(PHP_BINARY) . ' -S ' . $host . ':' . $port . ' ' . escapeshellarg($router), $exit);

        return (int) $exit;
    }
}
