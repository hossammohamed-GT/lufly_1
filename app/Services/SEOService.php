<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Seo\UrlLocalizer;

class SEOService
{
    private array $meta = [];

    private array $alternates = [];

    private array $structured = [];

    public function __construct(private readonly UrlLocalizer $urls)
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

    public function setAlternatesFor(string $routeKey, array $params = []): self
    {
        $this->alternates = $this->urls->alternates($routeKey, $params);
        return $this;
    }

    public function xDefaultUrl(string $routeKey, array $params = []): string
    {
        $default = (string) config('localization.default', 'en');
        return $this->urls->localizedUrl($routeKey, $default, $params);
    }

    public function addBreadcrumb(array $items): self
    {
        $elements = [];
        foreach (array_values($items) as $i => $item) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }
        return $this->addStructuredData('BreadcrumbList', ['itemListElement' => $elements]);
    }

    public function addProductSchema(array $product, string $canonicalUrl): self
    {
        $images = [];
        if (!empty($product['image'])) {
            $images[] = asset((string) $product['image']);
        }
        foreach ((array) ($product['gallery'] ?? []) as $path) {
            $abs = asset((string) $path);
            if (!in_array($abs, $images, true)) {
                $images[] = $abs;
            }
        }

        $description = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($product['short_description'] ?? $product['description'] ?? ''))) ?? '');

        return $this->addStructuredData('Product', [
            '@id' => $canonicalUrl . '#product',
            'name' => (string) ($product['name'] ?? ''),
            'description' => $description !== '' ? mb_substr($description, 0, 500) : (string) ($product['name'] ?? ''),
            'image' => $images,
            'sku' => (string) ($product['sku'] ?? $product['model_code'] ?? ''),
            'mpn' => (string) ($product['model_code'] ?? $product['sku'] ?? ''),
            'url' => $canonicalUrl,
            'category' => (string) ($product['category_slug'] ?? ''),
            'brand' => ['@type' => 'Brand', 'name' => 'LUFLY'],
            'manufacturer' => ['@id' => url('/#organization')],
            'countryOfOrigin' => ['@type' => 'Country', 'name' => 'TR'],
            'offers' => [
                '@type' => 'Offer',
                'url' => $canonicalUrl,
                'priceCurrency' => 'EUR',
                'price' => (float) ($product['price'] ?? 0),
                'availability' => 'https://schema.org/' . (($product['stock_status'] ?? 'in_stock') === 'out_of_stock' ? 'OutOfStock' : 'InStock'),
            ],
        ]);
    }

    public function addItemListSchema(array $items, string $name): self
    {
        $elements = [];
        foreach (array_values($items) as $i => $item) {
            $entry = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'url' => $item['url'],
                'name' => $item['name'],
            ];
            if (!empty($item['image'])) {
                $entry['image'] = $item['image'];
            }
            $elements[] = $entry;
        }
        return $this->addStructuredData('ItemList', ['name' => $name, 'itemListElement' => $elements]);
    }

    public function addFaqSchema(array $faqs): self
    {
        $entities = [];
        foreach ($faqs as $faq) {
            $entities[] = [
                '@type' => 'Question',
                'name' => $faq['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
            ];
        }
        return $this->addStructuredData('FAQPage', ['mainEntity' => $entities]);
    }

    public function addWebPageSchema(array $data): self
    {
        $type = (string) ($data['type'] ?? 'WebPage');
        unset($data['type']);
        return $this->addStructuredData($type, $data + [
            'isPartOf' => ['@id' => url('/#website')],
            'about' => ['@id' => url('/#organization')],
        ]);
    }

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

    public function addStructuredData(string $type, array $data): self
    {
        $this->structured[] = ['type' => $type, 'data' => $data];
        return $this;
    }

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

    public function alternates(): array
    {
        return $this->alternates;
    }

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
