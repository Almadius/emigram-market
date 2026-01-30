<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Order\Enums\OrderStatusEnum;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $shop = Shop::factory()->create();

        return [
            'user_id' => User::factory(),
            'shop_id' => $shop->id,
            'shop_domain' => $shop->domain,
            'status' => OrderStatusEnum::PENDING->value,
            'total' => fake()->randomFloat(2, 10, 1000),
            'currency' => 'EUR',
            'shop_order_id' => null,
            'metadata' => [],
        ];
    }
}
