<?php

declare(strict_types=1);

namespace Modules\Planner\Services;

use App\Services\Ai\AiCache;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiGuard;
use Modules\Products\Repositories\ProductRepository;

/**
 * Turns three taps into a bathroom plan.
 *
 * The flow is deliberately short and the assistant never has to guess:
 *   1. size     — a compact / standard / family room, or the visitor's own numbers
 *   2. wet area — shower or bathtub
 *   3. look     — one tap switches both the style and the metal finish
 *
 * From those answers the plan itself is computed locally (items, sizes,
 * placement, drawing), and the AI is only asked to write the friendly wording
 * around a plan that already exists. If the AI is off, slow or out of quota the
 * visitor still gets the complete plan, in every language.
 */
class PlannerService
{
    public function __construct(
        private readonly AiClient $ai,
        private readonly AiCache $cache,
        private readonly AiGuard $guard,
        private readonly ProductRepository $products,
        private readonly PlanDrawing $drawing,
    ) {
    }

    public function enabled(): bool
    {
        return (bool) ($this->config()['enabled'] ?? true);
    }

    /* ---------------------------------------------------------------------
     * answers
     * ------------------------------------------------------------------- */

    /**
     * The answers so far. Anything unknown or malformed falls back to the
     * default, so a visitor can never reach a broken state.
     */
    public function answers(array $input): array
    {
        $config = $this->config();
        $sizes = $config['sizes'];
        $looks = $config['looks'];

        $size = (string) ($input['size'] ?? '');
        if ($size !== 'custom' && !isset($sizes[$size])) {
            $size = 'standard';
        }

        $wet = (string) ($input['wet'] ?? '');
        if (!in_array($wet, ['shower', 'bath'], true)) {
            $wet = 'shower';
        }

        $look = (string) ($input['look'] ?? '');
        if (!isset($looks[$look])) {
            $look = 'modern-chrome';
        }

        $custom = [
            'w' => $this->clamp($input['w'] ?? 0, (int) $config['custom']['min'], (int) $config['custom']['max']),
            'l' => $this->clamp($input['l'] ?? 0, (int) $config['custom']['min'], (int) $config['custom']['max']),
        ];

        $room = $size === 'custom'
            ? ['w' => $custom['w'], 'l' => $custom['l']]
            : ['w' => (int) $sizes[$size]['w'], 'l' => (int) $sizes[$size]['l']];

        /* the door sits on the door wall; a window only fits a deeper room */
        $room['door'] = 'bottom';
        $room['window'] = $room['l'] >= 220 ? 'right' : '';

        return [
            'size' => $size,
            'wet' => $wet,
            'look' => $look,
            'style' => (string) $looks[$look]['style'],
            'finish' => (string) $looks[$look]['finish'],
            'custom' => $custom,
            'room' => $room,
            'area' => round(($room['w'] / 100) * ($room['l'] / 100), 1),
        ];
    }

    /**
     * Which step the chat shows next.
     *
     * @param array<int, string> $answered steps already submitted
     */
    public function step(array $answers, array $answered = []): string
    {
        if (!in_array('size', $answered, true)) {
            return 'size';
        }

        if ($answers['size'] === 'custom' && !in_array('custom', $answered, true)) {
            return 'custom';
        }

        if (!in_array('wet', $answered, true)) {
            return 'wet';
        }

        if (!in_array('look', $answered, true)) {
            return 'look';
        }

        return 'plan';
    }

    /* ---------------------------------------------------------------------
     * the plan itself — local, deterministic, cached
     * ------------------------------------------------------------------- */

    /**
     * @return array<string, mixed>
     */
    public function plan(array $answers, string $locale = 'en'): array
    {
        return $this->cache->remember(
            'planner.plan.' . $locale,
            $this->fingerprint($answers),
            fn (): array => $this->build($answers, $locale),
            (int) ($this->config()['cache_hours'] ?? 720),
        );
    }

