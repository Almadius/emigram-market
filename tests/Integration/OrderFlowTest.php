<?php

declare(strict_types=1);

namespace Tests\Integration;

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

/**
 * Integration test for complete order flow:
 * 1. Add products to cart
 * 2. View cart
 * 3. Create order
 * 4. View order
 * 5. Verify order status
 */
final class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Shop $shop1;
    private Shop $shop2;
    private Product $product1;
    private Product $product2;
    private CartService $cartService;
    private OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->shop1 = Shop::factory()->create([
            'domain' => 'shop1.com',
            'name' => 'Shop 1',
        ]);

        $this->shop2 = Shop::factory()->create([
            'domain' => 'shop2.com',
            'name' => 'Shop 2',
        ]);

        $this->product1 = Product::factory()->create([
            'shop_id' => $this->shop1->id,
            'name' => 'Product 1',
            'price' => 100.0,
            'currency' => 'EUR',
        ]);

        $this->product2 = Product::factory()->create([
            'shop_id' => $this->shop2->id,
            'name' => 'Product 2',
            'price' => 200.0,
            'currency' => 'EUR',
        ]);

        $this->cartService = $this->app->make(CartService::class);
        $this->orderService = $this->app->make(OrderService::class);
    }

    public function testCompleteOrderFlow(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        // Step 1: Add products to cart via API
        $response1 = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/cart/items', [
                'product_id' => $this->product1->id,
                'quantity' => 2,
            ]);
        $response1->assertStatus(201);

        $response2 = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/cart/items', [
                'product_id' => $this->product2->id,
                'quantity' => 1,
            ]);
        $response2->assertStatus(201);

        // Step 2: View cart via API
        $cartResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/cart');
        
        $cartResponse->assertStatus(200);
        $cartData = $cartResponse->json();
        $items = $cartData['data']['items'] ?? $cartData['items'] ?? [];
        
        $this->assertCount(2, $items);
        $this->assertEquals(400.0, $cartData['data']['total'] ?? $cartData['total'] ?? 0);

        // Step 3: Create order for shop1 via API
        $orderResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/orders', [
                'shop_domain' => $this->shop1->domain,
                'shop_id' => $this->shop1->id,
                'currency' => 'EUR',
            ]);

        $orderResponse->assertStatus(201);
        $orderData = $orderResponse->json();
        $orderId = $orderData['data']['id'] ?? $orderData['id'] ?? null;
        
        $this->assertNotNull($orderId);
        $this->assertEquals('pending', $orderData['data']['status'] ?? $orderData['status'] ?? null);
        $this->assertEquals(200.0, $orderData['data']['total'] ?? $orderData['total'] ?? 0);

        // Step 4: View order via API
        $viewOrderResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/orders/{$orderId}");

        $viewOrderResponse->assertStatus(200);
        $viewOrderData = $viewOrderResponse->json();
        $viewData = $viewOrderData['data'] ?? $viewOrderData;
        
        $this->assertEquals($orderId, $viewData['id']);
        $this->assertEquals('pending', $viewData['status']);
        $this->assertCount(1, $viewData['items'] ?? []);
        $this->assertEquals($this->product1->id, $viewData['items'][0]['product_id'] ?? null);

        // Step 5: Verify cart still has remaining items
        $cartAfterOrder = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/cart');
        
        $cartAfterData = $cartAfterOrder->json();
        $itemsAfter = $cartAfterData['data']['items'] ?? $cartAfterData['items'] ?? [];
        
        // Cart should still have product2 from shop2
        $this->assertCount(1, $itemsAfter);
        $this->assertEquals($this->product2->id, $itemsAfter[0]['product_id'] ?? null);
    }

    public function testOrderFlowWithMultipleShops(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        // Add products from different shops
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/cart/items', [
                'product_id' => $this->product1->id,
                'quantity' => 1,
            ]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/cart/items', [
                'product_id' => $this->product2->id,
                'quantity' => 1,
            ]);

        // Create order for shop1
        $order1Response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/orders', [
                'shop_domain' => $this->shop1->domain,
                'shop_id' => $this->shop1->id,
                'currency' => 'EUR',
            ]);

        $order1Response->assertStatus(201);
        $order1Id = $order1Response->json('data.id') ?? $order1Response->json('id');

        // Create order for shop2
        $order2Response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/orders', [
                'shop_domain' => $this->shop2->domain,
                'shop_id' => $this->shop2->id,
                'currency' => 'EUR',
            ]);

        $order2Response->assertStatus(201);
        $order2Id = $order2Response->json('data.id') ?? $order2Response->json('id');

        // Verify both orders exist
        $this->assertNotEquals($order1Id, $order2Id);

        // List all orders
        $ordersResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/orders');

        $ordersResponse->assertStatus(200);
        $ordersData = $ordersResponse->json();
        $orders = $ordersData['data']['data'] ?? $ordersData['data'] ?? $ordersData;
        
        $this->assertGreaterThanOrEqual(2, count($orders));
        
        // Verify cart is empty after all orders
        $finalCart = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/cart');
        
        $finalCartData = $finalCart->json();
        $finalItems = $finalCartData['data']['items'] ?? $finalCartData['items'] ?? [];
        $this->assertCount(0, $finalItems);
    }
}

