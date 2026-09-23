<?php

declare(strict_types=1);

namespace Modules\Planner\Controllers;

use App\Http\Controllers\Controller;
use App\Services\MailService;
use App\Services\SEOService;
use Core\Http\ApiResponse;
use Core\Http\JsonResponse;
use Core\Http\Request;
use Core\Http\Response;
use Core\Localization\Translator;
use Core\View\View;
use Modules\Planner\Services\PlannerService;

/**
 * The bathroom planner — three taps, then a plan.
 *
 * GET  /{locale}/planner          the chat
 * POST /{locale}/planner/step     one answer: size, wet area or look
 * POST /{locale}/planner/fit      "does the product I was looking at fit?"
 * POST /{locale}/planner/picture  the optional picture of the finished room
 * POST /{locale}/planner/send     hand the plan to the LUFLY team
 *
 * The chat talks in fragments: every answer comes back as the HTML the page
 * appends (bubble + the next choice), so the wording stays in PHP and the
 * browser only has to place nodes.
 */
class PlannerController extends Controller
{
    /** Answers a visitor may send back, in flow order. */
    private const STEPS = ['size', 'custom', 'wet', 'look'];

    public function __construct(
        private readonly PlannerService $planner,
        private readonly SEOService $seo,
        private readonly Translator $translator,
        private readonly MailService $mail,
    ) {
    }

    public function index(Request $request): Response
    {
        $locale = $this->translator->getLocale();

        /* promised, not delivered yet: the page says so and points at the finder */
        if ((bool) config('planner.coming_soon', true)) {
            $this->seo->setTitle(trans('planner.soon_title'));
            $this->seo->setDescription(trans('planner.soon_text'));
            $this->seo->setCanonical(route('planner.index'));

            return $this->view('planner::soon', [
                'title' => trans('planner.soon_title'),
                'locale' => $locale,
                /* ?chat=1 opens the finder panel the moment the page loads */
                'finderUrl' => route('products.index', ['chat' => 1]),
            ]);
        }

        $this->seo->setTitle(trans('planner.title'));
        $this->seo->setDescription(trans('planner.meta_description'));
        $this->seo->setCanonical(route('planner.index'));

        return $this->view('planner::index', [
            'title' => trans('planner.title'),
            'locale' => $locale,
            'answers' => $this->planner->answers([]),
            'waiting' => $this->planner->waitingMessages($locale),
            'endpoints' => [
                'step' => route('planner.step'),
                'fit' => route('planner.fit'),
                'render' => route('planner.render'),
                'send' => route('planner.send'),
            ],
            'seo' => $this->seo,
        ]);
    }

    /** One tap: answer the current question and get the next one (or the plan). */
    public function step(Request $request): JsonResponse
    {
        $locale = $this->translator->getLocale();
        $step = (string) $request->input('step', '');
        $answered = $this->answeredFrom($request);

        if (!in_array($step, self::STEPS, true)) {
            return ApiResponse::error(trans('planner.err_size'), [], 422);
        }

        if ($step === 'custom') {
            $w = (int) $request->input('w', 0);
            $l = (int) $request->input('l', 0);
            $limits = (array) config('planner.custom', []);

            if ($w < (int) ($limits['min'] ?? 100) || $l < (int) ($limits['min'] ?? 100)
                || $w > (int) ($limits['max'] ?? 600) || $l > (int) ($limits['max'] ?? 600)) {
                return ApiResponse::error(trans('planner.err_custom'), ['w' => [trans('planner.err_custom')]], 422);
            }
        }

        $answers = $this->planner->answers($request->all());
        $answered[] = $step;
        $answered = array_values(array_unique($answered));
        $next = $this->planner->step($answers, $answered);

        $data = [
            'step' => $next,
            'echo' => $this->echoLine($step, $answers, $locale),
            'html' => $next === 'plan' ? '' : $this->question($next, $answers, $locale, $this->progress($answered, $next)),
            'waiting' => $this->planner->waitingMessages($locale),
            'progress' => $this->progress($answered, $next),
            'plan_html' => '',
        ];

        if ($next === 'plan') {
            $plan = $this->planner->plan($answers, $locale);
            $intro = $this->planner->intro($answers, $plan, $locale);

            $data['plan_html'] = $this->fragment(
                'planner::partials.plan',
                $this->planData($plan, $locale, $this->contextFrom($request)),
            );
            $data['intro'] = $intro;
            $data['plan_text'] = (string) $plan['plan_text'];
        }

        return ApiResponse::success($data);
    }