    /**
     * The plan without any AI in it — used for the instant answer, for the PDF
     * sheets and as the fallback whenever the assistant is unavailable.
     *
     * @return array<string, mixed>
     */
    public function build(array $answers, string $locale = 'en'): array
    {
        $blocks = $this->layout($answers);
        $items = [];
        $number = 0;

        foreach ($blocks as $block) {
            $number++;
            $slot = $this->config()['slots'][$block['key']] ?? [];
            $items[] = [
                'n' => $number,
                'key' => $block['key'],
                'label' => $this->itemName($block['key'], $locale),
                'size' => (string) ($slot['size'] ?? ''),
                'zone' => $this->zoneName($block['zone'], $locale),
                'picks' => $this->picks($slot, (string) $answers['finish'], $locale),
            ];
        }

        $room = $answers['room'];
        $room['door_note'] = $this->doorNote($locale);
        $room['window_note'] = $room['window'] !== '' ? $this->windowNote($locale) : '';

        $clearance = $this->clearance($blocks, (int) $room['l']);

        return [
            'answers' => $answers,
            'items' => $items,
            'room' => $room,
            'clearance' => $clearance,
            'clearance_note' => $this->clearanceNote($clearance, $locale),
            'drawing' => $this->drawing->room($room, $blocks, $this->title($locale)),
            'plan_text' => $this->planText($answers, $items, $clearance, $locale),
            'summary' => $this->summary($answers, $items, $locale),
        ];
    }

    /**
     * Free floor left between the door wall and whatever stands against it: the
     * one measurement a visitor should not have to guess. A plan that leaves a
     * metre and more is comfortable; anything less gets a gentle warning.
     *
     * @param array<int, array<string, mixed>> $blocks
     */
    public function clearance(array $blocks, int $length): int
    {
        /* an 80 cm door needs 80 cm of free floor in front of it: whatever is
           closer to the door wall than that is in the way of the swing */
        $nearest = min(array_map(static fn (array $block): int => (int) $block['y'], $blocks) ?: [$length]);

        return $nearest - 80;
    }

    /**
     * The plain-text plan: what gets cached, mailed and handed to the support
     * team when the visitor asks us to source a similar item.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function planText(array $answers, array $items, int $clearance, string $locale = 'en'): string
    {
        $room = $answers['room'];
        $lines = [
            $this->title($locale) . ' · LUFLY',
            sprintf('%s: %d × %d cm (%s m²)', $this->t('room', $locale), $room['w'], $room['l'], $answers['area']),
            $this->t('look', $locale) . ': ' . $this->lookName((string) $answers['look'], $locale),
            $this->t('door', $locale) . ': ' . $this->doorNote($locale),
        ];

        if (($room['window'] ?? '') !== '') {
            $lines[] = $this->t('window', $locale) . ': ' . $this->windowNote($locale);
        }

        $lines[] = '';
        $lines[] = $this->t('items', $locale) . ':';

        foreach ($items as $item) {
            $lines[] = sprintf('%d. %s — %s — %s', $item['n'], $item['label'], $item['size'], $item['zone']);
        }

        $lines[] = '';
        $lines[] = $this->clearanceNote($clearance, $locale);

        return implode("\n", $lines);
    }

    /** The one-line pitch under the chat. */
    private function summary(array $answers, array $items, string $locale): string
    {
        return $this->t('summary', $locale, [
            'count' => (string) count($items),
            'area' => (string) $answers['area'],
            'look' => $this->lookName((string) $answers['look'], $locale),
        ]);
    }

    /* ---------------------------------------------------------------------
     * wording — the one place the AI helps
     * ------------------------------------------------------------------- */

    /**
     * A short, friendly introduction to a plan that is already complete.
     * Never throws; the local wording is used if anything goes wrong.
     *
     * @return array{text: string, source: string}
     */
    public function intro(array $answers, array $plan, string $locale = 'en'): array
    {
        $ai = $this->config()['ai'];
        $fallback = $this->t('intro_local', $locale, [
            'count' => (string) count($plan['items']),
            'area' => (string) $answers['area'],
        ]);

        if (!($ai['enabled'] ?? true) || !$this->ai->enabled()) {
            return ['text' => $fallback, 'source' => 'local'];
        }

        /* the same room is asked about over and over — the wording is cached
           next to the plan, so the second visitor of a size pays no tokens */
        $fingerprint = sha1($locale . '|' . $this->fingerprint($answers));

        return $this->cache->remember(
            'planner.intro.' . $locale,
            $fingerprint,
            fn (): array => $this->writeIntro($answers, $plan, $locale, $fallback),
            (int) ($this->config()['cache_hours'] ?? 720),
        );
    }

