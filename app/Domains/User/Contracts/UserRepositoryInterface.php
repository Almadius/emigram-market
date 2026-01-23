<?php

declare(strict_types=1);

namespace App\Domains\User\Contracts;

use App\Domains\User\DTOs\UserDTO;
use App\Domains\User\Enums\UserLevelEnum;

interface UserRepositoryInterface
{
    public function findById(int $userId): ?UserDTO;

    public function updateLevel(int $userId, UserLevelEnum $level): void;
}





