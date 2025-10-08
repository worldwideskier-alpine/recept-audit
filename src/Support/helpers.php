<?php
declare(strict_types=1);

use App\Support\Paths;
use App\Support\Response;

function href(string $path = ''): string
{
    return Paths::href($path);
}

function no_store_headers(): void
{
    Response::applyNoStoreHeaders();
}

function log_line(string $event, array $context = [], string $level = 'info'): void
{
    App\Support\Log::write($event, $context, $level);
}

function view(string $template, array $data = []): string
{
    return App\Support\View::render($template, $data);
}

function csrf_token(): string
{
    return App\Support\Csrf::token();
}

function verify_csrf(string $token): bool
{
    return App\Support\Csrf::verify($token);
}
