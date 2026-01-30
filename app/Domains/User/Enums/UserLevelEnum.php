<?php

declare(strict_types=1);

namespace App\Domains\User\Enums;

enum UserLevelEnum: int
{
    case BRONZE = 1;
    case SILVER = 2;
    case GOLD = 3;
    case PLATINUM = 4;
    case DIAMOND = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::BRONZE => 'Bronze',
            self::SILVER => 'Silver',
            self::GOLD => 'Gold',
            self::PLATINUM => 'Platinum',
            self::DIAMOND => 'Diamond',
        };
    }
}
