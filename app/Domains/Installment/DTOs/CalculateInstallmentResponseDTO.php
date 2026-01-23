<?php

declare(strict_types=1);

namespace App\Domains\Installment\DTOs;

use App\Domains\Installment\ValueObjects\InstallmentLimit;
use App\Domains\Installment\ValueObjects\InstallmentPlan;

final readonly class CalculateInstallmentResponseDTO
{
    public function __construct(
        private bool $approved,
        private ?InstallmentPlan $plan,
        private InstallmentLimit $limit,
        private string $message,
    ) {
    }

    public function isApproved(): bool
    {
        return $this->approved;
    }

    public function getPlan(): ?InstallmentPlan
    {
        return $this->plan;
    }

    public function getLimit(): InstallmentLimit
    {
        return $this->limit;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}




