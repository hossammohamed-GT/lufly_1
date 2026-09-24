<?php

declare(strict_types=1);

namespace Core\View;

use Core\Foundation\Application;
use Core\Localization\Translator;
use Throwable;

class View
{
    private array $namespaces = [];

    private ?string $layout = null;

    private array $shared = [];

    private array $assets = ['styles' => [], 'scripts' => [], 'preloads' => []];

    public function __construct(
        private readonly Application $app,
        private readonly Translator $translator,
    ) {
    }

    public function addNamespace(string $namespace, string $path): void
    {
        $this->namespaces[$namespace] = rtrim($path, '/\\');
    }

    public function share(array $data): void
    {
        $this->shared = array_merge($this->shared, $data);
    }

    public function render(string $template, array $data = []): string
    {
        $this->layout = null;

        $content = $this->renderFile($this->resolvePath($template), $data);

        if ($this->layout !== null) {
            $layoutPath = $this->resolvePath($this->layout);
            $this->layout = null;

            return $this->renderFile($layoutPath, array_merge($data, ['content' => $content]));
        }

        return $content;
    }

    public function layout(string $template): void
    {
        $this->layout = $template;
    }

    public function resolvePath(string $template): string
    {
        if (str_contains($template, '::')) {
            [$namespace, $relative] = explode('::', $template, 2);
            $base = $this->namespaces[$namespace] ?? $this->app->resourcePath('views');

            return $base . '/' . str_replace('.', '/', $relative) . '.php';
        }

        return $this->app->resourcePath('views/' . str_replace('.', '/', $template) . '.php');
    }

    public function renderFile(string $path, array $data = []): string
    {
        if (!is_file($path)) {
            throw new \Core\Exceptions\NotFoundException('View not found: ' . $path);
        }

        $view = $this;
        $t = fn (string $key, array $params = []): string => $this->translator->trans($key, $params);
        extract(array_merge($this->shared, $data), EXTR_SKIP);

        ob_start();
        try {
            include $path;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }

    public function component(string $name, array $props = [], ?string $slot = null): string
    {
        $props['slot'] = $slot;

        return $this->renderFile(
            $this->app->resourcePath('views/components/' . $name . '.php'),
            ['props' => $props] + $props,
        );
    }

    public function pushStyle(string $path): void
    {
        $this->pushAsset('styles', $path);
    }

    public function pushScript(string $path): void
    {
        $this->pushAsset('scripts', $path);
    }

    public function styles(): array
    {
        return $this->assets['styles'];
    }

    public function scripts(): array
    {
        return $this->assets['scripts'];
    }

    public function pushPreload(string $href, array $attributes = ['as' => 'image']): void
    {
        foreach ($this->assets['preloads'] as $preload) {
            if ($preload['href'] === $href) {
                return;
            }
        }

        $this->assets['preloads'][] = ['href' => $href, 'attributes' => $attributes];
    }

    public function preloads(): array
    {
        return $this->assets['preloads'];
    }

    private function pushAsset(string $type, string $path): void
    {
        if (!in_array($path, $this->assets[$type], true)) {
            $this->assets[$type][] = $path;
        }
    }

    public function exists(string $template): bool
    {
        return is_file($this->resolvePath($template));
    }
}
