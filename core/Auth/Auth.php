<?php

declare(strict_types=1);

namespace Core\Auth;

use Core\Contracts\UserProviderInterface;
use Core\Http\Session;
use Core\Logging\Log;

class Auth
{
    private ?object $cachedUser = null;

    /** @var string[]|null */
    private ?array $cachedPermissions = null;

    public function __construct(
        private readonly Session $session,
        private readonly UserProviderInterface $users,
        private readonly \App\Services\CacheService $cache,
    ) {
    }

    public function attempt(string $email, string $password): bool
    {
        if ($this->isLocked($email)) {
            Log::channel('security')->warning('Login attempt while locked out', ['email' => $email]);
            return false;
        }

        $user = $this->users->findByEmail($email);

        if ($user === null || ($user->status ?? 'active') !== 'active' || !password_verify($password, (string) ($user->password ?? ''))) {
            $this->registerFailedAttempt($email);
            Log::channel('security')->warning('Failed login', ['email' => $email]);

            return false;
        }

        $this->session->regenerate();
        $this->session->set('_auth_id', $user->id);
        $this->clearAttempts($email);
        $this->cachedUser = $user;

        Log::channel('security')->info('Login successful', ['user_id' => $user->id, 'email' => $email]);

        return true;
    }

    public function check(): bool
    {
        return $this->id() !== null;
    }

    public function id(): int|string|null
    {
        return $this->session->get('_auth_id');
    }

    public function user(): ?object
    {
        if ($this->cachedUser !== null) {
            return $this->cachedUser;
        }

        $id = $this->id();
        if ($id === null) {
            return null;
        }

        return $this->cachedUser = $this->users->findById($id);
    }

    public function userCan(string $permission): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        if ($this->cachedPermissions === null) {
            $this->cachedPermissions = $this->users->permissionsFor($user->id);
        }

        return in_array('*', $this->cachedPermissions, true)
            || in_array($permission, $this->cachedPermissions, true);
    }

    public function logout(): void
    {
        Log::channel('security')->info('Logout', ['user_id' => $this->id()]);

        $this->session->remove('_auth_id');
        $this->session->regenerate();
        $this->cachedUser = null;
        $this->cachedPermissions = null;
    }

    /* Lockout state lives in the server-side cache (file driver), keyed by
       email. Storing it in the visitor session - as before - let an attacker
       bypass the lock simply by discarding cookies. */
    private function attemptsKey(string $email): string
    {
        return 'login_attempts:' . sha1(mb_strtolower(trim($email)));
    }

    private function isLocked(string $email): bool
    {
        $entry = $this->cache->get($this->attemptsKey($email));

        return is_array($entry)
            && ($entry['count'] ?? 0) >= (int) config('security.login_max_attempts', 5)
            && ($entry['locked_until'] ?? 0) > time();
    }

    private function registerFailedAttempt(string $email): void
    {
        $entry = $this->cache->get($this->attemptsKey($email), ['count' => 0, 'locked_until' => 0]);
        if (!is_array($entry)) {
            $entry = ['count' => 0, 'locked_until' => 0];
        }

        $lockout = (int) config('security.login_lockout_seconds', 300);
        $count = (int) ($entry['count'] ?? 0) + 1;

        $this->cache->set($this->attemptsKey($email), [
            'count' => $count,
            'locked_until' => $count >= (int) config('security.login_max_attempts', 5)
                ? time() + $lockout
                : 0,
        ], max(300, $lockout + 300));
    }

    private function clearAttempts(string $email): void
    {
        $this->cache->forget($this->attemptsKey($email));
    }
}
