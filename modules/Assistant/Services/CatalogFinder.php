<?php

declare(strict_types=1);

namespace Modules\Assistant\Services;

use Modules\Products\Models\Product;

final class CatalogFinder
{
    private const W_CODE = 10;
    private const W_KEYWORD = 9;
    private const W_NAME = 8;
    private const W_SHORT = 4;
    private const W_KEYWORD_PART = 3;
    private const W_SPEC = 2;

    private const STOPWORDS = [

        'the', 'and', 'for', 'with', 'from', 'that', 'this', 'looking', 'look', 'like', 'want',
        'need', 'please', 'some', 'any', 'have', 'has', 'you', 'your', 'our', 'are', 'was',
        'can', 'could', 'would', 'about', 'into', 'over', 'very', 'just', 'not', 'but', 'all',
        'item', 'product', 'products', 'something', 'similar', 'same', 'kind', 'type', 'price',
        'buy', 'shop', 'store', 'find', 'search', 'show', 'give', 'make', 'made', 'new',

        'bir', 've', 'ile', 'için', 'icin', 'olan', 'olarak', 'gibi', 'daha', 'çok', 'cok',
        'ama', 'veya', 'her', 'bu', 'şu', 'su', 'ben', 'bana', 'benim', 'istiyorum', 'arıyorum',
        'ariyorum', 'lazım', 'lazim', 'var', 'yok', 'ürün', 'urun', 'fiyat', 'kaç', 'kac',

        'pro', 'nebo', 'kde', 'jak', 'jaky', 'jaký', 'ktery', 'který', 'ktera', 'která',
        'hledam', 'hledám', 'chci', 'potrebuji', 'potřebuji', 'mam', 'mám', 'mate', 'máte',
        'nejaky', 'nějaký', 'tento', 'tato', 'tohle', 'neni', 'není', 'dobre', 'dobrý',
        'cena', 'cenu', 'produkt', 'vyrobek', 'výrobek', 'velikost',

        'من', 'في', 'على', 'عن', 'مع', 'هذا', 'هذه', 'ذلك', 'التي', 'الذي', 'انا', 'أنا',
        'عايز', 'عاوز', 'محتاج', 'ممكن', 'فيه', 'عندكم', 'عندك', 'لو', 'او', 'أو', 'و',
        'سعر', 'بكم', 'كام', 'حاجة', 'شيء', 'زي', 'يشبه', 'شبه', 'نفس', 'اريد', 'أريد',
    ];

    private const FOLD = [
        'á' => 'a', 'ä' => 'a', 'â' => 'a', 'à' => 'a', 'å' => 'a', 'ã' => 'a',
        'č' => 'c', 'ć' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e', 'è' => 'e', 'ê' => 'e',
        'ë' => 'e', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ı' => 'i', 'ł' => 'l',
        'ň' => 'n', 'ñ' => 'n', 'ó' => 'o', 'ö' => 'o', 'ô' => 'o', 'ò' => 'o', 'õ' => 'o',
        'ř' => 'r', 'ŕ' => 'r', 'š' => 's', 'ş' => 's', 'ť' => 't', 'ú' => 'u', 'ů' => 'u',
        'ü' => 'u', 'û' => 'u', 'ù' => 'u', 'ý' => 'y', 'ž' => 'z', 'ğ' => 'g', 'ç' => 'c',
    ];

    private ?array $index = null;

