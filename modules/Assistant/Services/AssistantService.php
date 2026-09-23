<?php

declare(strict_types=1);

namespace Modules\Assistant\Services;

use App\Services\Ai\AiCache;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiGuard;
use App\Services\MailService;
use Core\View\View;
use Modules\Assistant\Models\AssistantLead;
use Throwable;

/**
 * The finder, end to end: a description (any language) or a photo in, a bank of
 * matching pieces out.
 *
 * The order of work is what keeps it cheap:
 *
 *   1. the words are searched in our own catalogue — no model, no tokens;
 *   2. the model is asked *only* when that first pass found nothing good, and
 *      only for a handful of search words (never the catalogue, never a price);
 *   3. a photo is one small vision call (the browser sends it already shrunk),
 *      cached, so the same picture is described once.
 *
 * Whatever happens — no key, no network, a 429 — the visitor still gets the
 * closest pieces we have, plus the promise that the team will look at it.
 */
final class AssistantService
{
    public function __construct(
        private readonly AiClient $ai,
        private readonly AiCache $cache,
        private readonly AiGuard $guard,
        private readonly CatalogFinder $finder,
        private readonly MailService $mail,
        private readonly View $view,
    ) {
    }

    public function enabled(): bool
    {
        return (bool) config('assistant.enabled', true) && (bool) config('assistant.chat.enabled', true);
    }

    public function photosEnabled(): bool
    {
        return (bool) config('assistant.photo.enabled', true) && $this->enabled();
    }

    /**
     * What the chat says while it works — several short lines, so a slow answer
     * does not look like a frozen one.
     *
     * @return array<int, string>
     */
    public function waiting(string $locale): array
    {
        $messages = [];

        for ($i = 1; $i <= 4; $i++) {
            $key = 'assistant.waiting_' . $i;
            $message = trans($key, [], $locale);

            if ($message !== '' && $message !== $key) {
                $messages[] = $message;
            }
        }

        return $messages;
    }

