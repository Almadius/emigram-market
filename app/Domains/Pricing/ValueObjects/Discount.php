<?php

declare(strict_types=1);

namespace App\Domains\Pricing\ValueObjects;

final readonly class Discount
{
    public function __construct(
        private float $baseDiscount,
        private float $personalDiscount,
        private float $minDiscount,
        private float $maxDiscount,
    ) {
        if ($baseDiscount < 0 || $baseDiscount > 100) {
            throw new \InvalidArgumentException('Base discount must be between 0 and 100');
        }
        if ($personalDiscount < 0 || $personalDiscount > 100) {
            throw new \InvalidArgumentException('Personal discount must be between 0 and 100');
        }
        if ($minDiscount < 0 || $minDiscount > 100) {
            throw new \InvalidArgumentException('Min discount must be between 0 and 100');
        }
        if ($maxDiscount < 0 || $maxDiscount > 100) {
            throw new \InvalidArgumentException('Max discount must be between 0 and 100');
        }
        if ($minDiscount > $maxDiscount) {
            throw new \InvalidArgumentException('Min discount cannot be greater than max discount');
        }
    }

    public function getBaseDiscount(): float
    {
        return $this->baseDiscount;
    }

    public function getPersonalDiscount(): float
    {
        return $this->personalDiscount;
    }

    public function getTotalDiscount(): float
    {
        $total = $this->baseDiscount + $this->personalDiscount;

        return max($this->minDiscount, min($total, $this->maxDiscount));
    }

    public function calculatePrice(float $storePrice): float
    {
        $discountPercent = $this->getTotalDiscount();
        $discountedPrice = $storePrice * (1 - $discountPercent / 100);

        return $this->roundPrice($discountedPrice);
    }

    private function roundPrice(float $price): float
    {
        // Округление до .99 или .90
        $floor = floor($price);
        $decimal = $price - $floor;

        if ($decimal >= 0.95) {
            return $floor + 0.99;
        }
        if ($decimal >= 0.90) {
            return $floor + 0.90;
        }

        return round($price, 2);
    }
}