    /**
     * One small request to the model: it writes the welcome, never the plan.
     *
     * @return array{text: string, source: string}
     */
    private function writeIntro(array $answers, array $plan, string $locale, string $fallback): array
    {
        $ai = $this->config()['ai'];

        if (!($ai['enabled'] ?? true) || !$this->guard->allows('planner.intro')) {
            return ['text' => $fallback, 'source' => 'local'];
        }

        /* the model sees the answers and two words per item — never the
           catalogue, never the visitor's words, never a long prompt */
        $outline = [];
        foreach ($plan['items'] as $item) {
            $outline[] = $item['label'] . ' (' . $item['size'] . ', ' . $item['zone'] . ')';
        }

        $prompt = implode("\n", [
            'You are the bathroom planner of LUFLY, a sanitary-ware shop.',
            'A visitor answered three quick questions. This plan is already fixed — do not change it, do not add or remove items:',
            'Room ' . $answers['room']['w'] . ' × ' . $answers['room']['l'] . ' cm (' . $answers['area'] . ' m²).',
            'Wet area: ' . $answers['wet'] . '. Look: ' . $this->lookName((string) $answers['look'], 'en') . '.',
            'Items: ' . implode('; ', $outline) . '.',
            $this->doorNote('en') . ', and ' . strtolower($this->clearanceNote((int) ($plan['clearance'] ?? 0), 'en')) . '.',
            '',
            'Write a warm, plain welcome of at most ' . (int) ($ai['max_words'] ?? 140) . ' words in the language with this code: ' . $locale . '.',
            'Rules: no prices, no brand names, no extra fittings, no different sizes, no lists, no markdown, no emoji.',
            'Two short paragraphs at most, and reassure the visitor that nothing blocks the door.',
        ]);

        $result = $this->ai->generate($prompt, [
            'scope' => 'planner.intro',
            'locale' => $locale,
        ]);

        $this->record('planner.intro', $result);

        /* `ok` also tells the cache how long to keep this: a local sentence
           written because the assistant was away is retried in five minutes,
           not remembered for a month */
        if (!($result['ok'] ?? false) || trim((string) ($result['text'] ?? '')) === '') {
            return [
                'text' => $fallback,
                'source' => 'local',
                'ok' => false,
                'error' => $result['error'] ?? null,
            ];
        }

        return [
            'text' => trim((string) $result['text']),
            'source' => 'ai',
            'ok' => true,
            'model' => $result['model'] ?? null,
        ];
    }

    /**
     * "Does this piece fit the plan I just made?" — the only thing the chat
     * says about a single product.
     *
     * Cheapest possible answer: the verdict is computed locally from the plan
     * (which item the product would be, in which size, on which wall) and the
     * AI is only asked to phrase it. What travels to the model is the product's
     * *words* — name, description, category — never an image and never the
     * catalogue. With the AI off, slow or out of quota the local wording stands.
     *
     * @param array<string, mixed> $plan
     * @param array{id: int, name: string, text: string, category: string, url: string} $context
     * @return array{text: string, source: string}
     */
    public function fit(array $plan, array $context, string $locale = 'en'): array
    {
        $fit = $this->config()['fit'];
        $facts = $this->fitFacts($plan, $context, $locale);
        $fallback = $facts['text'];

        if (!($fit['enabled'] ?? true)
            || !($this->config()['ai']['enabled'] ?? true)
            || !$this->ai->enabled()) {
            return ['text' => $fallback, 'source' => 'local', 'fit' => $facts['key'], 'state' => $facts['state']];
        }

        /* the same product in the same room is asked about over and over */
        $fingerprint = sha1(implode('|', [
            $locale,
            $this->fingerprint((array) $plan['answers']),
            (string) $context['id'],
            (string) $facts['key'],
            sha1((string) $context['name'] . '|' . (string) $context['text']),
        ]));

        return $this->cache->remember(
            'planner.fit.' . $locale,
            $fingerprint,
            fn (): array => $this->writeFit($plan, $context, $facts, $locale, $fallback),
            (int) ($this->config()['cache_hours'] ?? 720),
        );
    }

