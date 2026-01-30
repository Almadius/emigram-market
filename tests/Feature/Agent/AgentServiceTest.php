<?php

declare(strict_types=1);

namespace Tests\Feature\Agent;

use App\Domains\Agent\Exceptions\ShopIntegrationException;
use App\Domains\Agent\Services\AgentService;
use App\Domains\Order\Contracts\OrderRepositoryInterface;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AgentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AgentService $agentService;

    private OrderRepositoryInterface $orderRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = app(OrderRepositoryInterface::class);
        $this->agentService = app(AgentService::class);
    }

    public function test_create_order_in_shop_throws_exception_when_order_not_found(): void
    {
        $this->expectException(ShopIntegrationException::class);
        $this->expectExceptionMessage('Order not found');

        $this->agentService->createOrderInShop(99999);
    }

    public function test_create_order_in_shop_throws_exception_when_shop_unavailable(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create([
            'is_active' => false,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'shop_domain' => $shop->domain,
        ]);

        $this->expectException(ShopIntegrationException::class);

        $this->agentService->createOrderInShop($order->id);
    }

    public function test_sync_order_status_throws_exception_when_order_not_found(): void
    {
        $this->expectException(ShopIntegrationException::class);
        $this->expectExceptionMessage('Order not found');

        $this->agentService->syncOrderStatus(99999);
    }

    public function test_sync_order_status_throws_exception_when_shop_order_id_not_found(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create([
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'shop_domain' => $shop->domain,
            'shop_order_id' => null,
        ]);

        $this->expectException(ShopIntegrationException::class);
        $this->expectExceptionMessage('Shop order ID not found');

        $this->agentService->syncOrderStatus($order->id);
    }
}
