<?php
declare(strict_types=1);

namespace App\Support;

final class Response
{
    public static function applyNoStoreHeaders(): void
    {
        header('Cache-Control: no-store', true);
    }

    public static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        self::applyNoStoreHeaders();
        header('Content-Type: application/json; charset=utf-8', true);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function redirect(string $location, int $status = 302): void
    {
        self::applyNoStoreHeaders();
        header('Location: ' . $location, true, $status);
    }

    public static function html(string $content, int $status = 200, array $headers = []): void
    {
        http_response_code($status);
        self::applyNoStoreHeaders();
        header('Content-Type: text/html; charset=utf-8', true);
        foreach ($headers as $name => $value) {
            header($name . ': ' . $value, true);
        }
        echo $content;
    }
}
