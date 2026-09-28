<?php

declare(strict_types=1);

namespace Modules\Assistant\Controllers;

use App\Http\Controllers\Controller;
use Core\Http\ApiResponse;
use Core\Http\JsonResponse;
use Core\Http\Request;
use Core\Localization\Translator;
use Modules\Assistant\Services\AssistantService;

/**
 * The finder — "I am looking for something like this".
 *
 * POST /{locale}/assistant/ask    a description, a photo, or both
 * POST /{locale}/assistant/email  the address the gate asked for, saved at once
 *
 * One endpoint per thing the visitor does: ask() is the question (the browser
 * sends the words — or a tap on one of the choices the chat offered — and,
 * when there is one, the picture); email() is the gate, so the moment the
 * visitor presses "start" the address is validated and kept on our side, and
 * the chat can honestly say it was.
 */
class AssistantController extends Controller
{
    public function __construct(
        private readonly AssistantService $assistant,
        private readonly Translator $translator,
    ) {
    }

    /** What the chat says while it works (the widget prints it as data). */
    public function waiting(string $locale): array
    {
        return $this->assistant->waiting($locale);
    }

    public function ask(Request $request): JsonResponse
    {
        $locale = $this->translator->getLocale();

        if (!$this->assistant->enabled()) {
            return ApiResponse::error(trans('assistant.off'), [], 404);
        }

        $question = trim((string) $request->input('q', ''));
        $email = mb_strtolower(trim((string) $request->input('email', '')));
        $productId = (int) $request->input('product_id', 0);
        /* a tap on one of the choices the chat offered, and the tiny bit of state
           the browser carries between two turns of the conversation */
        $choice = trim((string) $request->input('choice', ''));
        $thread = json_decode((string) $request->input('thread', ''), true);
        $photo = null;

        if ((string) $request->input('photo_data', '') !== '') {
            if (!$this->assistant->photosEnabled()) {
                return ApiResponse::error(trans('assistant.photo_off'), [], 403);
            }

            $photo = [
                'name' => (string) $request->input('photo_name', ''),
                'mime' => (string) $request->input('photo_mime', ''),
                'data' => (string) $request->input('photo_data', ''),
            ];
        }

        $result = $this->assistant->ask([
            'q' => $question,
            'email' => $email,
            'photo' => $photo,
            'product_id' => $productId,
            'choice' => $choice,
            'thread' => is_array($thread) ? $thread : [],
        ], $locale);

        if (!($result['ok'] ?? false)) {
            $reason = (string) ($result['reason'] ?? 'error');

            return ApiResponse::error(
                (string) ($result['text'] ?? trans('assistant.err')),
                [],
                $reason === 'limit' ? 429 : ($reason === 'empty' ? 422 : 503),
            );
        }

        return ApiResponse::success($result);
    }

    /**
     * The gate: the address, saved the moment the visitor presses start.
     *
     * The answer is spoken in the visitor's language, because it is printed
     * under the field it belongs to.
     */
    public function email(Request $request): JsonResponse
    {
        if (!$this->assistant->enabled()) {
            return ApiResponse::error(trans('assistant.off'), [], 404);
        }

        $result = $this->assistant->registerEmail(
            (string) $request->input('email', ''),
            $this->translator->getLocale(),
        );

        if (!($result['ok'] ?? false)) {
            $reason = (string) ($result['reason'] ?? 'error');

            return ApiResponse::error(
                (string) ($result['text'] ?? trans('assistant.err')),
                [],
                $reason === 'limit' ? 429 : ($reason === 'invalid' ? 422 : 503),
            );
        }

        return ApiResponse::success($result, (string) ($result['text'] ?? ''));
    }

}
