<?php
declare(strict_types=1);

namespace App\Support;

use PDO;

final class Auth
{
    private const SESSION_KEY = 'auth_user';

    public static function init(): void
    {
        Csrf::ensureSession();
    }

    public static function attempt(string $email, string $password): bool
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            log_line('auth_login_failed', ['email' => $email]);
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            log_line('auth_login_failed', ['email' => $email]);
            return false;
        }
        $_SESSION[self::SESSION_KEY] = [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'tenant_id' => $user['tenant_id'] !== null ? (int) $user['tenant_id'] : null,
        ];
        log_line('auth_login', ['user_id' => $user['id'], 'email' => $email, 'role' => $user['role']]);
        return true;
    }

    public static function logout(): void
    {
        $user = self::user();
        if ($user) {
            log_line('auth_logout', ['user_id' => $user['id'], 'email' => $user['email']]);
        }
        unset($_SESSION[self::SESSION_KEY]);
    }

    public static function user(): ?array
    {
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    public static function requireRole(array $roles): void
    {
        $user = self::user();
        if (!$user || !in_array($user['role'], $roles, true)) {
            log_line('guard_blocked', ['required_roles' => $roles, 'user' => $user]);
            Response::redirect(href('/login'));
            exit;
        }
    }

    public static function requireProviderLogin(): void
    {
        $user = self::user();
        if (!$user || $user['role'] !== 'provider') {
            Response::redirect(href('/provider/login'));
            exit;
        }
    }
}
