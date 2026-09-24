<?php

declare(strict_types=1);

namespace Modules\Box\Services;

use App\Services\MailService;
use Core\Localization\Translator;
use Core\View\View;
use Modules\Box\Models\Box;

class BoxMailer
{
    public function __construct(
        private readonly MailService $mail,
        private readonly View $view,
        private readonly Translator $translator,
    ) {
    }

    public function send(Box $box, array $items, string $locale, string $email, string $note): array
    {
        $shop = trim((string) config('box.mail', ''));
        $shop = $shop !== '' ? $shop : trim((string) config('mail.from_address', 'info@lufly.tr'));

        if (!filter_var($shop, FILTER_VALIDATE_EMAIL)) {
            return ['sent' => false, 'copy' => false, 'error' => 'box.mail is not an address'];
        }

        $data = [
            'items' => $items,
            'count' => count($items),
            'boxUrl' => route('box.claim', ['token' => (string) $box->token]),
            'email' => $email,
            'note' => $note,
            'locale' => $locale,
            'box' => $box,
            'site' => url('/'),
            'phone' => (string) config('planner.handoff.phone', ''),
        ];

        $html = $this->view->renderFile($this->view->resolvePath('box::emails.box'), $data + ['audience' => 'admin']);
        $text = $this->view->renderFile($this->view->resolvePath('box::emails.box-text'), $data + ['audience' => 'admin']);

        $subject = $this->translator->trans(
            'box.mail_admin_subject' . (count($items) === 1 ? '_one' : '_many'),
            ['n' => (string) count($items)],
            $locale,
        );

        $headers = ['X-LUFLY-Message' => 'box-request'];

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $headers['Reply-To'] = $email;
        }

        $sent = $this->mail->sendHtml($shop, $subject, $html, $text, $headers);

        $copy = false;

        if ($sent && (bool) config('box.copy_visitor', true) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $visitorHtml = $this->view->renderFile($this->view->resolvePath('box::emails.box'), $data + ['audience' => 'visitor']);
            $visitorText = $this->view->renderFile($this->view->resolvePath('box::emails.box-text'), $data + ['audience' => 'visitor']);

            $copy = $this->mail->sendHtml(
                $email,
                $this->translator->trans(
                    'box.mail_copy_subject' . (count($items) === 1 ? '_one' : '_many'),
                    ['n' => (string) count($items)],
                    $locale,
                ),
                $visitorHtml,
                $visitorText,
                [
                    'Reply-To' => $shop,
                    'X-LUFLY-Message' => 'box-request-copy',
                ],
            );
        }

        return ['sent' => $sent, 'copy' => $copy, 'error' => $sent ? null : $this->mail->lastError()];
    }

    public function saved(Box $box, array $items, string $locale, string $email = ''): array
    {
        $shop = trim((string) config('box.mail', ''));
        $shop = $shop !== '' ? $shop : trim((string) config('mail.from_address', 'info@lufly.tr'));

        if (!filter_var($shop, FILTER_VALIDATE_EMAIL) || $items === []) {
            return ['sent' => false, 'error' => 'nothing to tell'];
        }

        $data = [
            'items' => $items,
            'count' => count($items),
            'boxUrl' => route('box.claim', ['token' => (string) $box->token]),
            'email' => $email,
            'note' => '',
            'locale' => $locale,
            'box' => $box,
            'site' => url('/'),
            'phone' => (string) config('planner.handoff.phone', ''),
            'audience' => 'saved',
        ];

        $html = $this->view->renderFile($this->view->resolvePath('box::emails.box'), $data);
        $text = $this->view->renderFile($this->view->resolvePath('box::emails.box-text'), $data);

        $subject = $this->translator->trans(
            'box.mail_saved_subject' . (count($items) === 1 ? '_one' : '_many'),
            ['n' => (string) count($items)],
            $locale,
        );

        $headers = ['X-LUFLY-Message' => 'box-saved'];

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $headers['Reply-To'] = $email;
        }

        $sent = $this->mail->sendHtml($shop, $subject, $html, $text, $headers);

        return ['sent' => $sent, 'error' => $sent ? null : $this->mail->lastError()];
    }
}
