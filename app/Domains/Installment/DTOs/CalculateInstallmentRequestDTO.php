<?php

declare(strict_types=1);

namespace App\Domains\Installment\DTOs;

final readonly class CalculateInstallmentRequestDTO
{
    public function __construct(
        private int $userId,
        private float $amount,
        private int $requestedMonths,
        private string $currency,
    ) {
        if ($this->userId <= 0) {
            throw new \InvalidArgumentException('User ID must be positive');
        }
        if ($this->amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        if ($this->requestedMonths <= 0) {
            throw new \InvalidArgumentException('Requested months must be positive');
        }
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getRequestedMonths(): int
    {
        return $this->requestedMonths;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}




