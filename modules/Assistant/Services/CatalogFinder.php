<?php

declare(strict_types=1);

namespace Modules\Assistant\Services;

use Modules\Products\Models\Product;

/**
 * "Something like this" — searched inside our own catalogue.
 *
 * This is the whole finder: no model, no tokens, no network. A description is
 * cut into words, each word is scored against what a product actually carries
 * (its code, its translated name, its short description, the search keywords
 * the shop already maintains, its technical values), and the products that
 * answer to the most words come back as cards.
 *
 * One query per word, portable across MySQL and SQLite: no FULLTEXT, no
 * collation trick, only LOWER() and LIKE — so the same code answers the same
 * way on XAMPP and in the test harness.
 */
final class CatalogFinder
{
    /** Weights. A model code is the surest hit, a spec value the softest. */
    private const W_CODE = 10;
    private const W_KEYWORD = 9;
    private const W_NAME = 8;
    private const W_SHORT = 4;
    private const W_KEYWORD_PART = 3;
    private const W_SPEC = 2;

    /** Words too common to mean anything in a search. */
    private const STOPWORDS = [
        // English
        'the', 'and', 'for', 'with', 'from', 'that', 'this', 'looking', 'look', 'like', 'want',
        'need', 'please', 'some', 'any', 'have', 'has', 'you', 'your', 'our', 'are', 'was',
        'can', 'could', 'would', 'about', 'into', 'over', 'very', 'just', 'not', 'but', 'all',
        'item', 'product', 'products', 'something', 'similar', 'same', 'kind', 'type', 'price',
        'buy', 'shop', 'store', 'find', 'search', 'show', 'give', 'make', 'made', 'new',
        // Turkish
        'bir', 've', 'ile', 'için', 'icin', 'olan', 'olarak', 'gibi', 'daha', 'çok', 'cok',
        'ama', 'veya', 'her', 'bu', 'şu', 'su', 'ben', 'bana', 'benim', 'istiyorum', 'arıyorum',
        'ariyorum', 'lazım', 'lazim', 'var', 'yok', 'ürün', 'urun', 'fiyat', 'kaç', 'kac',
        // Czech
        'pro', 'nebo', 'kde', 'jak', 'jaky', 'jaký', 'ktery', 'který', 'ktera', 'která',
        'hledam', 'hledám', 'chci', 'potrebuji', 'potřebuji', 'mam', 'mám', 'mate', 'máte',
        'nejaky', 'nějaký', 'tento', 'tato', 'tohle', 'neni', 'není', 'dobre', 'dobrý',
        'cena', 'cenu', 'produkt', 'vyrobek', 'výrobek', 'velikost',
        // Arabic
        'من', 'في', 'على', 'عن', 'مع', 'هذا', 'هذه', 'ذلك', 'التي', 'الذي', 'انا', 'أنا',
        'عايز', 'عاوز', 'محتاج', 'ممكن', 'فيه', 'عندكم', 'عندك', 'لو', 'او', 'أو', 'و',
        'سعر', 'بكم', 'كام', 'حاجة', 'شيء', 'زي', 'يشبه', 'شبه', 'نفس', 'اريد', 'أريد',
    ];

    /** Letters we fold away, so "umyvadlo" finds "Umývadlo" on SQLite too. */
    private const FOLD = [
        'á' => 'a', 'ä' => 'a', 'â' => 'a', 'à' => 'a', 'å' => 'a', 'ã' => 'a',
        'č' => 'c', 'ć' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e', 'è' => 'e', 'ê' => 'e',
        'ë' => 'e', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ı' => 'i', 'ł' => 'l',
        'ň' => 'n', 'ñ' => 'n', 'ó' => 'o', 'ö' => 'o', 'ô' => 'o', 'ò' => 'o', 'õ' => 'o',
        'ř' => 'r', 'ŕ' => 'r', 'š' => 's', 'ş' => 's', 'ť' => 't', 'ú' => 'u', 'ů' => 'u',
        'ü' => 'u', 'û' => 'u', 'ù' => 'u', 'ý' => 'y', 'ž' => 'z', 'ğ' => 'g', 'ç' => 'c',
    ];

    /** folded word => ['n' => how many products use it, 'word' => how the shop spells it] */
    private ?array $index = null;

