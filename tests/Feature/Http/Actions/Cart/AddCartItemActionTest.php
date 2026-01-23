<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Actions\Cart;

use App\Domains\Cart\Services\CartService;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AddCartItemActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Shop $shop;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

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

    public function testAddItemSuccessfully(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/cart/items', [
                'product_id' => $this->product->id,
                'quantity' => 2,
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Item added to cart',
        ]);

        // Verify item was added to cart
        $cartResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/cart');

        $cartResponse->assertStatus(200);
        $cartData = $cartResponse->json();
        
        // CartResource wraps data in 'data' key
        $items = $cartData['data']['items'] ?? $cartData['items'] ?? [];
        $this->assertCount(1, $items);
        $this->assertEquals($this->product->id, $items[0]['product_id']);
        $this->assertEquals(2, $items[0]['quantity']);
    }

    public function testAddItemWithNonExistentProduct(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/cart/items', [
                'product_id' => 99999,
                'quantity' => 1,
            ]);

        // Validation should catch non-existent product
        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'VALIDATION_ERROR',
        ]);
    }
}
