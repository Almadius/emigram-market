<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Actions\Order;

use App\Domains\Cart\DTOs\CartItemDTO;
use App\Domains\Cart\Services\CartService;
use App\Domains\Order\Services\OrderService;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ListOrdersActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Shop $shop;

    private Product $product;

    private CartService $cartService;

    private OrderService $orderService;

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
        $this->orderService = $this->app->make(OrderService::class);
    }

    public function test_list_orders_with_orders(): void
    {
        // Create an order first
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

        // Create order
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/orders', [
                'shop_domain' => $this->shop->domain,
                'shop_id' => $this->shop->id,
                'currency' => 'EUR',
            ]);

        // List orders
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/orders');

        $response->assertStatus(200);
        $responseData = $response->json();

        // OrderListResource returns ['data' => ...], and Laravel wraps it in 'data' again
        $data = $responseData['data'] ?? $responseData;
        if (isset($data['data'])) {
            $orders = $data['data'];
        } else {
            $orders = $data;
        }

        $this->assertIsArray($orders);
        $this->assertGreaterThanOrEqual(1, count($orders));

        if (count($orders) > 0) {
            $order = $orders[0];
            $this->assertArrayHasKey('id', $order);
            $this->assertArrayHasKey('status', $order);
            $this->assertArrayHasKey('total', $order);
        }
    }

    public function test_list_orders_with_no_orders(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/orders');

        $response->assertStatus(200);
        $responseData = $response->json();

        // OrderListResource returns ['data' => ...], and Laravel wraps it in 'data' again
        $data = $responseData['data'] ?? $responseData;
        if (isset($data['data'])) {
            $orders = $data['data'];
        } else {
            $orders = $data;
        }

        $this->assertIsArray($orders);
        $this->assertCount(0, $orders);
    }
}
