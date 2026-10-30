<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use App\Services\AdminAccount;
use Core\Console\Command;

/**
 * Create or reset THE single administrator account from .env.
 *
 * The account work itself lives in App\Services\AdminAccount so the web
 * sign-in can do the exact same sync on hosts without a console; this
 * command only resolves the credentials (options first, then .env),
 * validates them and reports the result. The password itself is never
 * printed, logged or rendered anywhere.
 */
final class AdminSetupCommand extends Command
{
    protected string $name = 'admin:setup';

    protected string $description = 'Create/reset the single administrator account from ADMIN_EMAIL/ADMIN_PASSWORD in .env.';

    public function handle(array $args, array $options): int
    {
        $email = strtolower(trim((string) ($this->option($options, 'email') ?: env('ADMIN_EMAIL', ''))));
        $password = (string) ($this->option($options, 'password') ?: env('ADMIN_PASSWORD', ''));
        $adminName = trim((string) ($this->option($options, 'name') ?: env('ADMIN_NAME', 'LUFLY Administrator')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid admin email is required. Set ADMIN_EMAIL in .env or pass --email=you@example.com.');

            return 1;
        }
        if (strlen($password) < 14) {
            $this->error('The admin password must contain at least 14 characters. Set ADMIN_PASSWORD in .env or pass --password=...');

            return 1;
        }

        $this->app->get(AdminAccount::class)->sync($email, $password, $adminName);

        $this->success("Single administrator ready: {$email}");
        $this->line('Every other user account is now inactive.');
        $this->line('Tip: remove ADMIN_PASSWORD from .env now — only its bcrypt hash is stored.');

        return 0;
    }
}
