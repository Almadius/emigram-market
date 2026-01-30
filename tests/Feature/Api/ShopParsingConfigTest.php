<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ShopParsingConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_parsing_config_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/shops/example.com/parsing-config');
        $response->assertStatus(401);
    }

    public function test_parsing_config_returns_selectors_from_database(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Shop::factory()->create([
            'domain' => 'example.com',
            'parsing_selectors' => [
                'price' => ['.price', '[data-price]'],
                'currency' => ['.currency'],
                'name' => ['h1'],
            ],
            'crawl_interval_minutes' => 15,
        ]);

        $response = $this->getJson('/api/v1/shops/example.com/parsing-config');

        $response->assertStatus(200);
        $response->assertJson([
            'shop_domain' => 'example.com',
            'known_shop' => true,
            'crawl_interval_minutes' => 15,
            'selectors' => [
                'price' => ['.price', '[data-price]'],
                'currency' => ['.currency'],
                'name' => ['h1'],
            ],
        ]);
        $response->assertHeader('ETag');
    }

    public function test_parsing_config_returns_defaults_when_shop_is_unknown(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/shops/unknown-shop.com/parsing-config');

        $response->assertStatus(200);
        $response->assertJsonPath('known_shop', false);
        $response->assertJsonStructure([
            'selectors' => ['price', 'currency', 'name'],
        ]);
    }
}
