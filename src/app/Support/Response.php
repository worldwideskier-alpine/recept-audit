<?php
declare(strict_types=1);

namespace App\Support;

final class Response
{
    public static function json(array $payload, int $status = 200, bool $omitBody = false): void
    {
        self::sendStatus($status);
        self::applyNoStore();
        if (PHP_SAPI !== 'cli') {
            header('Content-Type: application/json; charset=utf-8');
        }
        if (!$omitBody) {
            echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
    }

    public static function redirect(string $location, int $status = 302): void
    {
        self::sendStatus($status);
        self::applyNoStore();
        if (PHP_SAPI !== 'cli') {
            header('Location: ' . $location);
        }
        echo '';
    }

    private static function sendStatus(int $status): void
    {
        if (PHP_SAPI !== 'cli') {
            http_response_code($status);
        }
    }

    private static function applyNoStore(): void
    {
        $header = Config::getInstance()->get('http.no_store_header', 'Cache-Control: no-store');
        if (PHP_SAPI !== 'cli') {
            header($header);
        }
    }
}
