<?php

declare(strict_types=1);

namespace App\Exceptions;

final class BadRequestException extends ApiException
{
    public function getStatusCode(): int
    {
        return 400;
    }

    public function getErrorCode(): string
    {
        return 'BAD_REQUEST';
    }
}
