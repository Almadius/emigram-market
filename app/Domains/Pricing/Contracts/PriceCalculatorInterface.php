<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Contracts;

use App\Domains\Pricing\ValueObjects\Discount;
use App\Domains\Pricing\ValueObjects\Price;

interface PriceCalculatorInterface
{
    public function calculate(
        float $storePrice,
        Discount $discount,
        string $currency
    ): Price;
}





