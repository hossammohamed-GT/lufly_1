<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Core\Auth\Auth;
use Core\Database\DatabaseManager;
use Core\Http\RedirectResponse;
use Core\Http\Request;
use Core\Http\Response;

/**
 * The single administrator changes their own password. Installations that
 * prefer the .env to stay authoritative can reset it any time with
 * `php cli admin:setup`.
 */
final class PasswordController extends Controller
{
    public function __construct(
        private readonly Auth $auth,
        private readonly DatabaseManager $db,
        private readonly ActivityLogger $activity,
    ) {
    }

    public function edit(): Response
    {
        return $this->view('admin.password', [
            'title' => trans('common.change_password'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:14|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        $user = $this->auth->user();
        if ($user === null || !password_verify((string) $data['current_password'], (string) ($user->password ?? ''))) {
            return $this->redirect(route('admin.password.edit'))
                ->with('_errors', ['current_password' => [trans('auth.current_password_invalid')]]);
        }

        if (strlen((string) $data['password']) < 14) {
            return $this->redirect(route('admin.password.edit'))
                ->with('_errors', ['password' => [trans('auth.password_too_short')]]);
        }

        $this->db->connection()->table('users')->where('id', (int) $user->id)->update([
            'password' => password_hash((string) $data['password'], PASSWORD_BCRYPT, [
                'cost' => (int) config('security.bcrypt_cost', 12),
            ]),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->activity->updated('user', (int) $user->id);

        return $this->redirect(route('admin.password.edit'))
            ->with('_success', trans('common.password_updated'));
    }
}
