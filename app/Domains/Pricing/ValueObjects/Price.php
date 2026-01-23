<?php

declare(strict_types=1);

namespace App\Domains\Pricing\ValueObjects;

final readonly class Price
{
    public function __construct(
        private float $storePrice,
        private float $emigramPrice,
        private string $currency,
    ) {
        if ($storePrice < 0) {
            throw new \InvalidArgumentException('Store price cannot be negative');
        }
        if ($emigramPrice < 0) {
            throw new \InvalidArgumentException('Emigram price cannot be negative');
        }
        if ($emigramPrice > $storePrice) {
            throw new \InvalidArgumentException('Emigram price cannot be greater than store price');
        }
        if (empty($currency)) {
            throw new \InvalidArgumentException('Currency cannot be empty');
        }
    }

    public function getStorePrice(): float
    {
        return $this->storePrice;
    }

    public function getEmigramPrice(): float
    {
        return $this->emigramPrice;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getSavingsAbsolute(): float
    {
        return $this->storePrice - $this->emigramPrice;
    }

    public function getSavingsPercent(): float
    {
        if ($this->storePrice === 0.0) {
            return 0.0;
        }

        return ($this->getSavingsAbsolute() / $this->storePrice) * 100;
    }

    public function equals(Price $other): bool
    {
        return $this->storePrice === $other->storePrice
            && $this->emigramPrice === $other->emigramPrice
            && $this->currency === $other->currency;
    }
}