    /**
     * One small request: the model rephrases a verdict this class already
     * reached. It may not invent a size, a fitting or a price.
     *
     * @param array<string, mixed> $plan
     * @param array{id: int, name: string, text: string, category: string, url: string} $context
     * @param array{text: string, state: string, key: string|null} $facts
     * @return array{text: string, source: string}
     */
    private function writeFit(array $plan, array $context, array $facts, string $locale, string $fallback): array
    {
        $fit = $this->config()['fit'];

        if (!($fit['enabled'] ?? true) || !$this->guard->allows('planner.fit', (int) ($fit['daily_per_ip'] ?? 8))) {
            return ['text' => $fallback, 'source' => 'local', 'fit' => $facts['key'], 'state' => $facts['state'], 'ok' => false];
        }

        $lines = [
            'You are the bathroom planner of LUFLY, a sanitary-ware shop.',
            'A visitor planned a bathroom and is now looking at one product. The plan below is already fixed — do not change it, do not add or remove anything.',
            '',
            'Their plan:',
            (string) $plan['plan_text'],
            '',
            'The product they are looking at (described by text only, no picture):',
            'Name: ' . (string) $context['name'],
        ];

        if ((string) $context['text'] !== '') {
            $lines[] = 'Description: ' . (string) $context['text'];
        }

        if ((string) $context['category'] !== '') {
            $lines[] = 'Category: ' . (string) $context['category'];
        }

        $lines = array_merge($lines, [
            '',
            'Verdict already decided for you, use exactly this:',
            (string) $facts['text'],
            '',
            'Rewrite the verdict as a warm, plain answer of at most ' . (int) ($fit['max_words'] ?? 80) . ' words in the language with this code: ' . $locale . '.',
            'Rules: keep every size exactly as given; never invent a dimension, a price or a brand; no markdown, no emoji; two short paragraphs at most.',
        ]);

        $result = $this->ai->generate(implode("\n", $lines), [
            'scope' => 'planner.fit',
            'locale' => $locale,
        ]);

        $this->record('planner.fit', $result);

        if (!($result['ok'] ?? false) || trim((string) ($result['text'] ?? '')) === '') {
            return [
                'text' => $fallback,
                'source' => 'local',
                'fit' => $facts['key'],
                'state' => $facts['state'],
                'ok' => false,
                'error' => $result['error'] ?? null,
            ];
        }

        return [
            'text' => trim((string) $result['text']),
            'source' => 'ai',
            'fit' => $facts['key'],
            'state' => $facts['state'],
            'ok' => true,
            'model' => $result['model'] ?? null,
        ];
    }

    /**
     * The verdict, computed locally: which item of the plan this product would
     * be, and what that means for the room. Zero tokens, never wrong about the
     * plan, and the wording the AI gets to improve on.
     *
     * @param array<string, mixed> $plan
     * @param array{id: int, name: string, text: string, category: string, url: string} $context
     * @return array{text: string, state: string, key: string|null}
     */
    public function fitFacts(array $plan, array $context, string $locale = 'en'): array
    {
        $answers = (array) $plan['answers'];
        $room = (array) ($plan['room'] ?? []);
        $key = $this->matchSlot($context);

        if ($key !== null) {
            foreach ((array) $plan['items'] as $item) {
                if ((string) ($item['key'] ?? '') === $key) {
                    return [
                        'key' => $key,
                        'state' => 'in_plan',
                        'text' => $this->t('fit_in_plan', $locale, [
                            'item' => (string) $item['label'],
                            'zone' => (string) $item['zone'],
                            'size' => (string) $item['size'],
                        ]),
                    ];
                }
            }

            return [
                'key' => $key,
                'state' => 'not_in_plan',
                'text' => $this->t('fit_not_in_plan', $locale, [
                    'item' => $this->itemName($key, $locale),
                    'area' => (string) $answers['area'],
                ]),
            ];
        }

        return [
            'key' => null,
            'state' => 'other',
            'text' => $this->t('fit_other', $locale, [
                'w' => (string) ($room['w'] ?? ''),
                'l' => (string) ($room['l'] ?? ''),
            ]),
        ];
    }

    /**
     * Which slot of the plan a product would fill, from its own words: how many
     * of a slot's keywords the name and description mention, then how specific
     * the longest of them is.
     *
     * "Toilet paper holder … with shelf" mentions `paper` and `holder` — more
     * than the one `toilet` the toilet slot hears — so it lands on the holder,
     * where it belongs. A category on its own is never enough: six slots share
     * `bathroom-ceramics`, and guessing between a toilet and a bathtub helps
     * nobody.
     *
     * @param array{name?: string, text?: string, category?: string} $context
     */
    public function matchSlot(array $context): ?string
    {
        $slots = (array) ($this->config()['slots'] ?? []);
        $category = strtolower(trim((string) ($context['category'] ?? '')));
        $haystack = mb_strtolower(trim((string) ($context['name'] ?? '') . ' ' . (string) ($context['text'] ?? '')));

        if ($haystack === '') {
            return null;
        }

        $candidates = [];

        foreach ($slots as $key => $slot) {
            $hits = 0;
            $longest = 0;

            foreach ((array) ($slot['keywords'] ?? []) as $word) {
                $word = mb_strtolower(trim((string) $word));

                if ($word === '' || !$this->mentions($haystack, $word)) {
                    continue;
                }

                $hits++;
                $longest = max($longest, mb_strlen($word));
            }

            if ($hits === 0) {
                continue;
            }

            $candidates[] = [
                'key' => (string) $key,
                'hits' => $hits,
                'longest' => $longest,
                'category' => $category !== '' && strtolower((string) ($slot['category'] ?? '')) === $category,
            ];
        }

        if ($candidates === []) {
            return null;
        }

        /* stable in PHP 8: the order in config/planner.php breaks the last tie */
        usort($candidates, static fn (array $a, array $b): int => [$b['hits'], $b['longest'], $b['category']] <=> [$a['hits'], $a['longest'], $a['category']]);

        return $candidates[0]['key'];
    }

