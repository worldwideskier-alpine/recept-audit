<?php
declare(strict_types=1);

namespace App\Support;

final class Response
{
    public function __construct(
        private int $status = 200,
        private array $headers = [],
        private string $body = ''
    ) {
    }

    public static function json(array $data, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        return new self($status, $headers, json_encode($data, JSON_UNESCAPED_SLASHES));
    }

    public static function redirect(string $location, int $status = 302): self
    {
        $headers = [
            'Location' => $location,
        ];
        return new self($status, $headers, '');
    }

    public static function html(string $html, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'text/html; charset=utf-8';
        return new self($status, $headers, $html);
    }

    public function send(bool $sendBody = true): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            $headers = array_change_key_case($this->headers, CASE_LOWER);
            if (!isset($headers['cache-control'])) {
                $headers['cache-control'] = 'no-store';
            }
            foreach ($headers as $name => $value) {
                header($this->formatHeaderName($name) . ': ' . $value, true);
            }
        }
        if ($sendBody) {
            echo $this->body;
        }
    }

    private function formatHeaderName(string $name): string
    {
        return implode('-', array_map(static fn ($part) => ucfirst($part), explode('-', $name)));
    }
}
