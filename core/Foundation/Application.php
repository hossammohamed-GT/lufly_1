<?php

declare(strict_types=1);

namespace Core\Foundation;

use Core\Container\Container;

class Application extends Container
{
    public const VERSION = '1.0.0';

    private array $providers = [];

    private bool $booted = false;

    public function __construct(private readonly string $basePath)
    {
        $this->instance(self::class, $this);
        $this->instance(Container::class, $this);
        $this->instance(\Core\Contracts\ContainerInterface::class, $this);
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    public function configPath(string $path = ''): string
    {
        return $this->basePath('config' . ($path !== '' ? '/' . $path : ''));
    }

    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage' . ($path !== '' ? '/' . $path : ''));
    }

    public function resourcePath(string $path = ''): string
    {
        return $this->basePath('resources' . ($path !== '' ? '/' . $path : ''));
    }

    public function modulePath(string $path = ''): string
    {
        return $this->basePath('modules' . ($path !== '' ? '/' . $path : ''));
    }

    public function isDebug(): bool
    {
        return filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL);
    }

    public function environment(): string
    {
        return (string) env('APP_ENV', 'production');
    }

    public function registerProviders(): void
    {
        foreach ((array) config('app.providers', []) as $providerClass) {
            $provider = new $providerClass($this);
            $provider->register();
            $this->providers[] = $provider;
        }
    }

    public function bootProviders(): void
    {
        foreach ($this->providers as $provider) {
            $provider->boot();
        }
        $this->booted = true;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }
}
