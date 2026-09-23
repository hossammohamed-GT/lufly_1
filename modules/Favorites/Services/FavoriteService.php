<?php

declare(strict_types=1);

namespace Modules\Favorites\Services;

use Core\Http\Response;
use Core\Localization\Translator;
use Modules\Favorites\Models\Favorite;
use Modules\Favorites\Repositories\FavoriteRepository;
use Modules\Products\Models\Product;

/**
 * The visitor's saved-products list.
 *
 * Identity: a visitor is anonymous, so the list is found through the cookie
 * holding its token. Nothing is created until the first product is saved, which
 * keeps the storefront free of pointless rows (and pointless queries: count()
 * answers 0 without touching the database when no cookie is present).
 *
 * The same token is printed in the e-mailed copy of the list, so the visitor can
 * open it later — on another device, months later — and land on the same list.
 */
class FavoriteService
{
    private ?Favorite $favorite = null;

    private bool $resolved = false;

    public function __construct(
        private readonly FavoriteRepository $favorites,
        private readonly Translator $translator,
    ) {
    }

    public function cookieName(): string
    {
        return (string) config('favorites.cookie', 'lufly_favorites');
    }

    /** The visitor's list, or null. Creates one when $create is true. */
    public function current(bool $create = false): ?Favorite
    {
        if ($this->resolved) {
            /* the same request may have looked first (the header's counter) and
               asked for a list only afterwards */
            return $this->favorite === null && $create ? $this->create() : $this->favorite;
        }

        $this->resolved = true;

        $token = (string) request()->cookie($this->cookieName(), '');
        if ($token !== '') {
            $this->favorite = $this->favorites->findByToken($token);

            /* A cookie pointing at a deleted list is simply replaced. */
            if ($this->favorite !== null) {
                return $this->favorite;
            }
        }

        return $create ? $this->create() : null;
    }

    /** Adopt a list opened from an e-mailed / shared link. */
    public function adopt(Favorite $favorite): Favorite
    {
        $this->resolved = true;
        $this->favorite = $favorite;

        if ($favorite->claimed_at === null) {
            $favorite->update(['claimed_at' => date('Y-m-d H:i:s')]);
        }

        return $favorite;
    }

    public function findByToken(string $token): ?Favorite
    {
        return $this->favorites->findByToken($token);
    }

    public function count(): int
    {
        $favorite = $this->current();

        return $favorite === null ? 0 : $this->favorites->countItems($favorite);
    }

    /** @return int[] saved product ids (newest first) */
    public function productIds(): array
    {
        $favorite = $this->current();

        return $favorite === null ? [] : $this->favorites->productIds($favorite);
    }

    public function has(int $productId): bool
    {
        $favorite = $this->current();

        return $favorite !== null && $this->favorites->has($favorite, $productId);
    }

    /**
     * Add or remove one product.
     *
     * @return array{added: bool, count: int, limit_reached: bool}
     */
    public function toggle(int $productId): array
    {
        $favorite = $this->current(true);
        if ($favorite === null) {
            return ['added' => false, 'count' => 0, 'limit_reached' => false];
        }

        if ($this->favorites->has($favorite, $productId)) {
            $this->favorites->removeProduct($favorite, $productId);

            return ['added' => false, 'count' => $this->favorites->countItems($favorite), 'limit_reached' => false];
        }

        $max = max(1, (int) config('favorites.max_items', 60));
        if ($this->favorites->countItems($favorite) >= $max) {
            return ['added' => false, 'count' => $this->favorites->countItems($favorite), 'limit_reached' => true];
        }

        $this->favorites->addProduct($favorite, $productId);

        return ['added' => true, 'count' => $this->favorites->countItems($favorite), 'limit_reached' => false];
    }

    public function remove(int $productId): int
    {
        $favorite = $this->current();
        if ($favorite === null) {
            return 0;
        }

        $this->favorites->removeProduct($favorite, $productId);

        return $this->favorites->countItems($favorite);
    }

    public function clear(): void
    {
        $favorite = $this->current();
        if ($favorite !== null) {
            $this->favorites->clearItems($favorite);
        }
    }

    /**
     * Saved products translated for a locale, in the order they were saved
     * (newest first). Products that are no longer on sale drop out of the list.
     *
     * @return array<int, array<string, mixed>>
     */
    public function items(string $locale): array
    {
        $ids = $this->productIds();
        if ($ids === []) {
            return [];
        }

        /** @var Product[] $models */
        $models = Product::query()
            ->whereIn('id', $ids)
            ->where('status', 'active')
            ->get();

        $byId = [];
        foreach ($models as $model) {
            $byId[(int) $model->getKey()] = $model;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        if ($ordered === []) {
            return [];
        }

        Product::eagerLoad($ordered);

        $items = [];
        foreach ($ordered as $index => $model) {
            $translated = $model->translate($locale);
            $translated['fav_index'] = $index + 1;
            $translated['fav_url'] = route('products.show', ['slug' => (string) ($translated['slug'] ?? $model->slug)]);
            $items[] = $translated;
        }

        return $items;
    }

    /** Public link that opens the list anywhere (used in the e-mail). */
    public function shareUrl(Favorite $favorite): string
    {
        return route('favorites.claim', ['token' => (string) $favorite->token]);
    }

    /**
     * Stamp the visitor's cookie on a response so the list follows the browser.
     * Called by every action that may have created (or adopted) a list.
     */
    public function attachCookie(Response $response, ?Favorite $favorite = null): Response
    {
        $favorite ??= $this->current();
        if ($favorite === null) {
            return $response;
        }

        $name = $this->cookieName();
        $cookie = (string) request()->cookie($name, '');
        if ($cookie === (string) $favorite->token) {
            return $response;
        }

        return $response->header('Set-Cookie', $this->cookieHeader($name, (string) $favorite->token));
    }

    private function cookieHeader(string $name, string $token): string
    {
        $days = max(1, (int) config('favorites.cookie_days', 365));
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

    private function create(): Favorite
    {
        $favorite = Favorite::create([
            'token' => bin2hex(random_bytes(32)),
            'locale' => $this->translator->getLocale(),
            'user_id' => auth()->check() ? (int) (auth()->id() ?? 0) ?: null : null,
            'ip_hash' => $this->ipHash(),
            'user_agent' => mb_substr(request()->userAgent(), 0, 250),
        ]);

        return $this->favorite = $favorite;
    }

    /** IP addresses are never stored in the clear. */
    private function ipHash(): string
    {
        $ip = request()->ip();
        if ($ip === '') {
            return '';
        }

        return hash_hmac('sha256', $ip, (string) config('app.key', 'lufly'));
    }
}
