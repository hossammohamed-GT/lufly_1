<?php

declare(strict_types=1);

namespace Core\Console;

use Core\Database\Schema\SchemaDefinition;

final class SchemaDiscovery
{
    public static function definitions(string $path): array
    {
        $definitions = [];

        foreach ((array) glob($path . '/*Schema.php') as $file) {
            $class = 'Database\\Schema\\' . basename($file, '.php');
            if (!class_exists($class)) {
                require $file;
            }
            if (class_exists($class) && is_subclass_of($class, SchemaDefinition::class)) {
                $reflection = new \ReflectionClass($class);
                if (!$reflection->isAbstract()) {
                    $definitions[] = new $class();
                }
            }
        }

        usort($definitions, static fn ($a, $b) => strcasecmp($a->tableName(), $b->tableName()));

        return $definitions;
    }
}
