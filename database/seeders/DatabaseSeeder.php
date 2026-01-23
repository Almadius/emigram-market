<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DiscountRule;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Создаём тестового пользователя
        $user = User::firstOrCreate(
            ['email' => 'test@emigram.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'level' => 3, // Gold
            ]
        );

        // Создаём магазины
        $shops = [
            [
                'domain' => 'example-shop.com',
                'name' => 'Example Shop',
                'base_discount' => 5.0,
                'is_active' => true,
            ],
            [
                'domain' => 'test-store.com',
                'name' => 'Test Store',
                'base_discount' => 3.0,
                'is_active' => true,
            ],
        ];

        foreach ($shops as $shopData) {
            Shop::firstOrCreate(
                ['domain' => $shopData['domain']],
                $shopData
            );
        }

        // Создаём правила скидок
        $discountRules = [
            ['user_level' => 1, 'discount' => 0.0],   // Bronze
            ['user_level' => 2, 'discount' => 2.0],   // Silver
            ['user_level' => 3, 'discount' => 5.0],   // Gold
            ['user_level' => 4, 'discount' => 8.0],   // Platinum
            ['user_level' => 5, 'discount' => 10.0],  // Diamond
        ];

        foreach ($discountRules as $rule) {
            DiscountRule::firstOrCreate(
                ['user_level' => $rule['user_level']],
                $rule
            );
        }

        // Создаём тестовые товары
        $shops = Shop::all();
        if ($shops->isNotEmpty()) {
            $products = [
                [
                    'name' => 'Test Product 1',
                    'description' => 'Description for test product 1',
                    'url' => 'https://example-shop.com/product-1',
                    'image_url' => 'https://via.placeholder.com/300',
                    'price' => 100.00,
                    'currency' => 'EUR',
                    'shop_id' => $shops->first()->id,
                ],
                [
                    'name' => 'Test Product 2',
                    'description' => 'Description for test product 2',
                    'url' => 'https://example-shop.com/product-2',
                    'image_url' => 'https://via.placeholder.com/300',
                    'price' => 200.00,
                    'currency' => 'EUR',
                    'shop_id' => $shops->first()->id,
                ],
                [
                    'name' => 'Test Product 3',
                    'description' => 'Description for test product 3',
                    'url' => 'https://test-store.com/product-3',
                    'image_url' => 'https://via.placeholder.com/300',
                    'price' => 150.00,
                    'currency' => 'EUR',
                    'shop_id' => $shops->count() > 1 ? $shops->last()->id : $shops->first()->id,
                ],
            ];

            foreach ($products as $productData) {
                Product::firstOrCreate(
                    ['url' => $productData['url']],
                    $productData
                );
            }
        }

        $this->command->info('Test data seeded successfully!');
        $this->command->info('Test user: test@emigram.com / password');
        $this->command->info('Products created: ' . Product::count());
    }
}