    /**
     * "Does the piece I was looking at fit my plan?" — the floating chat asks
     * this on a product page.
     *
     * The product arrives as text (name, description, category), so the model
     * is never handed an image and never has to read the catalogue. Nothing is
     * stored: the same request answers the same question from the cache.
     */
    public function fit(Request $request): JsonResponse
    {
        $locale = $this->translator->getLocale();
        $context = $this->contextFrom($request);

        if ($context === null) {
            return ApiResponse::error(trans('planner.err_fit'), [], 422);
        }

        $answers = $this->planner->answers($request->all());
        $plan = $this->planner->plan($answers, $locale);
        $result = $this->planner->fit($plan, $context, $locale);
        $text = trim((string) ($result['text'] ?? ''));

        if ($text === '') {
            return ApiResponse::error(trans('planner.err_fit'), [], 503);
        }

        return ApiResponse::success([
            'text' => $text,
            'source' => (string) ($result['source'] ?? 'local'),
            'product' => $context['name'],
            'fit' => (string) ($result['fit'] ?? ''),
            'state' => (string) ($result['state'] ?? ''),
        ]);
    }

    /** The optional picture: one image generation, only when asked for. */
    public function render(Request $request): JsonResponse
    {
        $locale = $this->translator->getLocale();
        $answers = $this->planner->answers($request->all());
        $plan = $this->planner->plan($answers, $locale);

        $result = $this->planner->render($answers, $plan, $locale);

        if (!($result['ok'] ?? false)) {
            return ApiResponse::error(
                (string) ($result['error'] ?? trans('planner.render_failed')),
                [],
                503,
            );
        }

        return ApiResponse::success([
            'src' => (string) $result['src'],
            'alt' => trans('planner.render_title'),
            'model' => (string) ($result['model'] ?? ''),
        ]);
    }

