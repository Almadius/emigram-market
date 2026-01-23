<?php

declare(strict_types=1);

namespace App\Exceptions;

final class ForbiddenException extends ApiException
{
    public function getStatusCode(): int
    {
        return 403;
    }

    public function getErrorCode(): string
    {
        return 'FORBIDDEN';
    }
}


