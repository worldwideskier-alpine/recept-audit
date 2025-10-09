<?php
declare(strict_types=1);

namespace ReceptAudit\Support;

final class Response
{
    /**
     * @param array<string, mixed> $headers
     */
    public static function json(array $payload, int $status = 200, array $headers = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function text(string $body, int $status = 200, array $headers = []): void
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');

        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $body;
    }

    public static function redirect(string $location, int $status = 302, array $headers = []): void
    {
        http_response_code($status);
        header('Cache-Control: no-store');
        header('Location: ' . $location);

        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }
    }
}