    /**
     * One question: text, a photo, or both.
     *
     * @param array{q?:string,email?:string,photo?:array{name?:string,mime?:string,data?:string},product_id?:int} $input
     * @return array<string, mixed>
     */
    public function ask(array $input, string $locale): array
    {
        $limit = (int) config('assistant.chat.daily_per_ip', 20);

        if (!$this->guard->allows('assistant.ask', $limit)) {
            return [
                'ok' => false,
                'reason' => 'limit',
                'text' => trans('assistant.over_limit'),
                'cards' => [],
            ];
        }

        $question = $this->clamp((string) ($input['q'] ?? ''));
        $photo = $this->photo($input['photo'] ?? null);
        /* a picture that came back empty (too big, or not a picture at all) is
           said out loud instead of quietly ignored */
        $photoRejected = $photo === null
            && is_array($input['photo'] ?? null)
            && trim((string) ($input['photo']['data'] ?? '')) !== '';
        $productId = max(0, (int) ($input['product_id'] ?? 0));

        if ($question === '' && $photo === null && $productId === 0) {
            return ['ok' => false, 'reason' => 'empty', 'text' => trans('assistant.need_words'), 'cards' => []];
        }

        /* ---- 1. what we already know how to look for --------------------- */

        $fromProduct = $question === '' && $photo === null && $productId > 0;
        $terms = $fromProduct ? [] : $this->finder->terms($question, $locale);
        $category = $fromProduct ? '' : $this->finder->categorySlug($question);
        $cards = [];
        $source = 'local';
        $summary = '';
        $facets = null;

        if ($fromProduct) {
            $cards = $this->finder->similarTo($productId, $locale);
            $source = 'catalogue';
        } else {
            $cards = $this->finder->find($terms, $locale, null, $category);
        }

        $best = $this->bestScore($cards);
        $good = (int) config('assistant.find.good_score', 24);
        $enough = $cards !== [] && $best >= $good;

        /* ---- 2. the model, only when the catalogue words were not enough -- */

        $vision = $photo !== null && $this->visionAllowed();

        if (($vision || (!$enough && $this->aiAllowed())) && !$fromProduct) {
            $facets = $this->facets($question, $photo, $locale, $vision);

            if ($facets !== null && ($facets['terms'] !== [] || $facets['category'] !== '')) {
                $merged = $this->merge($terms, (array) $facets['terms']);
                $wanted = (string) ($facets['category'] ?? '') !== '' ? (string) $facets['category'] : $category;
                $withAi = $this->finder->find($merged, $locale, null, $wanted);

                /* the model only ever improves the answer — never shrinks it */
                if ($this->bestScore($withAi) >= $best) {
                    $cards = $withAi;
                    $terms = $merged;
                    $category = $wanted;
                    $source = $cards === [] ? 'local' : 'ai';
                }
            }

            $summary = (string) ($facets['summary'] ?? '');
        }

        /* ---- 3. never answer with nothing --------------------------------- */

        $exact = $cards !== [] && $this->bestScore($cards) >= (int) config('assistant.find.min_score', 3);

        $fallback = false;

        if ($cards === [] && !$exact) {
            /* nothing answered to a single word: show what the shop is known
               for, and say plainly that it is not a match */
            $cards = $this->finder->featured($locale, null, $category);
            $fallback = true;
        }

        /* the words of a description are the fallback for a photo nobody read */
        if ($cards === [] && $question !== '' && $terms === []) {
            $terms = $this->finder->terms($question, $locale);
            $cards = $this->finder->find($terms, $locale, null, $category);
        }

        $cards = array_slice($cards, 0, max(1, (int) config('assistant.find.limit', 6)));

        /* ---- 4. the visitor's address, the team's mail --------------------- */

        $lead = $this->keep($input, $question, $photo, $locale, $terms, $category, $summary, count($cards), $fromProduct ? $productId : 0);

        $reply = [
            'ok' => true,
            'text' => $this->wording($question, $photo, $cards, $exact, $locale, $fromProduct, $fallback),
            'note' => $photoRejected
                ? trans('assistant.note_photo_rejected')
                : $this->note($photo, $lead, $exact, $locale),
            'source' => $source,
            'exact' => $exact,
            'terms' => array_values($terms),
            'category' => $category,
            'summary' => $summary,
            'cards' => array_values($cards),
            'count' => count($cards),
            'photo' => $photo !== null ? ['url' => (string) $photo['url'], 'name' => (string) $photo['name']] : null,
            'lead' => ['stored' => $lead['stored'], 'notified' => $lead['notified'], 'email' => $lead['email']],
            'search_url' => $this->searchUrl($question),
            'support' => [
                'email' => (string) config('planner.handoff.email', 'info@lufly.tr'),
                'whatsapp' => (string) config('planner.handoff.whatsapp', ''),
            ],
        ];

        $this->guard->record('assistant.ask', ['ok' => true, 'model' => (string) ($facets['model'] ?? '')]);

        return $reply;
    }

    /* ------------------------------------------------------------------ the model */

    /**
     * A handful of search words for a sentence, or for a picture.
     *
     * @param array{name:string,mime:string,base64:string,kb:int,path:string,url:string}|null $photo
     * @return array{ok:bool,terms:array<int,string>,category:string,summary:string,model:string}|null
     */
    private function facets(string $question, ?array $photo, string $locale, bool $vision): ?array
    {
        if (!$this->aiAllowed() && !$vision) {
            return null;
        }

        $scope = $vision ? 'assistant.vision' : 'assistant.facets';
        $fingerprint = sha1($locale . '|' . ($vision
            ? 'photo:' . sha1((string) $photo['base64'])
            : 'text:' . sha1($question)));

        $payload = $this->cache->remember($scope, $fingerprint, function () use ($question, $photo, $locale, $vision, $scope): array {
            if (!$this->guard->allows($scope, (int) config('ai.daily_limit_per_ip', 15))) {
                return ['ok' => false, 'terms' => [], 'category' => '', 'summary' => '', 'model' => ''];
            }

            $options = [
                'json' => true,
                'scope' => $scope,
                'locale' => $locale,
                'temperature' => 0.2,
            ];

            $model = trim((string) config('assistant.ai.model', ''));

            if ($model !== '') {
                $options['model'] = $model;
            }

            if ($vision) {
                $options['images'] = [['mime' => (string) $photo['mime'], 'data' => (string) $photo['base64']]];
            }

            $result = $this->ai->generate($vision ? $this->visionPrompt($locale) : $this->textPrompt($question, $locale), $options);

            $this->record($scope, $result);

            if (!($result['ok'] ?? false)) {
                return ['ok' => false, 'terms' => [], 'category' => '', 'summary' => '', 'model' => (string) ($result['model'] ?? '')];
            }

            $data = (array) ($result['data'] ?? []);
            $terms = [];

            foreach ((array) ($data['terms'] ?? []) as $term) {
                $term = trim((string) $term);

                if ($term !== '' && !in_array($term, $terms, true)) {
                    $terms[] = $term;
                }
            }

            $slug = trim((string) ($data['category'] ?? ''));

            if ($slug !== '' && !isset($this->finder->categories($locale)[$slug])) {
                $slug = $this->finder->categorySlug($slug);
            }

            return [
                'ok' => true,
                'terms' => array_slice($terms, 0, max(1, (int) config('assistant.ai.max_terms', 5))),
                'category' => $slug,
                'summary' => mb_substr(trim((string) ($data['item'] ?? $data['summary'] ?? '')), 0, 200),
                'model' => (string) ($result['model'] ?? ''),
            ];
        }, (int) config('assistant.cache_hours', 168));

        if (!($payload['ok'] ?? false)) {
            return $payload;
        }

        return $payload;
    }

