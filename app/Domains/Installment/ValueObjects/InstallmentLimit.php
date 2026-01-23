<?php

declare(strict_types=1);

namespace App\Domains\Installment\ValueObjects;

final readonly class InstallmentLimit
{
    public function __construct(
        private float $maxAmount,
        private int $maxMonths,
        private float $minMonthlyPayment,
        private string $currency,
    ) {
        if ($this->maxAmount <= 0) {
            throw new \InvalidArgumentException('Max amount must be positive');
        }
        if ($this->maxMonths <= 0) {
            throw new \InvalidArgumentException('Max months must be positive');
        }
        if ($this->minMonthlyPayment <= 0) {
            throw new \InvalidArgumentException('Min monthly payment must be positive');
        }
    }

    public function getMaxAmount(): float
    {
        return $this->maxAmount;
    }

    public function getMaxMonths(): int
    {
        return $this->maxMonths;
    }

    public function getMinMonthlyPayment(): float
    {
        return $this->minMonthlyPayment;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function canAfford(float $amount, int $months): bool
    {
        if ($amount > $this->maxAmount) {
            return false;
        }
        if ($months > $this->maxMonths) {
            return false;
        }
        $monthlyPayment = $amount / $months;
        return $monthlyPayment >= $this->minMonthlyPayment;
    }
}




