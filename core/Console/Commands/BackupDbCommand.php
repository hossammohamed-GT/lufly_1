<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;

final class BackupDbCommand extends Command
{
    protected string $name = 'backup:db';

    protected string $description = 'Create a timestamped backup of the database in storage/backups.';

    public function handle(array $args, array $options): int
    {
        $backupDir = $this->app->storagePath('backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $connection = config('database.default', 'sqlite');
        $timestamp = date('Y-m-d-His');

        if ($connection === 'sqlite') {
            $source = $this->app->basePath((string) config('database.connections.sqlite.database', 'database/lufly.sqlite'));
            if (!file_exists($source)) {
                $this->error("SQLite database file not found at: {$source}");
                return 1;
            }

            $target = "{$backupDir}/lufly-backup-{$timestamp}.sqlite";
            if (!copy($source, $target)) {
                $this->error("Failed to copy database file.");
                return 1;
            }

            $sizeKb = round(filesize($target) / 1024, 2);
            $this->success("Backup completed successfully! [{$sizeKb} KB] -> {$target}");
            return 0;
        }

        if ($connection === 'mysql') {
            $host = (string) config('database.connections.mysql.host', '127.0.0.1');
            $port = (string) config('database.connections.mysql.port', '3306');
            $database = (string) config('database.connections.mysql.database', 'lufly');
            $user = (string) config('database.connections.mysql.username', 'root');
            $pass = (string) config('database.connections.mysql.password', '');
            $target = "{$backupDir}/lufly-backup-{$timestamp}.sql";

            // Write credentials to a protected temporary options file (readable only by current process owner)
            $cnfFile = tempnam($backupDir, 'my_cnf_');
            if ($cnfFile === false) {
                $this->error('Failed to create secure credentials file.');
                return 1;
            }
            chmod($cnfFile, 0600);

            $cnfContent = "[client]\n"
                . "host=" . addcslashes($host, "\"\\\n") . "\n"
                . "port=" . (int) $port . "\n"
                . "user=" . addcslashes($user, "\"\\\n") . "\n"
                . "password=" . addcslashes($pass, "\"\\\n") . "\n";

            file_put_contents($cnfFile, $cnfContent, LOCK_EX);

            try {
                $cmd = sprintf(
                    'mysqldump --defaults-extra-file=%s --single-transaction --quick %s > %s',
                    escapeshellarg($cnfFile),
                    escapeshellarg($database),
                    escapeshellarg($target)
                );

                exec($cmd, $output, $code);
            } finally {
                @unlink($cnfFile);
            }

            if ($code === 0 && file_exists($target)) {
                $sizeKb = round(filesize($target) / 1024, 2);
                $this->success("MySQL backup completed successfully! [{$sizeKb} KB] -> {$target}");
                return 0;
            }

            $this->error("mysqldump failed with code: {$code}");
            return 1;
        }

        $this->error("Unsupported database driver: {$connection}");
        return 1;
    }
}
