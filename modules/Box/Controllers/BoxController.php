<?php

declare(strict_types=1);

namespace Modules\Box\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SEOService;
use Core\Http\ApiResponse;
use Core\Http\Request;
use Core\Http\Response;
use Core\Localization\Translator;
use Modules\Box\Models\Box;
use Modules\Box\Services\BoxMailer;
use Modules\Box\Services\BoxService;
use Modules\Products\Models\Product;

class BoxController extends Controller
{
    public function __construct(
        private readonly BoxService $box,
        private readonly BoxMailer $mailer,
        private readonly SEOService $seo,
        private readonly Translator $translator,
    ) {
    }

    public function index(Request $request): Response
    {
        $token = trim((string) $request->query('t', ''));

        if ($token !== '') {
            $box = $this->box->findByToken($token);

            if ($box !== null) {
                $this->box->adopt($box);
            }
        }

        return $this->page($this->box->current());
    }

    public function claim(Request $request, string $token): Response
    {
        $box = $this->box->findByToken($token);

        if ($box === null) {
            abort(404, trans('box.not_found'));
        }

        $this->box->adopt($box);

        return $this->page($box);
    }

    public function add(Request $request): Response
    {
        $this->adoptToken($request);

        $product = $this->product($request);

        if ($product === null) {
            return ApiResponse::error(trans('box.err_product'), ['product_id' => [trans('box.err_product')]], 422);
        }

        $result = $this->box->toggle((int) $product->getKey());

        if ($result['limit_reached']) {
            $limit = max(1, (int) config('box.max_items', 40));

            return ApiResponse::error(
                trans('box.limit_reached', ['n' => (string) $limit]),
                ['product_id' => [trans('box.limit_reached', ['n' => (string) $limit])]],
                422,
            );
        }

        $locale = $this->translator->getLocale();
        $name = (string) ($product->translate($locale)['name'] ?? '');
        $message = $result['added']
            ? trans('box.added_named', ['name' => $name])
            : trans('box.removed_named', ['name' => $name]);

        $notified = $result['added'] && $this->teamShouldHear($locale);

        $response = ApiResponse::success([
            'product_id' => (int) $product->getKey(),
            'added' => $result['added'],
            'count' => $result['count'],
            'token' => $this->box->token(),
            'message' => $message,
            'notified' => $notified,
        ], $message);

        if ($notified) {
            $response->defer(fn (): bool => $this->tellTheTeam($locale));
        }

        return $this->box->attachCookie($response);
    }

    public function remove(Request $request): Response
    {
        $this->adoptToken($request);

        $productId = (int) $request->input('product_id', 0);

        if ($productId <= 0) {
            return ApiResponse::error(trans('box.err_product'), ['product_id' => [trans('box.err_product')]], 422);
        }

        $count = $this->box->remove($productId);
        $message = trans('box.removed');

        $response = ApiResponse::success([
            'count' => $count,
            'token' => $this->box->token(),
            'message' => $message,
        ], $message);

        return $this->box->attachCookie($response);
    }

    public function clear(Request $request): Response
    {
        $this->adoptToken($request);

        $this->box->clear();

        $message = trans('box.cleared');
        $response = ApiResponse::success(['count' => 0, 'token' => '', 'message' => $message], $message);

        return $this->box->attachCookie($response);
    }

    public function send(Request $request): Response
    {
        $this->adoptToken($request);

        $email = mb_strtolower(trim((string) $request->input('email', '')));
        $note = trim((string) preg_replace('/\s+/u', ' ', (string) $request->input('note', '')));
        $note = mb_substr($note, 0, max(60, (int) config('box.max_note', 600)));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ApiResponse::error(trans('box.err_email'), ['email' => [trans('box.err_email')]], 422);
        }

        $box = $this->box->current();
        $items = $this->box->items($this->translator->getLocale());

        if ($box === null || $items === []) {
            return ApiResponse::error(trans('box.err_empty'), ['email' => [trans('box.err_empty')]], 422);
        }

        $blocked = $this->throttle($box);

        if ($blocked !== null) {
            return ApiResponse::error($blocked, ['email' => [$blocked]], 429);
        }

        $result = $this->mailer->send($box, $items, $this->translator->getLocale(), $email, $note);

