<?php

declare(strict_types=1);

namespace App\Exceptions;

final class NotFoundException extends ApiException
{
    public function getStatusCode(): int
    {
        return 404;
    }

    public function getErrorCode(): string
    {
        return 'NOT_FOUND';
    }
}


