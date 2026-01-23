<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class PriceResolveTest extends TestCase
{
    use RefreshDatabase;

    public function testPriceResolveRequiresAuthentication(): void
    {
        $response = $this->postJson('/api/v1/price/resolve', [
            'shop_domain' => 'example.com',
            'product_url' => 'https://example.com/product',
            'price_store' => 100.0,
            'currency' => 'EUR',
        ]);

        $response->assertStatus(401);
    }

    public function testPriceResolveWithValidData(): void
    {
        $user = User::factory()->create(['level' => 3]); // Gold
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/price/resolve', [
            'shop_domain' => 'example.com',
            'product_url' => 'https://example.com/product',
            'price_store' => 100.0,
            'currency' => 'EUR',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'price' => [
                'store_price',
                'emigram_price',
                'savings_absolute',
                'savings_percent',
            ],
        ]);
    }
}
