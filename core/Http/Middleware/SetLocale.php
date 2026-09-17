<?php

declare(strict_types=1);

namespace Core\Http\Middleware;

use Core\Contracts\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Session;
use Core\Localization\Translator;

final class SetLocale implements MiddlewareInterface
{
    public function __construct(
        private readonly Translator $translator,
        private readonly Session $session,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $locale = $request->locale();
        $hasLocalizedRoute = $request->route()?->localized === true
            && $this->translator->isSupported($locale);

        $requested = $request->query('locale') ?? $request->query('lang');
        if ($hasLocalizedRoute) {
            // An explicit /en/, /tr/ or /cs/ URL must override the session locale.
            $locale = (string) $request->locale();
        } elseif (is_string($requested) && $this->translator->isSupported($requested)) {
            $locale = $requested;
        } elseif ($locale === (string) config('localization.default', 'en')) {
            $stored = $this->session->get('_locale');
            if (is_string($stored) && $this->translator->isSupported($stored)) {
                $locale = $stored;
            }
        }

        if ($this->translator->isSupported($locale)) {
            $this->translator->setLocale($locale);
            $request->setLocale($locale);
            $this->session->set('_locale', $locale);
        }

        return $next($request);
    }
}
