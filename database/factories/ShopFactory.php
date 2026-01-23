<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shop>
 */
class ShopFactory extends Factory
{
    protected $model = Shop::class;

    public function definition(): array
    {
        return [
            'domain' => fake()->unique()->domainName(),
            'name' => fake()->company(),
            'base_discount' => fake()->randomFloat(2, 0, 10),
            'is_active' => true,
            'parsing_selectors' => [
                'price' => ['.price', '[data-price]'],
                'currency' => ['.currency', '€'],
                'name' => ['h1', '.product-title'],
            ],
            'crawl_interval_minutes' => 30,
        ];
    }
}
