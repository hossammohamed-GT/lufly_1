<?php

declare(strict_types=1);

namespace Modules\Authentication\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Core\Auth\Auth;
use Core\Exceptions\AuthenticationException;
use Core\Http\RedirectResponse;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Session;
use Modules\Authentication\Requests\LoginRequest;

class AuthController extends Controller
{
    public function __construct(
        private readonly Auth $auth,
        private readonly Session $session,
        private readonly ActivityLogger $activity,
    ) {
    }

    public function showLogin(): Response
    {
        if ($this->auth->check()) {
            return $this->redirect(route('admin.dashboard'));
        }

        return $this->view('authentication::login', [
            'title' => trans('auth.login'),
        ]);
    }

    public function login(Request $request): Response
    {
        $data = (new LoginRequest())->handle($request);

        if (!$this->auth->attempt($data['email'], $data['password'])) {
            \Core\Logging\Log::channel('security')->warning('Login failed', [
                'email' => $data['email'],
                'ip' => $request->ip(),
            ]);

            if ($request->expectsJson()) {
                throw new AuthenticationException(trans('auth.failed'));
            }

            $this->session->flash('_errors', ['email' => [trans('auth.failed')]]);

            return new RedirectResponse(route('login'));
        }

        $this->activity->login($this->auth->id(), $data['email']);

        $intended = $this->session->getFlash('_intended_url');
        $target = is_string($intended) && $intended !== '' ? $intended : '/admin';

        return new RedirectResponse(url($target));
    }

    public function logout(): RedirectResponse
    {
        $this->activity->logout($this->auth->id());
        $this->auth->logout();

        return new RedirectResponse(route('home'));
    }
}
