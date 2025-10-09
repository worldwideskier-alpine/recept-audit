<?php
declare(strict_types=1);

namespace App\Support;

use App\Exceptions\HttpException;

final class Auth
{
    public const REALM_PROVIDER = 'provider';
    public const REALM_GENERAL = 'general';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('RECEPTSESSID');
            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => isset($_SERVER['HTTPS']),
                'cookie_samesite' => 'Lax',
            ]);
        }
    }

    public static function attempt(string $email, string $password, string $realm): bool
    {
        self::start();
        $user = self::findUserByEmail($email);
        if (!$user) {
            return false;
        }
        $allowedRoles = $realm === self::REALM_PROVIDER ? ['provider'] : ['admin', 'clerk'];
        if (!in_array($user['role'] ?? '', $allowedRoles, true)) {
            return false;
        }
        if (!password_verify($password, $user['password_hash'] ?? '')) {
            return false;
        }
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'tenant_id' => $user['tenant_id'],
        ];
        log_line('auth_login', ['email' => $user['email'], 'role' => $user['role']]);
        return true;
    }

    public static function logout(): void
    {
        self::start();
        $user = self::user();
        if ($user !== null) {
            log_line('auth_logout', ['email' => $user['email'], 'role' => $user['role']]);
        }
        $_SESSION = [];
        session_destroy();
    }

    public static function user(): ?array
    {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    public static function requireLogin(string $realm): void
    {
        $user = self::user();
        if ($user === null) {
            throw new HttpException(302, 'Redirect', $realm === self::REALM_PROVIDER ? '/provider/login' : '/login');
        }
        $allowedRoles = $realm === self::REALM_PROVIDER ? ['provider'] : ['admin', 'clerk'];
        if (!in_array($user['role'] ?? '', $allowedRoles, true)) {
            throw new HttpException(302, 'Redirect', '/login');
        }
    }

    public static function requireRoles(array $roles, string $realm = self::REALM_GENERAL): void
    {
        $user = self::user();
        if ($user === null) {
            throw new HttpException(302, 'Redirect', $realm === self::REALM_PROVIDER ? '/provider/login' : '/login');
        }
        if (!in_array($user['role'] ?? '', $roles, true)) {
            throw new HttpException(302, 'Redirect', $realm === self::REALM_PROVIDER ? '/provider/login' : '/login');
        }
    }

    private static function findUserByEmail(string $email): ?array
    {
        $rows = DB::select('SELECT id, email, password_hash, role, tenant_id, force_reset FROM users WHERE email = :email LIMIT 1', ['email' => $email]);
        return $rows[0] ?? null;
    }
}