    private function textPrompt(string $question, string $locale): string
    {
        return implode("\n", [
            'You are the finder of LUFLY, a sanitary-ware shop online.',
            'A visitor is looking for a piece and wrote the text below. Turn it into catalogue search words.',
            'The catalogue categories (slug: name): ' . $this->categoryList($locale) . '.',
            'The catalogue is labelled in English and Czech.',
            '',
            'Visitor (' . $locale . '):',
            $question,
            '',
            'Answer with JSON only, no prose:',
            '{"terms":["up to five short search words, the item type first, then colour, material or size if they were mentioned"],"category":"one slug from the list above, or an empty string"}',
        ]);
    }

    private function visionPrompt(string $locale): string
    {
        return implode("\n", [
            'You are the finder of LUFLY, a sanitary-ware shop online.',
            'A visitor sent a photo of one piece they would like us to match with something from our catalogue.',
            'Say what it is and turn it into catalogue search words. Look at the piece itself, not the room around it.',
            'The catalogue categories (slug: name): ' . $this->categoryList($locale) . '.',
            'The catalogue is labelled in English and Czech.',
            '',
            'Answer with JSON only, no prose:',
            '{"item":"the piece in three or four English words","terms":["up to five short search words: item type, material, colour, shape, size"],"category":"one slug from the list above, or an empty string"}',
        ]);
    }

    private function categoryList(string $locale): string
    {
        $out = [];

        foreach ($this->finder->categories($locale) as $slug => $name) {
            $out[] = $slug . ': ' . $name;
        }

        return implode('; ', $out);
    }

    /* ------------------------------------------------------------------ the photo */

    /**
     * The photo the browser sent: base64, already shrunk, in a field — not a
     * multipart upload, so it works the same behind every proxy.
     *
     * @param mixed $input
     * @return array{name:string,mime:string,base64:string,kb:int,path:string,url:string}|null
     */
    private function photo(mixed $input): ?array
    {
        if (!is_array($input) || !$this->photosEnabled()) {
            return null;
        }

        $raw = (string) ($input['data'] ?? '');
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        if (str_contains($raw, ',')) {
            $raw = substr($raw, (int) strpos($raw, ',') + 1);
        }

        $binary = base64_decode($raw, true);

        if ($binary === false || $binary === '') {
            return null;
        }

        $maxKb = max(64, (int) config('assistant.photo.max_kb', 4096));

        if (strlen($binary) > $maxKb * 1024) {
            return null;
        }

        $mime = $this->sniff($binary);

        if ($mime === '' || !in_array($mime, (array) config('assistant.photo.types', []), true)) {
            return null;
        }

        $folder = trim((string) config('assistant.photo.folder', 'uploads/assistant'), '/');
        $token = bin2hex(random_bytes(12));
        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $relative = $folder . '/' . $token . '.' . $extension;
        $absolute = base_path('public/' . $relative);

        try {
            if (!is_dir(dirname($absolute))) {
                @mkdir(dirname($absolute), 0755, true);
            }

            file_put_contents($absolute, $binary);
        } catch (Throwable) {
            return null;
        }

        return [
            'name' => mb_substr(trim((string) ($input['name'] ?? 'photo.' . $extension)), 0, 180),
            'mime' => $mime,
            'base64' => $raw,
            'kb' => (int) ceil(strlen($binary) / 1024),
            'path' => $relative,
            'url' => url($relative),
        ];
    }

