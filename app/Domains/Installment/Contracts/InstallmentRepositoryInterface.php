<?php

declare(strict_types=1);

namespace App\Domains\Installment\Contracts;

use App\Domains\Installment\ValueObjects\InstallmentLimit;
use App\Domains\User\Enums\UserLevelEnum;

interface InstallmentRepositoryInterface
{
    public function getLimitForUserLevel(UserLevelEnum $level): InstallmentLimit;

    public function getUserHistory(int $userId): array;

    public function getActiveInstallmentsCount(int $userId): int;
}
