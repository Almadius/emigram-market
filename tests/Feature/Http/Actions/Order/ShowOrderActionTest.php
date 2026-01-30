<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Actions\Order;

use App\Domains\Cart\DTOs\CartItemDTO;
use App\Domains\Cart\Services\CartService;
use App\Domains\Order\Commands\CreateOrderCommand;
use App\Domains\Order\Services\OrderService;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ShowOrderActionTest extends TestCase
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

    public function test_show_order_successfully(): void
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

        $command = new CreateOrderCommand(
            userId: $this->user->id,
            shopId: $this->shop->id,
            shopDomain: $this->shop->domain,
            items: [$item],
            currency: 'EUR'
        );

        $order = $this->orderService->createOrder($command);
        $orderId = $order->getId();

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/orders/{$orderId}");

        $response->assertStatus(200);
        $responseData = $response->json();

        $data = $responseData['data'] ?? $responseData;
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertEquals($orderId, $data['id']);
        $this->assertEquals(200.0, $data['total']);
    }

    public function test_show_order_not_found(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/orders/99999');

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'NOT_FOUND',
        ]);
    }
}