    /**
     * The products that answer to these words, best first.
     *
     * @param array<int, string> $terms
     * @return array<int, array{id:int,name:string,code:string,url:string,image:string,cat:string,size:string,score:int,hits:array<int,string>}>
     */
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

        /* one query per word; a product answering two words scores both.
           Each word is asked for in the spelling the catalogue itself uses, so
           a folded "dus" still finds "Duş" and "umyvadlo" finds "Umývadlo". */
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

        /* the category the visitor named outweighs any single word */
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

    /** Products of the same family as this one — the "more like this" chip. */
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

        /* the piece the visitor is looking at never appears in its own list */
        return array_values(array_filter($cards, static fn (array $card): bool => $card['id'] !== $productId)) ?: array_slice($cards, 1);
    }

    /**
     * The words worth searching for, from a sentence in any language — the
     * offline half of the finder (when the model is away, or not needed).
     *
     * @return array<int, string>
     */
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

            /* keep short model codes (1654, 111) but drop noise like "cm" */
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

        /* words the catalogue actually knows come first — a Czech "umyvadlo"
           or a Turkish "duş" is worth more than a word we invented */
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

    /**
     * Every word the shop already uses for its own products (search keywords,
     * translated names, category names). A few thousand short strings, read
     * once per request: it is what decides which of the visitor's words is
     * worth searching for, and how the catalogue spells it.
     *
     * @return array<string, int> folded word => how many products use it
     */
    public function vocabulary(): array
    {
        $out = [];

        foreach ($this->index() as $word => $entry) {
            $out[$word] = (int) $entry['n'];
        }

        return $out;
    }

    /** @return array<string, array{n:int, word:string}> */
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

        /* the product names are where the Turkish and Czech spellings live */
        foreach ($connection->select('SELECT name FROM product_translations LIMIT 4000') as $row) {
            $this->remember($out, (string) ($row['name'] ?? ''));
        }

        foreach ($connection->select('SELECT slug FROM categories WHERE deleted_at IS NULL') as $row) {
            $this->remember($out, (string) ($row['slug'] ?? ''));
        }

        return $this->index = $out;
    }

    /** @param array<string, array{n:int, word:string}> $index */
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

            /* a spelling that carries diacritics is the shop's own: keep it */
            if ($spelling !== $key) {
                $index[$key]['word'] = $spelling;
            }
        }
    }

    /**
     * The category the visitor named, as a slug — "shower" finds `shower-sets`,
     * "mixer" finds `washbasin-mixers`, and the product's own category too.
     */
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

    /** @return array<string, string> slug => translated name */
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

    /** A few pieces to show when nothing matched: featured first, then newest. */
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

    /** One card, the shape the chat renders and the shape the mail repeats. */
    public function card(Product $product, string $locale): array
    {
        $item = $product->translate($locale);
        $image = (string) ($item['image'] ?? '');

        return [
            'id' => (int) $product->getKey(),
            'name' => (string) ($item['name'] ?? ''),
            'code' => (string) ($item['sku'] ?? $item['model_code'] ?? ''),
            'url' => route('products.show', ['slug' => (string) $product->slug]),
            /* the chat renders these cards itself, so the picture is a full URL */
            'image' => asset($image !== '' ? $image : '/images/products/prod_146_1620-111-a.jpg'),
            'cat' => (string) ($item['category_name'] ?? ''),
            'size' => $this->size($item),
        ];
    }

    /* ------------------------------------------------------------------ internals */

    /** @return array<int, array<string, mixed>> raw rows, best score first */
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

    /** The one line under the name in a card: a size, if the shop entered one. */
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

    /**
     * @param array<int, string> $terms
     * @return array<int, string>
     */
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

    /** @return array<int, string> */
    private function words(string $text): array
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = strtr($text, self::FOLD);
        $text = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);

        return array_values(array_filter(preg_split('/\s+/u', $text) ?: [], static fn (string $word): bool => $word !== ''));
    }

    /** The raw words of a text, spelling intact — used to remember how the shop writes them. */
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

    /**
     * The catalogue's own spelling of a folded word — the diacritics the
     * visitor's keyboard did not carry. "dus" becomes "duş" because that is how
     * the shop writes it; an unknown word is searched exactly as it came.
     */
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

    /** Crude singular: "mixers" and "mixer" are the same search. */
    private function stem(string $word): string
    {
        return mb_strlen($word) > 4 ? rtrim($word, 's') : $word;
    }
}
