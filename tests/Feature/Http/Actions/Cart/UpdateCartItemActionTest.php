<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Actions\Cart;

use App\Domains\Cart\DTOs\CartItemDTO;
use App\Domains\Cart\Services\CartService;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UpdateCartItemActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Shop $shop;

    private Product $product;

    private CartService $cartService;

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

        $this->cartService = $this->app->make(CartService::class);
    }

    public function test_update_item_quantity(): void
    {
        // Add item to cart first
        $item = new CartItemDTO(
            productId: $this->product->id,
            productName: $this->product->name,
            quantity: 2,
            price: 100.0,
            currency: 'EUR',
            shopId: $this->shop->id,
            shopDomain: $this->shop->domain,
        );
        $this->cartService->addItem($this->user->id, $item);

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/cart/items/{$this->product->id}", [
                'quantity' => 5,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Item updated',
        ]);

        // Verify item was updated
        $cartResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/cart');

        $cartData = $cartResponse->json();
        $data = $cartData['data'] ?? $cartData;
        $this->assertCount(1, $data['items']);
        $this->assertEquals(5, $data['items'][0]['quantity']);
        $this->assertEquals(500.0, $data['total']);
    }
}