    /**
     * Hand the plan to the team. The plan travels from the visitor's answers,
     * never from the text the browser sends, so what support reads is exactly
     * what the drawing shows.
     */
    public function send(Request $request): JsonResponse
    {
        $locale = $this->translator->getLocale();
        $email = mb_strtolower(trim((string) $request->input('email', '')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ApiResponse::error(trans('planner.err_email'), ['email' => [trans('planner.err_email')]], 422);
        }

        $answers = $this->planner->answers($request->all());
        $plan = $this->planner->plan($answers, $locale);
        $to = (string) config('planner.handoff.email', 'info@lufly.tr');

        $body = trans('planner.handoff_mail_body', [
            'email' => $email,
            'plan' => (string) $plan['plan_text'],
        ]);

        /* the chat on a product page is asked "does this fit?" — support should
           know which piece the visitor meant, in words they can search for */
        $context = $this->contextFrom($request);

        if ($context !== null) {
            $looking = $context['url'] !== ''
                ? $context['name'] . ' — ' . $context['url']
                : $context['name'];

            $body .= "\n\n" . trans('planner.fit_mail_line', ['name' => $looking]);
        }

        try {
            $sent = $this->mail->send(
                $to,
                trans('planner.handoff_subject'),
                $body,
                ['Reply-To' => $email, 'X-LUFLY-Source' => 'bathroom-planner'],
            );
        } catch (\Throwable $e) {
            logger('planner.handoff_failed', ['message' => $e->getMessage()], 'warning', 'app');

            return ApiResponse::error(trans('planner.err_send'), [], 502);
        }

        if (!$sent) {
            return ApiResponse::error(
                trans('planner.err_send'),
                ['email' => [$this->mail->lastError() ?? trans('planner.err_send')]],
                502,
            );
        }

        return ApiResponse::success([
            'sent' => true,
            'to' => $to,
            'message' => trans('planner.handoff_sent', ['email' => $email]),
        ], trans('planner.handoff_sent', ['email' => $email]));
    }

    /* ------------------------------------------------------------- helpers */

    /**
     * What the visitor just chose, as a sentence in the chat: the answer, not
     * the question.
     */
    private function echoLine(string $step, array $answers, string $locale): string
    {
        return match ($step) {
            'size' => $answers['size'] === 'custom'
                ? trans('planner.size_custom')
                : trans('planner.size_' . $answers['size']),
            'custom' => trans('planner.room_value', [
                'w' => (string) $answers['room']['w'],
                'l' => (string) $answers['room']['l'],
                'area' => (string) $answers['area'],
            ]),
            'wet' => trans('planner.wet_' . $answers['wet']),
            'look' => trans('planner.look_' . $answers['look']),
            default => '',
        };
    }

    /** The next question with its choices. */
    private function question(string $step, array $answers, string $locale, int $progress): string
    {
        return $this->fragment('planner::partials.question', [
            'step' => $step,
            'answers' => $answers,
            'locale' => $locale,
            'progress' => $progress,
        ]);
    }

    /** A view rendered on its own, for the fragments the chat appends. */
    private function fragment(string $template, array $data): string
    {
        return app(View::class)->render($template, $data);
    }

    /**
     * How far the visitor has walked. Typing their own size is still question
     * one, so the counter does not move for it.
     *
     * @param array<int, string> $answered
     */
    private function progress(array $answered, string $next): int
    {
        if ($next === 'custom') {
            return 1;
        }

        return min(3, count(array_diff($answered, ['custom'])) + 1);
    }

    /**
     * Everything the plan fragment needs. `renderSoon` is what the free Google
     * accounts can do today: the picture is announced, not offered.
     *
     * @param array<string, mixed> $plan
     * @param array{id: int, name: string, text: string, category: string, url: string}|null $context
     * @return array<string, mixed>
     */
    private function planData(array $plan, string $locale, ?array $context = null): array
    {
        $answers = (array) $plan['answers'];
        $renderOn = (bool) feature('render', true)
            && (bool) config('planner.render.enabled', true)
            && trim((string) config('ai.image_model', '')) !== '';

        return [
            'plan' => $plan,
            'answers' => $answers,
            'handoff' => $this->planner->handoff($plan, $locale),
            'renderEnabled' => $renderOn && !(bool) config('planner.render.soon', true),
            'renderSoon' => $renderOn && (bool) config('planner.render.soon', true),
            'fit' => $this->fitSection($context),
            'locale' => $locale,
        ];
    }

    /**
     * The "does it fit?" block: only when the chat was opened on a product page
     * and only while the feature is on. Everything it shows is text.
     *
     * @param array{id: int, name: string, text: string, category: string, url: string}|null $context
     * @return array{name: string, hint: string}|null
     */
    private function fitSection(?array $context): ?array
    {
        if ($context === null || !(bool) config('planner.fit.enabled', true)) {
            return null;
        }

        return [
            'name' => (string) $context['name'],
            'hint' => (string) trans('planner.fit_hint'),
        ];
    }

    /**
     * The product the visitor was looking at, as *text*: name, description,
     * category, link. Pictures never travel with it — a few hundred characters
     * answer the question, an image neither adds an answer nor survives the
     * cost of sending the catalogue.
     *
     * @return array{id: int, name: string, text: string, category: string, url: string}|null
     */
    private function contextFrom(Request $request): ?array
    {
        $name = $this->cleanText($request->input('product_name', ''), 120);

        /* no product on this page (the chat also floats over every other page) */
        if ($name === '') {
            return null;
        }

        return [
            'id' => max(0, (int) $request->input('product_id', 0)),
            'name' => $name,
            'text' => $this->cleanText(
                $request->input('product_text', ''),
                (int) config('planner.fit.text_chars', 600),
            ),
            'category' => $this->cleanText($request->input('product_category', ''), 60),
            'url' => $this->cleanText($request->input('product_url', ''), 300),
        ];
    }

    /** One line of product text, safe to hand to a model and to echo back. */
    private function cleanText(mixed $value, int $limit): string
    {
        $value = (string) preg_replace('/\s+/u', ' ', strip_tags((string) $value)) ?? '';

        return mb_substr(trim($value), 0, max(1, $limit));
    }

    /**
     * The steps the visitor has already answered, as the page sends them.
     *
     * @return array<int, string>
     */
    private function answeredFrom(Request $request): array
    {
        $raw = $request->input('answered', []);
        $raw = is_array($raw) ? $raw : explode(',', (string) $raw);

        return array_values(array_intersect(
            array_map(static fn (mixed $value): string => trim((string) $value), $raw),
            self::STEPS,
        ));
    }
}
