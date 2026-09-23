<?php

declare(strict_types=1);

namespace Modules\Box\Services;

use Core\Http\Response;
use Core\Localization\Translator;
use Modules\Box\Models\Box;
use Modules\Box\Repositories\BoxRepository;

/**
 * The visitor's quotation box.
 *
 * The box is found through a cookie holding its token; nothing is written to the
 * database until the first piece goes in. The same token is printed in the
 * message the shop receives, so the team — and the visitor, later, on another
 * device — open exactly the same list.
 */
class BoxService
{
    private ?Box $box = null;

    private bool $resolved = false;

    public function __construct(
        private readonly BoxRepository $boxes,
        private readonly Translator $translator,
    ) {
    }

    /** Where one visitor's box token is remembered besides his cookie. */
    private const SESSION_KEY = 'lufly_box_token';

    public function enabled(): bool
    {
        return (bool) config('box.enabled', true);
    }

    public function cookieName(): string
    {
        return (string) config('box.cookie', 'lufly_box');
    }

    /** The visitor's box, or null. Creates one when $create is true. */
    public function current(bool $create = false): ?Box
    {
        if ($this->resolved) {
            /* an earlier call in this same request asked only to look — and there
               was nothing. A caller that needs a box now still gets one. */
            return $this->box === null && $create ? $this->create() : $this->box;
        }

        $this->resolved = true;

        $token = (string) request()->cookie($this->cookieName(), '');

        /* One visitor, one box. Three things can carry it, and any one of them is
           enough: the cookie, the token a page handed to the browser, and — for a
           browser that drops cookies but keeps the session — the token the
           session remembers. Only when none of them has a box does a new one
           begin. */
        foreach ([$token, (string) session(self::SESSION_KEY, '')] as $remembered) {
            if ($remembered === '') {
                continue;
            }

            $found = $this->boxes->findByToken($remembered);

            if ($found !== null) {
                return $this->box = $found;
            }
        }

        return $create ? $this->create() : null;
    }

    /** Adopt a box opened from a shared link. */
    public function adopt(Box $box): Box
    {
        $this->resolved = true;
        $this->box = $box;

        /* opening the link from the team's mail makes this browser the visitor's
           browser: the box is remembered here too, so the next piece he saves
           joins this list and not a fresh one */
        session()->set(self::SESSION_KEY, (string) $box->token);

        if ($box->claimed_at === null) {
            $box->update(['claimed_at' => date('Y-m-d H:i:s')]);
        }

        return $box;
    }

    /** Remember a box the browser handed back by token. */
    public function remember(string $token): ?Box
    {
        $box = $this->boxes->findByToken($token);

        if ($box === null) {
            return null;
        }

        session()->set(self::SESSION_KEY, (string) $box->token);

        session()->set(self::SESSION_KEY, (string) $box->token);

        return $this->box = $box;
    }

    public function findByToken(string $token): ?Box
    {
        return $this->boxes->findByToken($token);
    }

    /** The token of the visitor's box, or '' when there is none yet. */
    public function token(): string
    {
        $box = $this->current();

        return $box === null ? '' : (string) $box->token;
    }

    public function count(): int
    {
        $box = $this->current();

        return $box === null ? 0 : $this->boxes->countItems($box);
    }

    /** @return int[] the pieces in the box (newest first) */
    public function productIds(): array
    {
        $box = $this->current();

        return $box === null ? [] : $this->boxes->productIds($box);
    }

    public function has(int $productId): bool
    {
        $box = $this->current();

        return $box !== null && $this->boxes->has($box, $productId);
    }

    /**
     * Put one piece in the box, or take it out again.
     *
     * @return array{added: bool, count: int, limit_reached: bool}
     */
    public function toggle(int $productId): array
    {
        if (!$this->enabled()) {
            return ['added' => false, 'count' => 0, 'limit_reached' => false];
        }

        $box = $this->current(true);

        if ($box === null) {
            return ['added' => false, 'count' => 0, 'limit_reached' => false];
        }

        if ($this->boxes->has($box, $productId)) {
            $this->boxes->removeProduct($box, $productId);

            return ['added' => false, 'count' => $this->boxes->countItems($box), 'limit_reached' => false];
        }

        $max = max(1, (int) config('box.max_items', 40));

        if ($this->boxes->countItems($box) >= $max) {
            return ['added' => false, 'count' => $this->boxes->countItems($box), 'limit_reached' => true];
        }

        $this->boxes->addProduct($box, $productId);

        return ['added' => true, 'count' => $this->boxes->countItems($box), 'limit_reached' => false];
    }

    public function remove(int $productId): int
    {
        $box = $this->current();

        if ($box === null) {
            return 0;
        }

        $this->boxes->removeProduct($box, $productId);

        return $this->boxes->countItems($box);
    }

    public function clear(): void
    {
        $box = $this->current();

        if ($box !== null) {
            $this->boxes->clearItems($box);
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function items(string $locale): array
    {
        $box = $this->current();

        return $box === null ? [] : $this->boxes->items($box, $locale);
    }

    /** The permanent link that opens this exact box anywhere (also the one the shop gets). */
    public function shareUrl(Box $box): string
    {
        return route('box.claim', ['token' => (string) $box->token]);
    }

    /** Stamp the visitor's cookie on a response so the box follows the browser. */
    public function attachCookie(Response $response, ?Box $box = null): Response
    {
        $box ??= $this->current();

        if ($box === null) {
            return $response;
        }

        $name = $this->cookieName();

        if ((string) request()->cookie($name, '') === (string) $box->token) {
            return $response;
        }

        return $response->header('Set-Cookie', $this->cookieHeader($name, (string) $box->token));
    }

    private function cookieHeader(string $name, string $token): string
    {
        $days = max(1, (int) config('box.cookie_days', 180));
        $maxAge = $days * 86400;

        return sprintf(
            '%s=%s; Expires=%s; Max-Age=%d; Path=/; %sHttpOnly; SameSite=Lax',
            $name,
            $token,
            gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT',
            $maxAge,
            $this->isSecure() ? 'Secure; ' : '',
        );
    }

    /**
     * A Secure cookie is only sent over https — and a browser on plain http
     * silently throws it away, which is what makes a visitor's list (or his box)
     * start from nothing on every click. So the flag follows the request, not
     * APP_ENV: a shop on localhost over http keeps its cookie, and a shop behind
     * a proxy gets it from X-Forwarded-Proto.
     */
    private function isSecure(): bool
    {
        $server = (array) ($_SERVER ?? []);

        return (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off')
            || (($server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (int) ($server['SERVER_PORT'] ?? 0) === 443;
    }

    private function create(): Box
    {
        $box = Box::create([
            'token' => bin2hex(random_bytes(32)),
            'locale' => $this->translator->getLocale(),
            'ip_hash' => $this->ipHash(),
            'user_agent' => mb_substr(request()->userAgent(), 0, 250),
        ]);

        return $this->box = $box;
    }

    /** IP addresses are never stored in the clear. */
    private function ipHash(): string
    {
        $ip = request()->ip();

        return $ip === '' ? '' : hash_hmac('sha256', $ip, (string) config('app.key', 'lufly'));
    }
}
