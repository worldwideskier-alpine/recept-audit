<?php
declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class HttpException extends Exception
{
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly ?string $redirectTo = null
    ) {
        parent::__construct($message, $status);
    }
}