    /**
     * Does the text mention this word? Words of three letters or less ("wc",
     * "bar") would otherwise match inside half the catalogue.
     */
    private function mentions(string $haystack, string $word): bool
    {
        if (mb_strlen($word) <= 3) {
            return preg_match('/(?<![\p{L}\d])' . preg_quote($word, '/') . '(?![\p{L}\d])/u', $haystack) === 1;
        }

        return str_contains($haystack, $word);
    }

    /**
     * The lines the chat rotates while the assistant is thinking. Varied on
     * purpose: a visitor should never stare at one frozen sentence.
     *
     * @return array<int, string>
     */
    public function waitingMessages(string $locale = 'en'): array
    {
        $messages = [$this->t('thinking', $locale)];

        for ($i = 1; $i <= 6; $i++) {
            $key = 'wait_' . $i;
            $value = $this->t($key, $locale);

            if ($value !== 'planner.' . $key && $value !== '') {
                $messages[] = $value;
            }
        }

        return $messages;
    }

    /**
     * The picture of the finished room: optional, on demand, and the only step
     * in the whole flow that costs an image generation. The catalogue pictures
     * of the suggested items travel with the request, so the render matches the
     * things the visitor is actually looking at.
     */
    public function render(array $answers, array $plan, string $locale = 'en'): array
    {
        $render = $this->config()['render'];
        $off = $this->t('render_off', $locale);

        if (!($render['enabled'] ?? true) || !feature('render', true)) {
            return ['ok' => false, 'error' => $off];
        }

        if (!$this->ai->enabled() || trim((string) config('ai.image_model', '')) === '') {
            return ['ok' => false, 'error' => $off];
        }

        if (!$this->guard->allows('planner.render', (int) ($render['daily_per_ip'] ?? 3))) {
            return ['ok' => false, 'error' => $this->t('render_limit', $locale)];
        }

        $prompt = implode("\n", [
            'Photorealistic interior photo of a ' . $answers['room']['w'] . ' × ' . $answers['room']['l'] . ' cm bathroom.',
            'Wet area: ' . $answers['wet'] . '. Style ' . $answers['style'] . ', metal finish ' . $answers['finish'] . '.',
            'Fixtures, all ' . $answers['finish'] . ': ' . implode(', ', array_map(
                static fn (array $item): string => $item['label'] . ' (' . $item['size'] . ')',
                $plan['items'],
            )) . '.',
            'Matte large-format tiles, warm daylight, eye-level wide angle, clean and uncluttered, no people, no text, no watermark.',
        ]);

        $result = $this->ai->image($prompt, $this->references($plan, (int) ($render['references'] ?? 2)));

        $this->record('planner.render', $result);

        if (!($result['ok'] ?? false) || empty($result['images'])) {
            return ['ok' => false, 'error' => $this->t('render_failed', $locale), 'raw' => $result['error'] ?? null];
        }

        return [
            'ok' => true,
            'src' => (string) $result['images'][0],
            'w' => $answers['room']['w'],
            'l' => $answers['room']['l'],
            'items' => count($plan['items']),
            'model' => $result['model'] ?? null,
        ];
    }

    /* ---------------------------------------------------------------------
     * catalogue suggestions — always our own database, never the AI
     * ------------------------------------------------------------------- */

    /**
     * One or two products from the LUFLY catalogue for an item slot.
     *
     * @param array<string, mixed> $slot
     * @return array<int, array<string, mixed>>
     */
    public function picks(array $slot, string $finish, string $locale, ?int $limit = null): array
    {
        $limit = $limit ?? (int) ($this->config()['picks_per_slot'] ?? 2);
        $keywords = array_values(array_filter((array) ($slot['keywords'] ?? [])));
        $category = (string) ($slot['category'] ?? '');
        $seen = [];
        $picks = [];

        /* first inside the slot's own category, then — only if that found
           nothing — across the whole catalogue. Still one query per keyword. */
        foreach ([$category, ''] as $scope) {
            foreach ($keywords as $keyword) {
                if (count($picks) >= $limit) {
                    break 2;
                }

                $filters = ['locale' => $locale, 'search' => (string) $keyword];

                if ($scope !== '') {
                    $filters['category_slug'] = $scope;
                }

                foreach ($this->products->search($filters, 1, 4)->items() as $product) {
                    $id = $this->idOf($product);

                    if ($id === 0 || isset($seen[$id]) || count($picks) >= $limit) {
                        continue;
                    }

                    $seen[$id] = true;
                    $picks[] = $this->card($product, $finish, $locale);
                }
            }
        }

        return $picks;
    }