    public function find(array $terms, string $locale, ?int $limit = null, string $category = ''): array
    {
        $terms = $this->clean($terms);
        $limit = $limit ?? (int) config('assistant.find.limit', 6);

        if ($terms === []) {
            return [];
        }

        $scanned = (int) config('assistant.find.scanned', 24);
        $categoryId = $category !== '' ? $this->categoryId($category) : 0;
        $table = [];

        foreach (array_slice($terms, 0, 6) as $term) {
            foreach ($this->scan($this->spelling($term), $scanned) as $row) {
                $score = (int) ($row['match_score'] ?? 0);

                if ($score <= 0) {
                    continue;
                }

                $id = (int) ($row['id'] ?? 0);

                if ($id === 0) {
                    continue;
                }

                if (!isset($table[$id])) {
                    $table[$id] = ['row' => $row, 'score' => 0, 'hits' => []];
                }

                $table[$id]['score'] += $score;
                $table[$id]['hits'][] = $term;
            }
        }

        if ($categoryId > 0) {
            foreach ($table as $id => $entry) {
                if ((int) ($entry['row']['category_id'] ?? 0) === $categoryId) {
                    $table[$id]['score'] += 12;
                }
            }
        }

        uasort($table, static function (array $a, array $b): int {
            return [$b['score'], count($b['hits']), (int) ($b['row']['is_featured'] ?? 0)]
                <=> [$a['score'], count($a['hits']), (int) ($a['row']['is_featured'] ?? 0)];
        });

        $table = array_slice($table, 0, max(1, $limit * 2));

        $models = [];

        foreach ($table as $id => $entry) {
            $row = $entry['row'];
            unset($row['match_score']);
            $models[$id] = Product::fromRow($row);
        }

        if ($models === []) {
            return [];
        }

        Product::eagerLoad(array_values($models));

        $cards = [];

        foreach ($table as $id => $entry) {
            if (!isset($models[$id])) {
                continue;
            }

            $card = $this->card($models[$id], $locale);
            $card['score'] = (int) $entry['score'];
            $card['hits'] = array_values(array_unique($entry['hits']));
            $cards[] = $card;

            if (count($cards) >= $limit) {
                break;
            }
        }

        return $cards;
    }

    public function similarTo(int $productId, string $locale, ?int $limit = null): array
    {
        $limit = $limit ?? (int) config('assistant.find.limit', 6);

        $rows = Product::query()->connection()->select(
            'SELECT * FROM products WHERE id = ? AND deleted_at IS NULL LIMIT 1',
            [$productId],
        );

        if ($rows === []) {
            return [];
        }

        $product = Product::fromRow($rows[0]);
        $item = $product->translate($locale);

        $terms = array_merge(
            $this->terms((string) ($item['name'] ?? ''), $locale),
            $this->terms((string) ($item['short_description'] ?? ''), $locale),
        );

        $cards = $this->find($terms, $locale, $limit + 1, $this->categorySlugOf($productId));

        return array_values(array_filter($cards, static fn (array $card): bool => $card['id'] !== $productId)) ?: array_slice($cards, 1);
    }

    public function terms(string $text, string $locale = 'en'): array
    {
        $max = (int) config('assistant.find.terms', 8);
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = strtr($text, self::FOLD);
        $text = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);

        $out = [];

        foreach (preg_split('/\s+/u', $text) ?: [] as $word) {
            $word = trim($word);

            if ($word === '' || in_array($word, self::STOPWORDS, true)) {
                continue;
            }

            if (mb_strlen($word) < 3 && !ctype_digit($word)) {
                continue;
            }

            if (in_array($word, $out, true)) {
                continue;
            }

            $out[] = $word;
        }

        if ($out === []) {
            return [];
        }

        $vocabulary = $this->vocabulary();

        if ($vocabulary !== []) {
            $known = [];
            $unknown = [];

            foreach ($out as $word) {
                if (isset($vocabulary[$word]) || isset($vocabulary[$this->stem($word)])) {
                    $known[] = $word;
                } else {
                    $unknown[] = $word;
                }
            }

            $out = array_merge($known, $unknown);
        }

