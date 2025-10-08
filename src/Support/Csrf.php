<?php
declare(strict_types=1);

namespace App\Support;

final class Csrf
{
    private const KEY = '_csrf_token';

    public static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $config = Config::get();
        session_name($config['security']['session_name']);
        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'cookie_samesite' => 'Lax',
        ]);
    }

    public static function token(): string
    {
        self::ensureSession();
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(16));
        }
        return $_SESSION[self::KEY];
    }

    public static function verify(string $token): bool
    {
        self::ensureSession();
        return hash_equals($_SESSION[self::KEY] ?? '', $token);
    }
}