    /** A product is a model from the repository or a plain array. */
    private function idOf(mixed $product): int
    {
        if (is_array($product)) {
            return (int) ($product['id'] ?? 0);
        }

        if (is_object($product)) {
            return (int) ($product->id ?? 0);
        }

        return 0;
    }

    /** The small card the chat shows under an item. */
    private function card(mixed $product, string $finish, string $locale): array
    {
        /* the repository hands back models; a plain array is accepted too, so
           the planner can be fed from a fixture or a cached payload */
        $row = $product;

        if (!is_array($row)) {
            $row = method_exists($row, 'translate') ? (array) $row->translate($locale) : (array) $row;
        }

        $size = '';

        foreach ((array) ($row['specs'] ?? []) as $spec) {
            $key = strtolower((string) ($spec['key'] ?? $spec['spec_key'] ?? ''));

            if (str_contains($key, 'size') || str_contains($key, 'dimension')) {
                $size = (string) ($spec['value'] ?? $spec['spec_value'] ?? '');
                break;
            }
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'url' => $this->productUrl($row, $locale),
            'image' => (string) ($row['image'] ?? ''),
            'size' => $size,
            'finish' => $this->finishName($finish, $locale),
        ];
    }

    /** The product page a plan card links to, built by the router. */
    private function productUrl(array $product, string $locale): string
    {
        /* the router is the one place that knows both the translated segment
           (`produkty`, `urunler`, …) and the folder the shop sits in — and the
           planner is always rendered in the request's own locale */
        unset($locale);

        $slug = (string) ($product['slug'] ?? '');

        return $slug !== '' ? route('products.show', ['slug' => $slug]) : '';
    }

    /** The first catalogue pictures, handed to the image model as references. */
    private function references(array $plan, int $limit): array
    {
        $references = [];

        foreach ($plan['items'] as $item) {
            foreach ($item['picks'] as $pick) {
                $image = (string) ($pick['image'] ?? '');

                if ($image === '' || in_array($image, $references, true)) {
                    continue;
                }

                $references[] = $image;

                if (count($references) >= $limit) {
                    return $references;
                }
            }
        }

        return $references;
    }

    /* ---------------------------------------------------------------------
     * handing the plan to the team
     * ------------------------------------------------------------------- */

    /**
     * The two ways a plan leaves the shop: a WhatsApp message the visitor can
     * send in one tap, and the e-mail the shop sends on their behalf.
     *
     * @param array<string, mixed> $plan
     * @return array<string, string>
     */
    public function handoff(array $plan, string $locale = 'en'): array
    {
        $config = (array) ($this->config()['handoff'] ?? []);
        $body = $this->t('handoff_wa_body', $locale, ['plan' => $plan['plan_text']]);

        return [
            'phone' => (string) ($config['phone'] ?? ''),
            'email' => (string) ($config['email'] ?? ''),
            'whatsapp' => 'https://wa.me/' . rawurlencode((string) ($config['whatsapp'] ?? ''))
                . '?text=' . rawurlencode($body),
            'body' => $body,
            'mail_body' => $this->t('handoff_mail_body', $locale, [
                'plan' => $plan['plan_text'],
                'email' => '',
            ]),
        ];
    }

    /* ---------------------------------------------------------------------
     * placement
     * ------------------------------------------------------------------- */

    /**
     * Which wall each item prefers. The wet area takes the back wall, the
     * toilet the wall opposite the door, the basin the wall beside it.
     */
    private const WALLS = [
        'shower' => ['back', 'left', 'right'],
        'bathtub' => ['back', 'left', 'right'],
        'toilet' => ['right', 'left', 'back'],
        'paper_holder' => ['right', 'left', 'back'],
        'towel_bar' => ['right', 'left', 'back'],
        'basin' => ['left', 'right', 'back'],
        'second_basin' => ['left', 'right', 'back'],
    ];

    /** Wall-hung pieces: they stand where the piece they belong to stands. */
    private const ACCESSORIES = [
        'basin_mixer' => 'basin',
        'mirror' => 'basin',
    ];

