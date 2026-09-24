<?php

declare(strict_types=1);

namespace Core\Support;

final class Arr
{
    public static function get(array $array, string|int|null $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $array;
        }
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        $segments = explode('.', (string) $key);
        $value = $array;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function set(array &$array, string|int|null $key, mixed $value): void
    {
        if ($key === null) {
            return;
        }

        $segments = explode('.', (string) $key);
        $target = &$array;
        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $target[$segment] = $value;
                return;
            }
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }
            $target = &$target[$segment];
        }
    }

    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }
}
