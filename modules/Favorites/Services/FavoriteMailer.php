<?php

declare(strict_types=1);

namespace Modules\Favorites\Services;

use App\Services\MailService;
use Core\Localization\Translator;
use Core\View\View;
use Modules\Favorites\Models\Favorite;

class FavoriteMailer
{
    public function __construct(
        private readonly MailService $mail,
        private readonly View $view,
        private readonly Translator $translator,
    ) {
    }

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
