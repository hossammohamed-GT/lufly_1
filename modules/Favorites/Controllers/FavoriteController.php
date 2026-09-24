<?php

declare(strict_types=1);

namespace Modules\Favorites\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SEOService;
use Core\Http\ApiResponse;
use Core\Http\RedirectResponse;
use Core\Http\Request;
use Core\Http\Response;
use Core\Localization\Translator;
use Modules\Favorites\Models\Favorite;
use Modules\Favorites\Services\FavoriteMailer;
use Modules\Favorites\Services\FavoriteService;
use Modules\Products\Models\Product;

class FavoriteController extends Controller
{
    private const AUTO_MAIL_COOLDOWN = 20;

    public function __construct(
        private readonly FavoriteService $favorites,
        private readonly FavoriteMailer $mailer,
        private readonly SEOService $seo,
        private readonly Translator $translator,
    ) {
    }

    public function index(Request $request): Response
    {
        $token = trim((string) $request->query('t', ''));
        if ($token !== '') {
            $favorite = $this->favorites->findByToken($token);
            if ($favorite !== null) {
                $this->favorites->adopt($favorite);
            }
        }

        return $this->page($request, $this->favorites->current());
    }

    public function claim(Request $request, string $token): Response
    {
        $favorite = $this->favorites->findByToken($token);
        if ($favorite === null) {
            abort(404, trans('favorites.not_found'));
        }

        $this->favorites->adopt($favorite);

        return $this->page($request, $favorite);
    }

    public function toggle(Request $request): Response
    {
        $productId = (int) $request->input('product_id', 0);
        $product = $productId > 0
            ? Product::query()->where('id', $productId)->where('status', 'active')->first()
            : null;

        if (!$product instanceof Product) {
            return $this->fail($request, trans('favorites.err_product'), 422, ['product_id' => [trans('favorites.err_product')]]);
        }

        $result = $this->favorites->toggle($productId);

        if ($result['limit_reached']) {
            $limit = max(1, (int) config('favorites.max_items', 60));

            return $this->fail(
                $request,
                trans('favorites.limit_reached', ['n' => (string) $limit]),
                422,
                ['product_id' => [trans('favorites.limit_reached', ['n' => (string) $limit])]],
            );
        }

        $locale = $this->translator->getLocale();
        $name = (string) ($product->translate($locale)['name'] ?? '');
        $message = $result['added']
            ? trans('favorites.added_named', ['name' => $name])
            : trans('favorites.removed_named', ['name' => $name]);

        $mailed = $result['added'] ? $this->autoMail($locale) : '';

        $data = [
            'product_id' => $productId,
            'added' => $result['added'],
            'count' => $result['count'],
            'message' => $message,
            'mailed' => $mailed !== '',
            'mail_message' => $mailed,
        ];

        $response = ApiResponse::success($data, $message);

        $this->favorites->attachCookie($response);

        return $response;
    }

