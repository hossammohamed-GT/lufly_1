<?php

declare(strict_types=1);

namespace Modules\Assistant\Controllers;

use App\Http\Controllers\Controller;
use Core\Http\ApiResponse;
use Core\Http\JsonResponse;
use Core\Http\Request;
use Core\Localization\Translator;
use Modules\Assistant\Services\AssistantService;

class AssistantController extends Controller
{
    public function __construct(
        private readonly AssistantService $assistant,
        private readonly Translator $translator,
    ) {
    }

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

}