    /**
     * Where every item goes, in centimetres, measured from the corner of the
     * door wall (x to the right, y away from the door).
     *
     * Every piece is laid along a wall in the order a plumber would work: the
     * wet area first, then whatever follows it on that wall. Nothing is ever
     * stacked on top of something else — a wall that is full means the piece
     * moves to the next wall, and a room that is full means the piece is left
     * out rather than drawn over the door.
     *
     * @return array<int, array<string, mixed>>
     */
    public function layout(array $answers): array
    {
        $slots = $this->config()['slots'];
        $room = $answers['room'];
        $w = (int) $room['w'];
        $l = (int) $room['l'];

        /* every wall is a line: `start` where the next piece may stand, `end`
           where the wall runs out. A piece on one wall moves the start of the
           walls beside it and the end of the wall opposite, which is how two
           pieces can never share a corner. */
        $grid = [
            'back' => ['start' => 10, 'end' => $w - 10],
            'left' => ['start' => 10, 'end' => $l - 10],
            'right' => ['start' => 10, 'end' => $l - 10],
        ];

        $blocks = [];
        $keys = array_keys($slots);
        $wet = array_values(array_filter($keys, static fn (string $key): bool => in_array($key, ['shower', 'bathtub'], true)));

        /* the wet area goes first: it is the biggest piece and the one the
           visitor asked for by name, so it gets the wall it wants */
        foreach (array_merge($wet, array_values(array_diff($keys, $wet))) as $key) {
            $this->put($key, $slots[$key], $answers, $grid, $blocks, $w, $l);
        }

        return array_values($blocks);
    }

    /**
     * One slot: does it belong in this room, is it a wall-hung accessory, and
     * where does it fit? Nothing is written if the answer is "nowhere".
     *
     * @param array<string, mixed> $slot
     * @param array<string, array{start: int, end: int}> $grid
     * @param array<string, array<string, mixed>> $blocks
     */
    private function put(string $key, array $slot, array $answers, array &$grid, array &$blocks, int $w, int $l): void
    {
        if (!$this->applies((string) ($slot['when'] ?? 'always'), $answers)) {
            return;
        }

        [$along, $out] = $this->footprint($key);

        /* a mirror or a mixer hangs where the piece it belongs to stands */
        $host = self::ACCESSORIES[$key] ?? null;

        if ($host !== null) {
            if (!isset($blocks[$host])) {
                return;
            }

            $block = $blocks[$host];
            $blocks[$key] = $this->attach(
                $key,
                (string) $block['wall'],
                (int) $block['offset'],
                min($along, (int) $block['along']),
                $out,
                $w,
                $l,
            );

            return;
        }

        $wall = $this->fitsOnWall($key, $along, $out, $w, $l, $grid);

        if ($wall === null) {
            return;
        }

        $offset = $grid[$wall]['start'];
        $grid[$wall]['start'] = $offset + $along + 10;

        $reach = $out + 10;

        if ($wall === 'back') {
            $grid['left']['start'] = max($grid['left']['start'], 10 + $reach);
            $grid['right']['start'] = max($grid['right']['start'], 10 + $reach);
        } elseif ($wall === 'left') {
            $grid['back']['start'] = max($grid['back']['start'], 10 + $reach);
        } else {
            $grid['back']['end'] = min($grid['back']['end'], $w - $reach);
        }

        $blocks[$key] = $this->attach($key, $wall, $offset, $along, $out, $w, $l);
    }

    /**
     * The first wall that still has room for the piece, in the order the piece
     * would like them. Null means it does not fit anywhere — better to leave it
     * out of the drawing than to draw it through the door.
     *
     * @param array<string, array{start: int, end: int}> $grid
     */
    private function fitsOnWall(string $key, int $along, int $out, int $w, int $l, array $grid): ?string
    {
        foreach (self::WALLS[$key] ?? ['back', 'left', 'right'] as $wall) {
            $roomDepth = $wall === 'back' ? $l : $w;

            if ($out > $roomDepth - 20) {
                continue;
            }

            if ($along > $grid[$wall]['end'] - $grid[$wall]['start']) {
                continue;
            }

            return $wall;
        }

        return null;
    }

