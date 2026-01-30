<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Actions\Product;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ShowProductActionTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::factory()->create([
            'domain' => 'test-shop.com',
            'name' => 'Test Shop',
        ]);

        $this->product = Product::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'Test Product',
            'price' => 100.0,
            'currency' => 'EUR',
        ]);
    }

    public function test_show_product_successfully(): void
    {
        $user = \App\Models\User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/products/{$this->product->id}");

        $response->assertStatus(200);
        $responseData = $response->json();

        $data = $responseData['data'] ?? $responseData;
        $this->assertArrayHasKey('product', $data);
        $this->assertEquals($this->product->id, $data['product']['id']);
        $this->assertEquals('Test Product', $data['product']['name']);
    }

    public function test_show_product_not_found(): void
    {
        $user = \App\Models\User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/products/99999');

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'NOT_FOUND',
        ]);
    }
}
