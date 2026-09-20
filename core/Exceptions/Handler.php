<?php

declare(strict_types=1);

namespace Core\Exceptions;

use Core\Foundation\Application;
use Core\Http\JsonResponse;
use Core\Http\RedirectResponse;
use Core\Http\Request;
use Core\Http\Response;
use Core\Logging\Log;
use Core\View\View;
use ErrorException;
use Throwable;

final class Handler
{
    private static ?Application $app = null;

    public static function register(Application $app): void
    {
        self::$app = $app;

        error_reporting(E_ALL);
        ini_set('display_errors', '0');

        set_exception_handler([self::class, 'onUncaught']);
        set_error_handler([self::class, 'onError']);
        register_shutdown_function([self::class, 'onShutdown']);
    }

    /** @internal */
    public static function onError(int $severity, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) {
            Log::channel('app')->debug('deprecation: ' . $message, ['file' => $file, 'line' => $line]);
            return true;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    /** @internal */
    public static function onShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            self::onUncaught(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
        }
    }

    /** @internal */
    public static function onUncaught(Throwable $e): void
    {
        self::render($e, self::currentRequest())->send();
    }

    public static function render(Throwable $e, ?Request $request): Response
    {
        $status = $e instanceof AppException ? $e->httpStatus() : 500;
        $code = $e instanceof AppException ? $e->errorCode() : 'server_error';

        self::log($e, $status);

        if ($e instanceof ValidationException && $request !== null && !$request->expectsJson()) {
            // Errors + old input are already flashed by Request::validate().
            return new RedirectResponse(
                is_string($request->header('HTTP_REFERER')) ? (string) $request->header('HTTP_REFERER') : url('/'),
            );
        }

        if ($request !== null && $request->expectsJson()) {
            $payload = [
                'success' => false,
                'message' => self::publicMessage($e, $status),
                'errors' => $e instanceof ValidationException ? $e->errors() : new \stdClass(),
                'error_code' => $code,
            ];
            /* No debug payload is ever attached to responses - it lives in the logs. */

            return new JsonResponse($payload, $status);
        }

        return self::renderHtml($e, $status);
    }

    private static function renderHtml(Throwable $e, int $status): Response
    {
        $data = [
            'status' => $status,
            'title' => self::statusTitle($status),
            'message' => self::publicMessage($e, $status),
            'errors' => $e instanceof ValidationException ? $e->errors() : [],
            'debug' => null, /* never rendered on public pages - logs only */
        ];

        try {
            $view = app(View::class);
            $template = 'errors.' . $status;
            if (!is_file($view->resolvePath($template))) {
                $template = 'errors.500';
            }

            return new Response($view->render($template, $data), $status);
        } catch (Throwable) {
            $body = '<h1>' . $status . ' ' . self::statusTitle($status) . '</h1><p>' . e($data['message']) . '</p>';
            return new Response($body, $status, ['Content-Type' => 'text/html; charset=utf-8']);
        }
    }

    private static function log(Throwable $e, int $status): void
    {
        try {
            Log::channel('error')->log($status >= 500 ? 'error' : 'warning', sprintf(
                '%s: %s in %s:%d',
                $e::class,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ), ['trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 10)]);
        } catch (Throwable) {
            // Logging must never break the error response path.
        }
    }

    private static function publicMessage(Throwable $e, int $status): string
    {
        /* 5xx must NEVER leak backend details to visitors - regardless of
           APP_DEBUG. Full exception + trace go to storage/logs/error.log. */
        if ($status >= 500) {
            try {
                $message = trans('errors.server_error');
                if (is_string($message) && $message !== '' && $message !== 'errors.server_error') {
                    return $message;
                }
            } catch (Throwable) {
                // translator itself unavailable - static safe text below
            }

            return 'The service is temporarily unavailable. Please try again in a moment.';
        }

        /* 4xx exceptions carry user-facing (translated) messages by design. */
        return $e->getMessage();
    }

    /** @return array<string, mixed> */
    private static function debugPayload(Throwable $e): array
    {
        return [
            'exception' => $e::class,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 20),
        ];
    }

    private static function statusTitle(int $status): string
    {
        return match ($status) {
            401 => 'Unauthenticated',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            419 => 'Session Expired',
            422 => 'Unprocessable Entity',
            503 => 'Service Unavailable',
            default => 'Server Error',
        };
    }

    private static function isDebug(): bool
    {
        return self::$app?->isDebug() ?? false;
    }

    private static function currentRequest(): ?Request
    {
        $app = self::$app;
        if ($app !== null && $app->bound(Request::class)) {
            return $app->get(Request::class);
        }

        return null;
    }
}
