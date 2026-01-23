<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\Domains\Cart\DTOs\CartItemDTO;
use App\Domains\Order\Commands\CreateOrderCommand;
use App\Domains\Order\Contracts\OrderRepositoryInterface;
use App\Domains\Order\DTOs\OrderDTO;
use App\Domains\Order\DTOs\OrderItemDTO;
use App\Domains\Order\Enums\OrderStatusEnum;
use App\Domains\Order\Events\OrderCreated;
use App\Domains\Order\Queries\GetOrderQuery;
use App\Services\MetricsService;
use Illuminate\Contracts\Events\Dispatcher;

final class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly Dispatcher $eventDispatcher,
        private readonly MetricsService $metrics,
    ) {
    }

    public function createOrder(CreateOrderCommand $command): OrderDTO
    {
        // Преобразуем CartItemDTO в OrderItemDTO
        $orderItems = array_map(
            fn(CartItemDTO $item) => new OrderItemDTO(
                productId: $item->getProductId(),
                productName: $item->getProductName(),
                quantity: $item->getQuantity(),
                price: $item->getPrice(),
                currency: $item->getCurrency()
            ),
            $command->getItems()
        );

        // Рассчитываем общую сумму
        $total = array_sum(array_map(fn(OrderItemDTO $item) => $item->getTotal(), $orderItems));

        // Создаём заказ
        $order = new OrderDTO(
            id: 0, // Будет установлен репозиторием
            userId: $command->getUserId(),
            shopId: $command->getShopId(),
            shopDomain: $command->getShopDomain(),
            status: OrderStatusEnum::PENDING,
            items: $orderItems,
            total: $total,
            currency: $command->getCurrency(),
            createdAt: new \DateTimeImmutable()
        );

        $createdOrder = $this->orderRepository->create($order);

        // Записываем метрику
        $this->metrics->increment('orders.created', 1, [
            'shop_domain' => $createdOrder->getShopDomain(),
        ]);

        // Отправляем событие
        $this->eventDispatcher->dispatch(
            new OrderCreated($createdOrder)
        );

        return $createdOrder;
    }

    public function getOrder(GetOrderQuery $query): ?OrderDTO
    {
        return $this->orderRepository->findById(
            $query->getOrderId(),
            $query->getUserId()
        );
    }

    /**
     * @return array<OrderDTO>
     */
    public function getUserOrders(int $userId): array
    {
        return $this->orderRepository->findByUserId($userId);
    }
}



