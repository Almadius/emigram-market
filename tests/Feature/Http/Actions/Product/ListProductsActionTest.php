<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Actions\Product;

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ListProductsActionTest extends TestCase
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

    public function testListProductsSuccessfully(): void
    {
        $user = \App\Models\User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertArrayHasKey('page', $responseData);
        $this->assertArrayHasKey('per_page', $responseData);
        $this->assertIsArray($responseData['data']);
        $this->assertGreaterThanOrEqual(1, count($responseData['data']));
    }

    public function testListProductsWithFilters(): void
    {
        $user = \App\Models\User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/products?shop_id=' . $this->shop->id);

        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertArrayHasKey('data', $responseData);
        $this->assertIsArray($responseData['data']);
    }
}


