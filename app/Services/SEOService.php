<?php

declare(strict_types=1);

namespace App\Services;

class SEOService
{
    /** @var array<string, mixed> */
    private array $meta = [];

    /** @var array<string, string> */
    private array $alternates = [];

    /** @var array<int, array{type: string, data: array<string, mixed>}> */
    private array $structured = [];

    public function __construct()
    {
        $this->meta = (array) config('seo.defaults', []);
        $this->meta['type'] = 'website';
        $this->meta['robots'] = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    }

    public function setTitle(string $title): self
    {
        $this->meta['title'] = $title;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->meta['description'] = $description;
        return $this;
    }

    /** @param string[]|string $keywords */
    public function setKeywords(array|string $keywords): self
    {
        $this->meta['keywords'] = is_array($keywords) ? $keywords : array_map('trim', explode(',', $keywords));
        return $this;
    }

    public function setCanonical(string $url): self
    {
        $this->meta['canonical'] = $url;
        return $this;
    }

    public function setImage(string $url): self
    {
        $this->meta['image'] = $url;
        return $this;
    }

    public function setType(string $type): self
    {
        $this->meta['type'] = $type;
        return $this;
    }

    public function setRobots(string $robots): self
    {
        $this->meta['robots'] = $robots;
        return $this;
    }

    public function setLocale(string $locale): self
    {
        $this->meta['locale'] = $locale;
        return $this;
    }

    public function addAlternate(string $lang, string $url): self
    {
        $this->alternates[$lang] = $url;
        return $this;
    }

    /** @param array<string, mixed> $entity entity data merged into structured data */
    public function setFromEntity(array $entity): self
    {
        if (isset($entity['seo']) && is_array($entity['seo'])) {
            foreach ($entity['seo'] as $key => $value) {
                if ($value !== null && $value !== '') {
                    $this->meta[$key] = $value;
                }
            }
        }
        if (isset($entity['title']) && !empty($entity['title'])) {
            $this->setTitle((string) $entity['title']);
        }
        if (isset($entity['name']) && !empty($entity['name']) && empty($entity['title'])) {
            $this->setTitle((string) $entity['name']);
        }
        if (isset($entity['description']) && !empty($entity['description'])) {
            $this->setDescription(strip_tags((string) $entity['description']));
        }
        if (isset($entity['short_description']) && !empty($entity['short_description'])) {
            $this->setDescription(strip_tags((string) $entity['short_description']));
        }
        if (isset($entity['image']) && !empty($entity['image'])) {
            $this->setImage(asset((string) $entity['image']));
        }

        return $this;
    }

    /** @param array<string, mixed> $data */
    public function addStructuredData(string $type, array $data): self
    {
        $this->structured[] = ['type' => $type, 'data' => $data];
        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $meta = $this->meta;
        $title = (string) ($meta['title'] ?? '');
        $suffix = (string) config('seo.title_suffix', ' | LUFLY');

        if ($title !== '' && !str_contains($title, 'LUFLY')) {
            $meta['title'] = $title . $suffix;
        }

        return $meta;
    }

    /** @return array<string, string> */
    public function alternates(): array
    {
        return $this->alternates;
    }

    /** @return array<int, array{type: string, data: array<string, mixed>}> */
    public function structuredData(): array
    {
        return $this->structured;
    }

    public function reset(): void
    {
        $this->meta = (array) config('seo.defaults', []);
        $this->meta['type'] = 'website';
        $this->meta['robots'] = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
        $this->alternates = [];
        $this->structured = [];
    }
}
