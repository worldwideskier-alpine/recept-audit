<?php

declare(strict_types=1);

namespace App;

final class App
{
    public function greet(?string $name = null): string
    {
        $subject = $this->normalizeName($name);

        return sprintf('Hello, %s!', $subject);
    }

    private function normalizeName(?string $name): string
    {
        $normalized = trim((string) $name);

        return $normalized !== '' ? $normalized : 'World';
    }
}