        if (!$result['sent']) {
            return ApiResponse::error(
                trans('box.err_send'),
                ['email' => [$result['error'] ?? trans('box.err_send')]],
                502,
            );
        }

        $box->update([
            'email' => $email,
            'note' => $note !== '' ? $note : $box->note,
            'sends' => (int) $box->sends + 1,
            'sends_today' => ($this->sameDay($box) ? (int) $box->sends_today : 0) + 1,
            'last_sent_at' => date('Y-m-d H:i:s'),
        ]);

        $message = trans('box.sent', ['email' => $email]);
        $response = ApiResponse::success([
            'sent' => true,
            'copy' => $result['copy'],
            'count' => count($items),
            'email' => $email,
            'token' => (string) $box->token,
            'message' => $message,
        ], $message);

        return $this->box->attachCookie($response, $box);
    }

    private function page(?Box $box): Response
    {
        $locale = $this->translator->getLocale();
        $items = $box === null ? [] : $this->boxServiceItems($box, $locale);
        $title = trans('box.title');

        $this->seo->setTitle($title);
        $this->seo->setDescription(trans('box.meta_description'));
        $this->seo->setCanonical(route('box.index'));
        $this->seo->setRobots('noindex, nofollow');

        $response = $this->view('box::index', [
            'title' => $title,
            'locale' => $locale,
            'items' => $items,
            'count' => count($items),
            'box' => $box,
            'shareUrl' => $box !== null ? $this->box->shareUrl($box) : '',
            'maxItems' => max(1, (int) config('box.max_items', 40)),
            'shopMail' => (string) config('box.mail', 'info@lufly.tr'),
            'seo' => $this->seo,
        ]);

        return $this->box->attachCookie($response, $box);
    }

    private function boxServiceItems(Box $box, string $locale): array
    {
        return $this->box->items($locale);
    }

    private function adoptToken(Request $request): void
    {
        if ($this->box->current() !== null) {
            return;
        }

        $token = trim((string) $request->input('token', ''));

        if ($token === '' || !preg_match('/^[A-Za-z0-9]{16,64}$/', $token)) {
            return;
        }

        $box = $this->box->findByToken($token);

        if ($box !== null) {
            $this->box->adopt($box);
        }
    }

    private function product(Request $request): ?Product
    {
        $productId = (int) $request->input('product_id', 0);

        if ($productId <= 0) {
            return null;
        }

        $product = Product::query()->where('id', $productId)->where('status', 'active')->first();

        return $product instanceof Product ? $product : null;
    }

    private function sameDay(Box $box): bool
    {
        return $box->last_sent_at !== null && str_starts_with((string) $box->last_sent_at, date('Y-m-d'));
    }

    private function teamShouldHear(string $locale): bool
    {
        if (!(bool) config('box.saved_notice', true)) {
            return false;
        }

        $box = $this->box->current();

        if ($box === null) {
            return false;
        }

        $cooldown = max(0, (int) config('box.saved_cooldown', 300));
        $last = $box->notified_at !== null ? strtotime((string) $box->notified_at) : null;

        if ($cooldown > 0 && $last !== null && (time() - $last) < $cooldown) {
            return false;
        }

        if ($this->box->items($locale) === []) {
            return false;
        }

        $box->update(['notified_at' => date('Y-m-d H:i:s')]);

        return true;
    }

    private function tellTheTeam(string $locale): bool
    {
        $box = $this->box->current();

        if ($box === null) {
            return false;
        }

        $items = $this->box->items($locale);

        if ($items === []) {
            return false;
        }

        $result = $this->mailer->saved($box, $items, $locale, (string) ($box->email ?? ''));

        return (bool) $result['sent'];
    }

    private function throttle(Box $box): ?string
    {
        $cooldown = max(0, (int) config('box.send_cooldown', 60));
        $last = $box->last_sent_at !== null ? strtotime((string) $box->last_sent_at) : null;

        if ($cooldown > 0 && $last !== null && (time() - $last) < $cooldown) {
            return trans('box.err_cooldown', ['s' => (string) max(1, $cooldown - (time() - $last))]);
        }

        $limit = max(1, (int) config('box.send_daily_limit', 5));

        if ($this->sameDay($box) && (int) $box->sends_today >= $limit) {
            return trans('box.err_daily');
        }

        return null;
    }
}
