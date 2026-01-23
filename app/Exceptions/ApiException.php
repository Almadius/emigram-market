<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

abstract class ApiException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 400,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    abstract public function getStatusCode(): int;

    abstract public function getErrorCode(): string;
}


