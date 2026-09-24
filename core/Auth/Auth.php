<?php

declare(strict_types=1);

namespace Core\Auth;

use Core\Contracts\UserProviderInterface;
use Core\Http\Session;
use Core\Logging\Log;

class Auth
{
    private ?object $cachedUser = null;

    private ?array $cachedPermissions = null;

    public function __construct(
        private readonly Session $session,
        private readonly UserProviderInterface $users,
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

    private function isLocked(string $email): bool
    {
        $maxAttempts = (int) config('security.login_max_attempts', 5);
        $cleanEmail = strtolower(trim($email));
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        $emailKey = 'login_lockout_email_' . sha1($cleanEmail);
        $ipKey = 'login_lockout_ip_' . sha1($ip);

        $emailEntry = $this->readLockoutEntry($emailKey);
        if ($emailEntry && ($emailEntry['count'] ?? 0) >= $maxAttempts && ($emailEntry['locked_until'] ?? 0) > time()) {
            return true;
        }

        $ipEntry = $this->readLockoutEntry($ipKey);
        $maxIpAttempts = $maxAttempts * 3;
        if ($ipEntry && ($ipEntry['count'] ?? 0) >= $maxIpAttempts && ($ipEntry['locked_until'] ?? 0) > time()) {
            return true;
        }

        $attempts = (array) $this->session->get('_login_attempts', []);
        $sessionEntry = $attempts[$cleanEmail] ?? null;
        if (is_array($sessionEntry) && ($sessionEntry['count'] ?? 0) >= $maxAttempts && ($sessionEntry['locked_until'] ?? 0) > time()) {
            return true;
        }

        return false;
    }

    private function registerFailedAttempt(string $email): void
    {
        $maxAttempts = (int) config('security.login_max_attempts', 5);
        $lockoutSeconds = (int) config('security.login_lockout_seconds', 300);
        $cleanEmail = strtolower(trim($email));
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        $emailKey = 'login_lockout_email_' . sha1($cleanEmail);
        $ipKey = 'login_lockout_ip_' . sha1($ip);

        $emailEntry = $this->readLockoutEntry($emailKey) ?? ['count' => 0, 'locked_until' => 0];
        $emailCount = (int) (($emailEntry['count'] ?? 0) + 1);
        $emailLockedUntil = $emailCount >= $maxAttempts ? time() + $lockoutSeconds : 0;
        $this->writeLockoutEntry($emailKey, [
            'count' => $emailCount,
            'locked_until' => $emailLockedUntil,
            'expires_at' => time() + $lockoutSeconds,
        ]);

        $ipEntry = $this->readLockoutEntry($ipKey) ?? ['count' => 0, 'locked_until' => 0];
        $ipCount = (int) (($ipEntry['count'] ?? 0) + 1);
        $ipLockedUntil = $ipCount >= ($maxAttempts * 3) ? time() + $lockoutSeconds : 0;
        $this->writeLockoutEntry($ipKey, [
            'count' => $ipCount,
            'locked_until' => $ipLockedUntil,
            'expires_at' => time() + $lockoutSeconds,
        ]);

        $attempts = (array) $this->session->get('_login_attempts', []);
        $attempts[$cleanEmail] = [
            'count' => $emailCount,
            'locked_until' => $emailLockedUntil,
        ];
        $this->session->set('_login_attempts', $attempts);
    }

    private function clearAttempts(string $email): void
    {
        $cleanEmail = strtolower(trim($email));
        $emailKey = 'login_lockout_email_' . sha1($cleanEmail);
        $this->deleteLockoutEntry($emailKey);

        $attempts = (array) $this->session->get('_login_attempts', []);
        unset($attempts[$cleanEmail]);
        $this->session->set('_login_attempts', $attempts);
    }

    private function getLockoutPath(string $key): string
    {
        $dir = function_exists('base_path') ? base_path('storage/cache') : (dirname(__DIR__, 2) . '/storage/cache');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir . '/' . $key . '.json';
    }

    private function readLockoutEntry(string $key): ?array
    {
        $path = $this->getLockoutPath($key);
        if (!is_file($path)) {
            return null;
        }

        $data = @json_decode((string) file_get_contents($path), true);
        if (!is_array($data)) {
            return null;
        }

        if (($data['locked_until'] ?? 0) < time() && ($data['expires_at'] ?? 0) < time()) {
            @unlink($path);
            return null;
        }

        return $data;
    }

    private function writeLockoutEntry(string $key, array $data): void
    {
        $path = $this->getLockoutPath($key);
        @file_put_contents($path, json_encode($data), LOCK_EX);
    }

    private function deleteLockoutEntry(string $key): void
    {
        $path = $this->getLockoutPath($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
