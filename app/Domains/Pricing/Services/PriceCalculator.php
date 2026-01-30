<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Services;

use App\Domains\Pricing\Contracts\PriceCalculatorInterface;
use App\Domains\Pricing\ValueObjects\Discount;
use App\Domains\Pricing\ValueObjects\Price;

final class PriceCalculator implements PriceCalculatorInterface
{
    public function calculate(
        float $storePrice,
        Discount $discount,
        string $currency
    ): Price {
        $emigramPrice = $discount->calculatePrice($storePrice);

        return new Price(
            storePrice: $storePrice,
            emigramPrice: $emigramPrice,
            currency: $currency
        );
    }
}