    /**
     * One piece of furniture, placed along its wall: the offset walks away from
     * the corner the wall shares with the door, and the plan is measured from
     * that same corner (x to the right, y away from the door).
     *
     * @return array<string, mixed>
     */
    private function attach(string $key, string $wall, int $offset, int $along, int $out, int $w, int $l): array
    {
        [$x, $y, $spanX, $spanY] = match ($wall) {
            'left' => [0, $l - $offset - $along, $out, $along],
            'right' => [$w - $out, $l - $offset - $along, $out, $along],
            default => [$offset, $l - $out, $along, $out],
        };

        return [
            'key' => $key,
            'wall' => $wall,
            'offset' => $offset,
            'along' => $along,
            'w' => $spanX,
            'l' => $spanY,
            'x' => (int) round($x),
            'y' => (int) round($y),
            'zone' => $wall,
        ];
    }

    /**
     * Real footprints in centimetres: `along` the wall, then `out` from it.
     * They match the size each slot recommends, so the drawing and the item
     * list can never disagree about how much floor a piece takes.
     *
     * @return array{0: int, 1: int}
     */
    private function footprint(string $key): array
    {
        return match ($key) {
            'toilet' => [37, 60],
            'basin' => [55, 45],
            'second_basin' => [45, 35],
            'basin_mixer' => [18, 18],
            'shower' => [80, 80],
            'bathtub' => [170, 70],
            'towel_bar' => [60, 12],
            'paper_holder' => [15, 13],
            'mirror' => [60, 4],
            default => [40, 40],
        };
    }

    private function applies(string $when, array $answers): bool
    {
        if ($when === 'always') {
            return true;
        }

        if (str_starts_with($when, 'wet:')) {
            return substr($when, 4) === $answers['wet'];
        }

        if (str_starts_with($when, 'area>=')) {
            return (float) $answers['area'] >= (float) substr($when, 6);
        }

        return true;
    }

    /* ---------------------------------------------------------------------
     * wording helpers — translation files, so the assistant can add languages
     * ------------------------------------------------------------------- */

    public function title(string $locale): string
    {
        return $this->t('title', $locale);
    }

    public function itemName(string $key, string $locale): string
    {
        return $this->t('item_' . $key, $locale);
    }

    public function zoneName(string $zone, string $locale): string
    {
        return $this->t('zone_' . $zone, $locale);
    }

    public function lookName(string $look, string $locale): string
    {
        return $this->t('look_' . $look, $locale);
    }

    public function finishName(string $finish, string $locale): string
    {
        return $this->t('finish_' . $finish, $locale);
    }

    public function doorNote(string $locale): string
    {
        return $this->t('door_note', $locale);
    }

    public function windowNote(string $locale): string
    {
        return $this->t('window_note', $locale);
    }

    public function clearanceNote(int $clearance, string $locale): string
    {
        if ($clearance >= 0) {
            return $this->t('clearance_ok', $locale);
        }

        /* how much more floor the door needs, rounded up to the next 5 cm */
        $missing = (int) (ceil(abs($clearance) / 5) * 5);

        return $this->t('clearance_tight', $locale, ['cm' => (string) $missing]);
    }

    /**
     * @param array<string, string> $replace
     */
    public function t(string $key, string $locale = 'en', array $replace = []): string
    {
        /* answered in the language the visitor is reading, whatever the request
           locale happens to be when the plan is built from a cached answer */
        return (string) trans('planner.' . $key, $replace, $locale !== '' ? $locale : null);
    }

    /* ---------------------------------------------------------------------
     * helpers
     * ------------------------------------------------------------------- */

    /**
     * One row per call in `ai_usage`: the daily caps are read from that table,
     * and the admin can see what the quota was spent on.
     *
     * @param array<string, mixed> $result what the client returned
     */
    private function record(string $scope, array $result): void
    {
        $usage = (array) ($result['raw']['usageMetadata'] ?? []);

        $this->guard->record($scope, [
            'key' => (int) ($result['key'] ?? 0),
            'model' => (string) ($result['model'] ?? ''),
            'prompt_tokens' => (int) ($usage['promptTokenCount'] ?? 0),
            'output_tokens' => (int) ($usage['candidatesTokenCount'] ?? 0),
            'ok' => (bool) ($result['ok'] ?? false),
            'error' => (string) ($result['error'] ?? ''),
        ]);
    }

    /** Four answers in, one short string out — the identity of a plan. */
    private function fingerprint(array $answers): string
    {
        return sha1((string) json_encode([
            $answers['size'],
            $answers['custom'],
            $answers['wet'],
            $answers['look'],
        ]));
    }

    /** @return array<string, mixed> */
    private function config(): array
    {
        $config = config('planner');

        return is_array($config) ? $config : [];
    }

    private function clamp(mixed $value, int $min, int $max): int
    {
        $value = (int) $value;

        return max($min, min($max, $value > 0 ? $value : $min));
    }
}
