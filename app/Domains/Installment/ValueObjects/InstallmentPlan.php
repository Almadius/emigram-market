<?php

declare(strict_types=1);

namespace App\Domains\Installment\ValueObjects;

final readonly class InstallmentPlan
{
    public function __construct(
        private float $totalAmount,
        private int $months,
        private float $monthlyPayment,
        private float $interestRate,
        private string $currency,
    ) {
        if ($this->totalAmount <= 0) {
            throw new \InvalidArgumentException('Total amount must be positive');
        }
        if ($this->months <= 0) {
            throw new \InvalidArgumentException('Months must be positive');
        }
        if ($this->monthlyPayment <= 0) {
            throw new \InvalidArgumentException('Monthly payment must be positive');
        }
        if ($this->interestRate < 0) {
            throw new \InvalidArgumentException('Interest rate cannot be negative');
        }
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function getMonths(): int
    {
        return $this->months;
    }

    public function getMonthlyPayment(): float
    {
        return $this->monthlyPayment;
    }

    public function getInterestRate(): float
    {
        return $this->interestRate;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getTotalWithInterest(): float
    {
        return $this->totalAmount * (1 + $this->interestRate / 100);
    }
}




