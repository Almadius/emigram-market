<?php

declare(strict_types=1);

namespace App\Exceptions;

final class ValidationException extends ApiException
{
    /**
     * @param  array<string, array<string>>  $errors
     */
    public function __construct(
        string $message = 'Validation failed',
        private readonly array $errors = [],
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return 422;
    }

    public function getErrorCode(): string
    {
        return 'VALIDATION_ERROR';
    }

    /**
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
