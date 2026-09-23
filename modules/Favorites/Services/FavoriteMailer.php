<?php

declare(strict_types=1);

namespace Modules\Favorites\Services;

use App\Services\MailService;
use Core\Localization\Translator;
use Core\View\View;
use Modules\Favorites\Models\Favorite;

/**
 * The e-mailed copy of a saved list.
 *
 * Two messages leave the store every time a visitor asks for their list:
 *
 *   1. the visitor's own copy — one row per saved product with a button that
 *      opens that exact product page, plus the permanent link back to the list
 *   2. the store inbox copy (config favorites.notify_admin) — the same list with
 *      the visitor's address as Reply-To, so the message can be answered as a
 *      quotation request
 *
 * Both are rendered from the module's e-mail views, so the wording lives in the
 * translation files next to the rest of the UI text.
 */
class FavoriteMailer
{
    public function __construct(
        private readonly MailService $mail,
        private readonly View $view,
        private readonly Translator $translator,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $items translated products (see FavoriteService::items)
     * @return array{sent: bool, admin_sent: bool, error: ?string}
     */
    public function send(Favorite $favorite, array $items, string $locale, string $email): array
    {
        $shareUrl = route('favorites.claim', ['token' => (string) $favorite->token]);
        $adminAddress = trim((string) config('mail.admin_address', ''))
            ?: trim((string) config('mail.from_address', 'info@lufly.tr'));
        $data = [
            'items' => $items,
            'count' => count($items),
            'listUrl' => $shareUrl,
            'email' => $email,
            'locale' => $locale,
            'favorite' => $favorite,
        ];

        $visitorHtml = $this->view->renderFile(
            $this->view->resolvePath('favorites::emails.list'),
            $data,
        );
        $visitorText = $this->view->renderFile(
            $this->view->resolvePath('favorites::emails.list-text'),
            $data,
        );

        $subject = $this->translator->trans('favorites.mail_subject' . (count($items) === 1 ? '_one' : '_many'), ['n' => (string) count($items)], $locale);

        $sent = $this->mail->sendHtml(
            $email,
            $subject,
            $visitorHtml,
            $visitorText,
            [
                // "Simply reply to this e-mail" — the reply has to reach the store
                'Reply-To' => $adminAddress,
                'X-LUFLY-Message' => 'favorites-list',
                'List-Unsubscribe' => '<mailto:' . $adminAddress . '?subject=unsubscribe>',
            ],
        );

        $adminSent = false;
        if ((bool) config('favorites.notify_admin', true) && filter_var($adminAddress, FILTER_VALIDATE_EMAIL)) {
            $adminSubject = $this->translator->trans(
                'favorites.mail_admin_subject' . (count($items) === 1 ? '_one' : '_many'),
                ['email' => $email, 'n' => (string) count($items)],
                $locale,
            );

            $adminHtml = $this->view->renderFile(
                $this->view->resolvePath('favorites::emails.admin'),
                $data + ['adminSubject' => $adminSubject],
            );

            $adminSent = $this->mail->sendHtml(
                $adminAddress,
                $adminSubject,
                $adminHtml,
                '',
                [
                    'Reply-To' => $email,
                    'X-LUFLY-Message' => 'favorites-lead',
                ],
            );
        }

        return [
            'sent' => $sent,
            'admin_sent' => $adminSent,
            'error' => $sent ? null : $this->mail->lastError(),
        ];
    }
}
