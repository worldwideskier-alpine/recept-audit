<?php
declare(strict_types=1);

namespace App\Support;

use App\Support\CSRF;

function render_layout(string $title, string $body, array $options = []): string
{
    $lang = $options['lang'] ?? 'ja';
    $headExtra = $options['head'] ?? '';
    $csrfInput = '';
    if (($options['csrf'] ?? false) === true) {
        $csrfInput = '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(CSRF::token(), ENT_QUOTES, 'UTF-8') . '">';
    }
    $body = str_replace('{{csrf}}', $csrfInput, $body);

    return <<<HTML
    <!doctype html>
    <html lang="{$lang}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{$title}</title>
        <meta http-equiv="Cache-Control" content="no-store">
        {$headExtra}
    </head>
    <body>
    {$body}
    </body>
    </html>
    HTML;
}
