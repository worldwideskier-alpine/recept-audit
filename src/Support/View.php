<?php
declare(strict_types=1);

namespace App\Support;

final class View
{
    public static function render(string $template, array $data = []): string
    {
        $path = Paths::basePath('src/Pages/' . $template . '.php');
        if (!is_file($path)) {
            return '';
        }
        extract($data);
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }
}