    /** The image type is read from the bytes, never from what a browser claims. */
    private function sniff(string $binary): string
    {
        return match (true) {
            str_starts_with($binary, "\xFF\xD8\xFF") => 'image/jpeg',
            str_starts_with($binary, "\x89PNG\r\n\x1a\n") => 'image/png',
            str_starts_with($binary, 'RIFF') && substr($binary, 8, 4) === 'WEBP' => 'image/webp',
            default => '',
        };
    }

    /* ------------------------------------------------------------------ the lead */

    /**
     * Keep the request and tell the shop about it.
     *
     * A photo always reaches the team (a human has to look at it). Text reaches
     * them once per visitor per day, so a chatty visitor is one mail, not ten.
     *
     * @param array<string, mixed> $input
     * @param array{name:string,mime:string,base64:string,kb:int,path:string,url:string}|null $photo
     * @param array<int, string> $terms
     * @return array{stored:bool,notified:bool,email:string}
     */
    private function keep(
        array $input,
        string $question,
        ?array $photo,
        string $locale,
        array $terms,
        string $category,
        string $summary,
        int $results,
        int $productId,
    ): array {
        $asked = trim((string) ($input['email'] ?? ''));
        $email = filter_var($asked, FILTER_VALIDATE_EMAIL) ? $asked : '';
        $default = trim((string) config('assistant.lead.default_email', 'hossam545mohamed@gmail.com'));

        if ($photo === null && $email === '') {
            return ['stored' => false, 'notified' => false, 'email' => ''];
        }

        try {
            $lead = AssistantLead::create([
                'token' => bin2hex(random_bytes(16)),
                'ip_hash' => $this->guard->ipHash(),
                'locale' => $locale,
                'email' => $email !== '' ? $email : $default,
                'message' => $question !== '' ? $question : null,
                'product_id' => $productId,
                'image_path' => $photo['path'] ?? null,
                'image_url' => $photo['url'] ?? null,
                'image_name' => $photo['name'] ?? null,
                'image_mime' => $photo['mime'] ?? null,
                'image_kb' => (int) ($photo['kb'] ?? 0),
                'summary' => $summary !== '' ? $summary : null,
                'terms' => implode(', ', array_slice($terms, 0, 8)),
                'category' => $category !== '' ? $category : null,
                'results_count' => $results,
                'source' => $photo !== null ? 'photo' : 'text',
                'status' => 'new',
            ]);
        } catch (Throwable) {
            return ['stored' => false, 'notified' => false, 'email' => $email];
        }

        $notified = $this->notify($lead, $photo !== null, $email);

        return ['stored' => true, 'notified' => $notified, 'email' => $email];
    }

    /** The mail the team reads: who asked, what they asked, and the picture. */
    private function notify(AssistantLead $lead, bool $withPhoto, string $visitorEmail): bool
    {
        $to = trim((string) config('assistant.lead.email', ''));

        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        /* text questions are only mailed once per visitor per day; a picture
           always is — somebody has to look at it */
        if (!$withPhoto && $this->alreadyTold()) {
            return false;
        }

        try {
            $data = [
                'lead' => $lead,
                'image' => (string) $lead->image_url !== '' ? (string) $lead->image_url : '',
                'visitor' => $visitorEmail,
                'product_url' => (int) $lead->product_id > 0 ? $this->productUrl((int) $lead->product_id) : '',
                'terms' => (string) $lead->terms,
                'site' => url('/'),
            ];

            $html = $this->view->renderFile($this->view->resolvePath('assistant::emails.lead'), $data);
            $text = $this->view->renderFile($this->view->resolvePath('assistant::emails.lead-text'), $data);

            $headers = [];

            if ($visitorEmail !== '') {
                $headers['Reply-To'] = $visitorEmail;
            }

            $sent = $this->mail->sendHtml($to, trans('assistant.mail_subject', ['n' => (string) $lead->id]), $html, $text, $headers);

            if ($sent) {
                $lead->update(['notified_at' => date('Y-m-d H:i:s')]);
            }

            return $sent;
        } catch (Throwable) {
            return false;
        }
    }

