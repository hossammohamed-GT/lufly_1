<?php

declare(strict_types=1);

namespace Modules\Box\Services;

use Core\Http\Response;
use Core\Localization\Translator;
use Modules\Box\Models\Box;
use Modules\Box\Repositories\BoxRepository;

class BoxService
{
    private ?Box $box = null;

    private bool $resolved = false;

    public function __construct(
        private readonly BoxRepository $boxes,
        private readonly Translator $translator,
    ) {
    }

    private const SESSION_KEY = 'lufly_box_token';

    public function enabled(): bool
    {
        return (bool) config('box.enabled', true);
    }

    public function cookieName(): string
    {
        return (string) config('box.cookie', 'lufly_box');
    }

    public function current(bool $create = false): ?Box
    {
        if ($this->resolved) {
            return $this->box === null && $create ? $this->create() : $this->box;
        }

        $this->resolved = true;

        $token = (string) request()->cookie($this->cookieName(), '');

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

    public function adopt(Box $box): Box
    {
        $this->resolved = true;
        $this->box = $box;

        session()->set(self::SESSION_KEY, (string) $box->token);

        if ($box->claimed_at === null) {
            $box->update(['claimed_at' => date('Y-m-d H:i:s')]);
        }

        return $box;
    }

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

    public function items(string $locale): array
    {
        $box = $this->current();

        return $box === null ? [] : $this->boxes->items($box, $locale);
    }

    public function shareUrl(Box $box): string
    {
        return route('box.claim', ['token' => (string) $box->token]);
    }

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

    private function ipHash(): string
    {
        $ip = request()->ip();

        return $ip === '' ? '' : hash_hmac('sha256', $ip, (string) config('app.key', 'lufly'));
    }
}
