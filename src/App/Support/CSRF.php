<?php
declare(strict_types=1);

namespace App\Support;

final class CSRF
{
    private const TOKEN_KEY = '_csrf_token';

    public static function token(): string
    {
        Auth::start();
        if (!isset($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    public static function verify(?string $token): bool
    {
        Auth::start();
        $stored = $_SESSION[self::TOKEN_KEY] ?? '';
        return is_string($token) && hash_equals($stored, $token);
    }
}
