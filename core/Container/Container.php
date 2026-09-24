<?php

declare(strict_types=1);

namespace Core\Container;

use Closure;
use Core\Contracts\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

class Container implements ContainerInterface
{
    private array $bindings = [];

    private array $instances = [];

    private array $resolving = [];

    public function bind(string $abstract, callable|string|null $concrete = null, bool $shared = false): void
    {
        unset($this->instances[$abstract]);
        $this->bindings[$abstract] = ['concrete' => $concrete ?? $abstract, 'shared' => $shared];
    }

    public function singleton(string $abstract, callable|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->bindings[$id]) || class_exists($id);
    }

    public function bound(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->bindings[$id]);
    }

    public function get(string $id): mixed
    {
        return $this->resolve($id);
    }

    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }

    private function resolve(string $abstract, array $parameters = []): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->resolving[$abstract])) {
            throw new ContainerException("Circular dependency detected while resolving [{$abstract}].");
        }
        $this->resolving[$abstract] = true;

        try {
            $binding = $this->bindings[$abstract] ?? null;
            $concrete = $binding['concrete'] ?? $abstract;

            if ($concrete instanceof Closure) {
                $object = $concrete($this, $parameters);
            } elseif (is_string($concrete) && $concrete !== $abstract && $this->isResolvableId($concrete)) {
                $object = $this->resolve($concrete, $parameters);
            } else {
                $object = $this->build(is_string($concrete) ? $concrete : $abstract, $parameters);
            }

            if ($binding !== null && $binding['shared']) {
                $this->instances[$abstract] = $object;
            }

            return $object;
        } finally {
            unset($this->resolving[$abstract]);
        }
    }

    private function isResolvableId(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->bindings[$id]) || class_exists($id) || interface_exists($id);
    }

    private function build(string $class, array $parameters = []): mixed
    {
        if (!class_exists($class)) {
            throw new NotFoundException("No binding found and class [{$class}] does not exist.");
        }

        $reflector = new ReflectionClass($class);
        if (!$reflector->isInstantiable()) {
            throw new ContainerException("Class [{$class}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();
        if ($constructor === null) {
            return new $class();
        }

        $dependencies = array_map(
            fn (ReflectionParameter $param) => $this->resolveParameter($param, $parameters),
            $constructor->getParameters()
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    private function resolveParameter(ReflectionParameter $param, array $parameters): mixed
    {
        $name = $param->getName();
        if (array_key_exists($name, $parameters)) {
            return $parameters[$name];
        }

        $type = $param->getType();
        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            try {
                return $this->resolve($type->getName());
            } catch (ContainerException $e) {
                if ($param->isDefaultValueAvailable()) {
                    return $param->getDefaultValue();
                }
                if ($type->allowsNull()) {
                    return null;
                }
                throw $e;
            }
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        throw new ContainerException("Unresolvable dependency [\${$name}] in [{$param->getDeclaringClass()?->getName()}].");
    }

    public function call(callable|string|array $callable, array $parameters = []): mixed
    {
        if (is_array($callable)) {
            [$target, $method] = $callable;
            $target = is_string($target) ? $this->resolve($target) : $target;
            $reflection = new \ReflectionMethod($target, $method);
            $instance = $target;
        } else {
            $closure = Closure::fromCallable($callable);
            $reflection = new \ReflectionFunction($closure);
            $instance = null;
        }

        $args = array_map(
            fn (ReflectionParameter $param) => $this->coerceScalar($param, $this->resolveParameter($param, $parameters)),
            $reflection->getParameters()
        );

        return $reflection instanceof \ReflectionFunction
            ? $reflection->invokeArgs($args)
            : $reflection->invokeArgs($instance, $args);
    }

    private function coerceScalar(ReflectionParameter $param, mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $type = $param->getType();
        if (!$type instanceof ReflectionNamedType || !$type->isBuiltin()) {
            return $value;
        }

        return match ($type->getName()) {
            'int' => preg_match('/^-?\d+$/', $value) === 1 ? (int) $value : $value,
            'float' => is_numeric($value) ? (float) $value : $value,
            'bool' => in_array(strtolower($value), ['true', 'false'], true) ? strtolower($value) === 'true' : $value,
            default => $value,
        };
    }

    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
    }
}
