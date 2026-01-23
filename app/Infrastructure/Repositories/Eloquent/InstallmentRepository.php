<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Eloquent;

use App\Domains\Installment\Contracts\InstallmentRepositoryInterface;
use App\Domains\Installment\ValueObjects\InstallmentLimit;
use App\Domains\User\Enums\UserLevelEnum;
use Illuminate\Support\Facades\DB;

final class InstallmentRepository implements InstallmentRepositoryInterface
{
    private const LIMITS = [
        UserLevelEnum::BRONZE->value => ['max_amount' => 1000.0, 'max_months' => 6, 'min_monthly' => 50.0],
        UserLevelEnum::SILVER->value => ['max_amount' => 2500.0, 'max_months' => 12, 'min_monthly' => 100.0],
        UserLevelEnum::GOLD->value => ['max_amount' => 5000.0, 'max_months' => 18, 'min_monthly' => 150.0],
        UserLevelEnum::PLATINUM->value => ['max_amount' => 10000.0, 'max_months' => 24, 'min_monthly' => 200.0],
        UserLevelEnum::DIAMOND->value => ['max_amount' => 50000.0, 'max_months' => 36, 'min_monthly' => 500.0],
    ];

    public function getLimitForUserLevel(UserLevelEnum $level): InstallmentLimit
    {
        $limits = self::LIMITS[$level->value] ?? self::LIMITS[UserLevelEnum::BRONZE->value];

        return new InstallmentLimit(
            maxAmount: $limits['max_amount'],
            maxMonths: $limits['max_months'],
            minMonthlyPayment: $limits['min_monthly'],
            currency: 'EUR'
        );
    }

    public function getUserHistory(int $userId): array
    {
        return DB::table('installments')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function getActiveInstallmentsCount(int $userId): int
    {
        return DB::table('installments')
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved', 'active'])
            ->count();
    }
}




