<?php

declare(strict_types=1);

namespace Modules\Languages\Controllers;

use App\Http\Controllers\Controller;
use App\Services\LocalizationService;
use Core\Http\RedirectResponse;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Session;

class LanguageController extends Controller
{
    public function __construct(
        private readonly LocalizationService $localization,
        private readonly Session $session,
    ) {
    }

    public function index(): Response
    {
        return $this->view('languages::Admin.index', [
            'title' => trans('common.languages'),
            'languages' => $this->localization->languages(),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $request->validate([
            'code' => 'required|string|max:10|unique:languages,code',
            'name' => 'required|string|max:50',
            'native_name' => 'required|string|max:50',
            'dir' => 'required|in:ltr,rtl',
            'active' => 'required|in:0,1',
            'sort_order' => 'nullable|integer',
        ]);

        $this->localization->storeLanguage($data);

        return $this->redirect(route('admin.languages.index'))
            ->with('_success', trans('common.saved'));
    }

    public function toggle(int $id): Response
    {
        $this->localization->toggleLanguage($id);

        return $this->redirect(route('admin.languages.index'))
            ->with('_success', trans('common.saved'));
    }

    public function switchLocale(string $code): RedirectResponse
    {
        $translator = app(\Core\Localization\Translator::class);
        if (!$translator->isSupported($code)) {
            $code = (string) config('localization.default', 'en');
        }

        $this->session->set('_locale', $code);
        $translator->setLocale($code);

        $referer = request()->header('HTTP_REFERER');
        if (is_string($referer) && $referer !== '') {
            $parts = parse_url($referer) ?: [];
            $path = (string) ($parts['path'] ?? '');
            $supportedCodes = array_keys($translator->supported());
            if (preg_match('#^/(' . implode('|', array_map('preg_quote', $supportedCodes)) . ')(?:/(.*))?$#', $path, $match) === 1) {
                $fromLocale = $match[1];
                $rest = trim((string) ($match[2] ?? ''), '/');
                $newRest = $rest;
                if ($rest !== '') {
                    $segments = explode('/', $rest);
                    $firstSeg = $segments[0];
                    $routeKeys = ['products.index', 'contact', 'central-vacuum', 'favorites.index', 'box.index', 'planner.index', 'login'];
                    foreach ($routeKeys as $rk) {
                        if ($translator->trans('routes.' . $rk, [], $fromLocale) === $firstSeg) {
                            $segments[0] = $translator->trans('routes.' . $rk, [], $code);
                            $newRest = implode('/', $segments);
                            break;
                        }
                    }
                }
                $rebuilt = url('/' . $code . ($newRest !== '' ? '/' . $newRest : ''));
                if (!empty($parts['query'])) {
                    $rebuilt .= '?' . $parts['query'];
                }
                if (!empty($parts['fragment'])) {
                    $rebuilt .= '#' . $parts['fragment'];
                }

                return new RedirectResponse($rebuilt);
            }

            return new RedirectResponse($referer);
        }

        return new RedirectResponse(url('/' . $code));
    }
}