    public function email(Request $request): Response
    {
        $email = mb_strtolower(trim((string) $request->input('email', '')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->fail($request, trans('favorites.err_email'), 422, ['email' => [trans('favorites.err_email')]]);
        }

        $locale = $this->translator->getLocale();
        $favorite = $this->favorites->current();
        $items = $this->favorites->items($locale);

        if ($favorite === null || $items === []) {
            return $this->fail($request, trans('favorites.err_empty'), 422, ['email' => [trans('favorites.err_empty')]]);
        }

        $blocked = $this->throttle($favorite);
        if ($blocked !== null) {
            return $this->fail($request, $blocked, 429, ['email' => [$blocked]]);
        }

        $result = $this->mailer->send($favorite, $items, $locale, $email);

        if (!$result['sent']) {
            return $this->fail(
                $request,
                trans('favorites.err_send'),
                502,
                ['email' => [$result['error'] ?? trans('favorites.err_send')]],
            );
        }

        $notify = $request->input('notify');
        $this->stamp($favorite, $email, $locale, $notify !== null ? (bool) (int) $notify : null);

        $message = trans('favorites.mail_sent', ['email' => $email]);
        $response = ApiResponse::success([
            'sent' => true,
            'count' => count($items),
            'email' => $email,
            'notify' => $favorite->wantsUpdates(),
            'message' => $message,
        ], $message);

        $this->favorites->attachCookie($response, $favorite);

        return $response;
    }

    public function clear(Request $request): Response
    {
        $this->favorites->clear();

        $message = trans('favorites.cleared');
        $response = ApiResponse::success(['count' => 0, 'message' => $message], $message);

        $this->favorites->attachCookie($response);

        return $response;
    }

    private function page(Request $request, ?Favorite $favorite): Response
    {
        $locale = $this->translator->getLocale();
        $items = $this->favorites->items($locale);
        $count = count($items);

        $title = trans('favorites.title');

        $this->seo->setTitle($title);
        $this->seo->setDescription(trans('favorites.meta_description'));
        $this->seo->setCanonical(route('favorites.index'));
        $this->seo->setRobots('noindex, nofollow');

        $response = $this->view('favorites::index', [
            'title' => $title,
            'locale' => $locale,
            'items' => $items,
            'count' => $count,
            'favorite' => $favorite,
            'shareUrl' => $favorite !== null ? $this->favorites->shareUrl($favorite) : '',
            'savedIds' => array_map(static fn (array $item): int => (int) ($item['id'] ?? 0), $items),
            'maxItems' => max(1, (int) config('favorites.max_items', 60)),
            'notifyChoice' => $favorite?->notify,
            'seo' => $this->seo,
        ]);

        return $this->favorites->attachCookie($response, $favorite);
    }

    private function autoMail(string $locale): string
    {
        $favorite = $this->favorites->current();
        if ($favorite === null || !$favorite->wantsUpdates()) {
            return '';
        }

        $email = mb_strtolower(trim((string) $favorite->email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '';
        }

        if ($this->throttle($favorite, self::AUTO_MAIL_COOLDOWN) !== null) {
            return '';
        }

        $items = $this->favorites->items($locale);
        if ($items === []) {
            return '';
        }

        try {
            $result = $this->mailer->send($favorite, $items, $locale, $email);
        } catch (\Throwable $e) {
            logger('favorites.auto_mail_failed', ['message' => $e->getMessage()], 'warning', 'app');

            return '';
        }

        if (!$result['sent']) {
            return '';
        }

        $this->stamp($favorite, $email, $locale);

        return trans('favorites.mail_sent', ['email' => $email]);
    }

    private function stamp(Favorite $favorite, string $email, string $locale, ?bool $notify = null): void
    {
        if ($notify !== null) {
            $favorite->notify = $notify;
        }

        $today = date('Y-m-d');
        $sameDay = $favorite->last_emailed_at !== null
            && str_starts_with((string) $favorite->last_emailed_at, $today);

        $favorite->update([
            'email' => $email,
            'locale' => $locale,
            'notify' => $favorite->notify,
            'emails_sent' => (int) $favorite->emails_sent + 1,
            'emails_today' => ($sameDay ? (int) $favorite->emails_today : 0) + 1,
            'last_emailed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function throttle(Favorite $favorite, ?int $cooldown = null): ?string
    {
        $cooldown ??= max(0, (int) config('favorites.email_cooldown', 60));
        $cooldown = max(0, $cooldown);
        $last = $favorite->last_emailed_at !== null ? strtotime((string) $favorite->last_emailed_at) : null;

        if ($cooldown > 0 && $last !== null && (time() - $last) < $cooldown) {
            return trans('favorites.err_cooldown', ['s' => (string) max(1, $cooldown - (time() - $last))]);
        }

        $limit = max(1, (int) config('favorites.email_daily_limit', 5));
        $today = date('Y-m-d');
        $sameDay = $last !== null && str_starts_with((string) $favorite->last_emailed_at, $today);

        if ($sameDay && (int) $favorite->emails_today >= $limit) {
            return trans('favorites.err_daily_limit', ['n' => (string) $limit]);
        }

        return null;
    }

    private function fail(Request $request, string $message, int $status, array $errors = []): Response
    {
        if ($request->wantsJson()) {
            return ApiResponse::error($message, $errors, $status);
        }

        $redirect = back();

        return $errors !== [] ? $redirect->withErrors($errors) : $redirect->with('_errors', ['form' => [$message]]);
    }
}