    private function alreadyTold(): bool
    {
        try {
            $rows = AssistantLead::query()->connection()->select(
                'SELECT COUNT(*) AS told FROM assistant_leads
                 WHERE ip_hash = ? AND notified_at IS NOT NULL AND created_at >= ?',
                [$this->guard->ipHash(), date('Y-m-d 00:00:00')],
            );

            return (int) ($rows[0]['told'] ?? 0) > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function productUrl(int $productId): string
    {
        try {
            $rows = \Modules\Products\Models\Product::query()->connection()->select(
                'SELECT slug FROM products WHERE id = ? LIMIT 1',
                [$productId],
            );

            $slug = (string) ($rows[0]['slug'] ?? '');

            return $slug !== '' ? route('products.show', ['slug' => $slug]) : '';
        } catch (Throwable) {
            return '';
        }
    }

    /* ------------------------------------------------------------------ wording */

    /** @param array<int, array<string, mixed>> $cards */
    private function wording(string $question, ?array $photo, array $cards, bool $exact, string $locale, bool $likeThis = false, bool $fallback = false): string
    {
        $count = count($cards);
        $pick = abs((int) crc32($question !== '' ? $question : (string) ($photo['path'] ?? 'photo'))) % 3;

        if ($count === 0) {
            return trans('assistant.nothing');
        }

        if ($likeThis) {
            return trans('assistant.found_like', ['n' => (string) $count]);
        }

        /* not one word of the question was in the catalogue: these are not
           matches, they are what the shop is known for — say so */
        if ($fallback) {
            return trans($pick === 0 ? 'assistant.popular_1' : 'assistant.popular_2');
        }

        if ($photo !== null) {
            $key = $exact ? 'assistant.found_photo' : 'assistant.found_photo_loose';

            return trans($key, ['n' => (string) $count]);
        }

        if (!$exact) {
            return trans($pick === 0 ? 'assistant.loose_1' : 'assistant.loose_2', ['n' => (string) $count]);
        }

        return trans('assistant.found_' . ($pick + 1), ['n' => (string) $count]);
    }

    /** The one extra line under the cards — where the request went, or may go. */
    private function note(?array $photo, array $lead, bool $exact, string $locale): string
    {
        if ($lead['stored'] && $lead['notified']) {
            return trans('assistant.note_sent');
        }

        if ($photo !== null) {
            return trans('assistant.note_kept');
        }

        if (!$exact) {
            return trans('assistant.note_team');
        }

        return '';
    }

    private function searchUrl(string $question): string
    {
        $query = trim(mb_substr($question, 0, 80));

        return $query === '' ? route('products.index') : route('products.index', ['q' => $query]);
    }

    /* ------------------------------------------------------------------ helpers */

    /** @param array<int, array<string, mixed>> $cards */
    private function bestScore(array $cards): int
    {
        $best = 0;

        foreach ($cards as $card) {
            $best = max($best, (int) ($card['score'] ?? 0));
        }

        return $best;
    }

    /**
     * @param array<int, string> $a
     * @param array<int, string> $b
     * @return array<int, string>
     */
    private function merge(array $a, array $b): array
    {
        $out = [];

        /* the visitor's own words come first: they know what they want */
        foreach (array_merge($a, $b) as $term) {
            $term = trim((string) $term);

            if ($term !== '' && !in_array($term, $out, true)) {
                $out[] = $term;
            }
        }

        return array_slice($out, 0, max(2, (int) config('assistant.find.terms', 8)));
    }

    private function clamp(string $question): string
    {
        $max = max(60, (int) config('assistant.chat.max_chars', 600));
        $question = trim((string) preg_replace('/\s+/u', ' ', $question));

        return mb_substr($question, 0, $max);
    }

    private function aiAllowed(): bool
    {
        return (bool) config('assistant.ai.enabled', true)
            && (bool) config('ai.enabled', true)
            && $this->ai->enabled();
    }

    private function visionAllowed(): bool
    {
        return $this->aiAllowed() && (bool) config('assistant.ai.vision', true);
    }

    /** @param array<string, mixed> $result */
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
}
