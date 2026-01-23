<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Eloquent;

use App\Domains\User\Contracts\UserRepositoryInterface;
use App\Domains\User\DTOs\UserDTO;
use App\Domains\User\Enums\UserLevelEnum;
use App\Models\User;

final class UserRepository implements UserRepositoryInterface
{
    public function findById(int $userId): ?UserDTO
    {
        $user = User::find($userId);
        
        if ($user === null) {
            return null;
        }

        // Предполагаем, что в таблице users есть поле level (int)
        $level = UserLevelEnum::from($user->level ?? 1);

        return new UserDTO(
            id: $user->id,
            email: $user->email,
            level: $level
        );
    }

    public function updateLevel(int $userId, UserLevelEnum $level): void
    {
        User::where('id', $userId)->update(['level' => $level->value]);
    }
}





