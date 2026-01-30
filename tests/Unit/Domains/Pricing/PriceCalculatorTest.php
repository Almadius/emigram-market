<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Pricing;

use App\Domains\Pricing\Services\PriceCalculator;
use App\Domains\Pricing\ValueObjects\Discount;
use App\Domains\Pricing\ValueObjects\Price;
use PHPUnit\Framework\TestCase;

final class PriceCalculatorTest extends TestCase
{
    private PriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new PriceCalculator;
    }

    public function test_calculate_price_with_discount(): void
    {
        $storePrice = 100.0;
        $discount = new Discount(
            baseDiscount: 5.0,
            personalDiscount: 5.0,
            minDiscount: 0.0,
            maxDiscount: 50.0
        );

        $price = $this->calculator->calculate($storePrice, $discount, 'EUR');

        $this->assertInstanceOf(Price::class, $price);
        $this->assertEquals(100.0, $price->getStorePrice());
        $this->assertEquals(90.0, $price->getEmigramPrice());
        $this->assertEquals(10.0, $price->getSavingsAbsolute());
        $this->assertEquals(10.0, $price->getSavingsPercent());
    }

    public function test_calculate_price_with_max_discount(): void
    {
        $storePrice = 1000.0;
        $discount = new Discount(
            baseDiscount: 5.0,
            personalDiscount: 50.0, // Would be 55% total, but max is 50%
            minDiscount: 0.0,
            maxDiscount: 50.0
        );

        $price = $this->calculator->calculate($storePrice, $discount, 'EUR');

        $this->assertEquals(500.0, $price->getEmigramPrice());
        $this->assertEquals(50.0, $price->getSavingsPercent());
    }
}