        return array_slice($out, 0, max(1, $max));
    }

    public function vocabulary(): array
    {
        $out = [];

        foreach ($this->index() as $word => $entry) {
            $out[$word] = (int) $entry['n'];
        }

        return $out;
    }

    private function index(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }

        $connection = Product::query()->connection();
        $out = [];

        foreach ($connection->select('SELECT keyword FROM product_search_keywords LIMIT 4000') as $row) {
            $this->remember($out, (string) ($row['keyword'] ?? ''));
        }

        foreach ($connection->select('SELECT name FROM product_translations LIMIT 4000') as $row) {
            $this->remember($out, (string) ($row['name'] ?? ''));
        }

        foreach ($connection->select('SELECT slug FROM categories WHERE deleted_at IS NULL') as $row) {
            $this->remember($out, (string) ($row['slug'] ?? ''));
        }

        return $this->index = $out;
    }

    private function remember(array &$index, string $text): void
    {
        $raw = $this->rawWords($text);

        foreach ($raw as $position => $spelling) {
            $key = $this->fold($spelling);

            if ($key === '') {
                continue;
            }

            if (!isset($index[$key])) {
                $index[$key] = ['n' => 0, 'word' => $key];
            }

            $index[$key]['n']++;

            if ($spelling !== $key) {
                $index[$key]['word'] = $spelling;
            }
        }
    }

    public function categorySlug(string $text): string
    {
        $words = $this->words($text);

        if ($words === []) {
            return '';
        }

        $best = '';
        $bestScore = 0;

        foreach ($this->categories() as $slug => $name) {
            $score = 0;

            foreach ($this->words($slug . ' ' . $name) as $word) {
                if ($word !== '' && in_array($word, $words, true)) {
                    $score++;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $slug;
            }
        }

        return $bestScore > 0 ? $best : '';
    }

    public function categories(string $locale = 'en'): array
    {
        $rows = Product::query()->connection()->select(
            'SELECT c.slug, ct.name FROM categories c
             LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.locale = ?
             WHERE c.deleted_at IS NULL ORDER BY c.sort_order ASC, c.id ASC',
            [$locale],
        );

        $out = [];

        foreach ($rows as $row) {
            $slug = (string) ($row['slug'] ?? '');

            if ($slug !== '') {
                $out[$slug] = (string) ($row['name'] ?? $slug);
            }
        }

        return $out;
    }

    public function featured(string $locale, ?int $limit = null, string $category = ''): array
    {
        $limit = $limit ?? (int) config('assistant.find.limit', 6);
        $params = ['active'];
        $where = "p.deleted_at IS NULL AND p.status = ?";
        $categoryId = $category !== '' ? $this->categoryId($category) : 0;

        if ($categoryId > 0) {
            $where .= ' AND p.category_id = ?';
            $params[] = $categoryId;
        }

        $params[] = $limit;

        $rows = Product::query()->connection()->select(
            "SELECT p.* FROM products p WHERE {$where}
             ORDER BY p.is_featured DESC, p.sort_order ASC, p.id DESC LIMIT ?",
            $params,
        );

        $models = [];

        foreach ($rows as $row) {
            $models[(int) $row['id']] = Product::fromRow($row);
        }

        if ($models === []) {
            return [];
        }

        Product::eagerLoad(array_values($models));

        return array_map(fn (Product $product): array => $this->card($product, $locale), array_values($models));
    }

    public function card(Product $product, string $locale): array
    {
        $item = $product->translate($locale);
        $image = (string) ($item['image'] ?? '');

        return [
            'id' => (int) $product->getKey(),
            'name' => (string) ($item['name'] ?? ''),
            'code' => (string) ($item['sku'] ?? $item['model_code'] ?? ''),
            'url' => route('products.show', ['slug' => (string) $product->slug]),
            'image' => asset($image !== '' ? $image : '/images/products/prod_146_1620-111-a.jpg'),
            'cat' => (string) ($item['category_name'] ?? ''),
            'size' => $this->size($item),
        ];
    }

    private function scan(string $term, int $limit): array
    {
        $like = '%' . $term . '%';

        return Product::query()->connection()->select(
            "SELECT p.*, (
                (CASE WHEN LOWER(COALESCE(p.model_code, '')) LIKE ? THEN " . self::W_CODE . " ELSE 0 END)
              + (CASE WHEN EXISTS (
                    SELECT 1 FROM product_translations pt
                    WHERE pt.product_id = p.id AND LOWER(pt.name) LIKE ?
                 ) THEN " . self::W_NAME . " ELSE 0 END)
              + (CASE WHEN EXISTS (
                    SELECT 1 FROM product_translations pt
                    WHERE pt.product_id = p.id AND LOWER(COALESCE(pt.short_description, '')) LIKE ?
                 ) THEN " . self::W_SHORT . " ELSE 0 END)
              + (CASE WHEN EXISTS (
                    SELECT 1 FROM product_search_keywords k
                    WHERE k.product_id = p.id AND LOWER(k.keyword) = ?
                 ) THEN " . self::W_KEYWORD . " ELSE 0 END)
              + (CASE WHEN EXISTS (
                    SELECT 1 FROM product_search_keywords k
                    WHERE k.product_id = p.id AND LOWER(k.keyword) LIKE ?
                 ) THEN " . self::W_KEYWORD_PART . " ELSE 0 END)
              + (CASE WHEN EXISTS (
                    SELECT 1 FROM product_specifications s
                    WHERE s.product_id = p.id AND (LOWER(s.spec_value) LIKE ? OR LOWER(s.spec_key) LIKE ?)
                 ) THEN " . self::W_SPEC . " ELSE 0 END)
            ) AS match_score
            FROM products p
            WHERE p.deleted_at IS NULL AND p.status = 'active'
            ORDER BY match_score DESC, p.is_featured DESC, p.sort_order ASC, p.id DESC
            LIMIT ?",
            [$like, $like, $like, $term, $like, $like, $like, $limit],
        );
    }

    private function categoryId(string $slug): int
    {
        $rows = Product::query()->connection()->select(
            'SELECT id FROM categories WHERE slug = ? AND deleted_at IS NULL LIMIT 1',
            [$slug],
        );

        return (int) ($rows[0]['id'] ?? 0);
    }

    private function categorySlugOf(int $productId): string
    {
        $rows = Product::query()->connection()->select(
            'SELECT c.slug FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ? LIMIT 1',
            [$productId],
        );

        return (string) ($rows[0]['slug'] ?? '');
    }

    private function size(array $item): string
    {
        foreach ((array) ($item['specs'] ?? []) as $spec) {
            $key = mb_strtolower((string) ($spec['key'] ?? $spec['spec_key'] ?? ''));

            if (str_contains($key, 'size') || str_contains($key, 'dimension') || str_contains($key, 'boyut')) {
                return trim((string) ($spec['value'] ?? $spec['spec_value'] ?? ''));
            }
        }

        return '';
    }

    private function clean(array $terms): array
    {
        $out = [];

        foreach ($terms as $term) {
            foreach ($this->words((string) $term) as $word) {
                if ($word !== '' && !in_array($word, $out, true)) {
                    $out[] = $word;
                }
            }
        }

        return $out;
    }

    private function words(string $text): array
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = strtr($text, self::FOLD);
        $text = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);

        return array_values(array_filter(preg_split('/\s+/u', $text) ?: [], static fn (string $word): bool => $word !== ''));
    }

    private function rawWords(string $text): array
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);

        return array_values(array_filter(preg_split('/\s+/u', $text) ?: [], static fn (string $word): bool => $word !== ''));
    }

    private function fold(string $word): string
    {
        return strtr($word, self::FOLD);
    }

    private function spelling(string $word): string
    {
        $index = $this->index();

        foreach ([$word, $this->stem($word)] as $key) {
            if (isset($index[$key]['word'])) {
                return (string) $index[$key]['word'];
            }
        }

        return $word;
    }

    private function stem(string $word): string
    {
        return mb_strlen($word) > 4 ? rtrim($word, 's') : $word;
    }
}
