<?php

declare(strict_types=1);

namespace App\Exceptions;

final class UnauthorizedException extends ApiException
{
    public function getStatusCode(): int
    {
        return 401;
    }

    public function getErrorCode(): string
    {
        return 'UNAUTHORIZED';
    }
}
