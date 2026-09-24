<?php

declare(strict_types=1);

namespace Core\Http;

final class Route
{
    public array $middlewares = [];

    public array $wheres = [];

    public array $meta = [];

    public ?string $name = null;

    public string $namePrefix = '';

    public bool $localized = false;

    public string $localizedKey = '';

    public ?string $compiledPattern = null;

    public function __construct(
        public readonly array $methods,
        public readonly string $uri,
        public mixed $handler,
    ) {
    }

    public function name(string $name): self
    {
        $this->name = $this->namePrefix . $name;
        return $this;
    }

    public function middleware(string|array $middlewares): self
    {
        $this->middlewares = array_merge($this->middlewares, (array) $middlewares);
        return $this;
    }

    public function where(string $param, string $regex): self
    {
        $this->wheres[$param] = $regex;
        return $this;
    }

    public function meta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);
        return $this;
    }

    public function doc(string $summary, array $validationRules = [], array $responseExample = []): self
    {
        $this->meta['doc'] = [
            'summary' => $summary,
            'validation' => $validationRules,
            'response' => $responseExample,
        ];

        return $this;
    }
}
