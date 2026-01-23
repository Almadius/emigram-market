<?php

declare(strict_types=1);

namespace App\Domains\User\DTOs;

use App\Domains\User\Enums\UserLevelEnum;

final readonly class UserDTO
{
    public function __construct(
        private int $id,
        private string $email,
        private UserLevelEnum $level,
    ) {
        if ($id <= 0) {
            throw new \InvalidArgumentException('User ID must be positive');
        }
        if (empty($email)) {
            throw new \InvalidArgumentException('Email cannot be empty');
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getLevel(): UserLevelEnum
    {
        return $this->level;
    }
}





