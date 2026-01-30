<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Actions\Order;

use App\Domains\Cart\DTOs\CartItemDTO;
use App\Domains\Cart\Services\CartService;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class CreateOrderActionTest extends TestCase
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

    public function test_create_order_successfully(): void
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
            ->postJson('/api/v1/orders', [
                'shop_domain' => $this->shop->domain,
                'shop_id' => $this->shop->id,
                'currency' => 'EUR',
            ]);

        $response->assertStatus(201);
        $orderData = $response->json();

        // Laravel Resource wraps in 'data' key
        $data = $orderData['data'] ?? $orderData;

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertEquals('pending', $data['status']);
        $this->assertEquals(200.0, $data['total']);
        $this->assertEquals($this->shop->domain, $data['shop_domain']);
        $this->assertArrayHasKey('items', $data);
        $this->assertIsArray($data['items']);
        $this->assertGreaterThanOrEqual(1, count($data['items']));
    }

    public function test_create_order_with_empty_cart(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/orders', [
                'shop_domain' => $this->shop->domain,
                'shop_id' => $this->shop->id,
                'currency' => 'EUR',
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'BAD_REQUEST',
        ]);
    }
}
