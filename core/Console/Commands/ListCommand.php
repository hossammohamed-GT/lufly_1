<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;
use Core\Console\Kernel;

final class ListCommand extends Command
{
    protected string $name = 'list';

    protected string $description = 'List all available CLI commands.';

    public function handle(array $args, array $options): int
    {
        $this->line('Lufly Platform CLI');
        $this->line('');
        $this->line('Usage: php cli <command> [--option=value]');
        $this->line('');

        foreach ($this->app->get(Kernel::class)->commands() as $name => $class) {
            $command = $this->app->make($class);
            $this->line(sprintf('  %-16s %s', $name, $command->description()));
        }

        return 0;
    }
}
