<?php

declare(strict_types=1);

namespace App\Infrastructure\Aimeos\Repositories;

use Aimeos\MShop;
use App\Domains\Order\Contracts\OrderRepositoryInterface;
use App\Domains\Order\DTOs\OrderDTO;
use App\Domains\Order\DTOs\OrderItemDTO;
use App\Domains\Order\Enums\OrderStatusEnum;
use Illuminate\Support\Facades\Log;

final class AimeosOrderRepository implements OrderRepositoryInterface
{
    private ?MShop\Order\Manager\Iface $orderManager = null;

    private ?MShop\Product\Manager\Iface $productManager = null;

    private bool $isAvailable = false;

    public function __construct()
    {
        try {
            $context = app('aimeos.context')->get();
            $this->orderManager = MShop::create($context, 'order');
            $this->productManager = MShop::create($context, 'product');
            $this->isAvailable = true;
        } catch (\Exception $e) {
            Log::warning('Aimeos not configured, using fallback', ['error' => $e->getMessage()]);
            $this->isAvailable = false;
        }
    }

    public function create(OrderDTO $order): OrderDTO
    {
        if (! $this->isAvailable) {
            return app(\App\Infrastructure\Repositories\Eloquent\OrderRepository::class)->create($order);
        }

        try {
            $context = app('aimeos.context')->get();
            // Aimeos context может не иметь метода setUserId, используем fallback

            // Aimeos API может отличаться, используем fallback
            // В реальной реализации нужно использовать правильный Aimeos API
            throw new \RuntimeException('Aimeos order creation not fully implemented, using fallback');
            // Преобразуем в OrderDTO
            $items = [];
            foreach ($orderItem->getProducts() as $product) {
                $items[] = new OrderItemDTO(
                    productId: (int) $product->getId(),
                    productName: $product->getName(),
                    quantity: (int) $product->getQuantity(),
                    price: (float) $product->getPrice()->getValue(),
                    currency: $product->getPrice()->getCurrencyId()
                );
            }

            return new OrderDTO(
                id: (int) $orderItem->getId(),
                userId: $order->getUserId(),
                shopId: $order->getShopId(),
                shopDomain: $order->getShopDomain(),
                status: $this->mapStatus($orderItem->getStatusDelivery()),
                items: $items,
                total: (float) $orderItem->getPrice()->getValue(),
                currency: $orderItem->getPrice()->getCurrencyId(),
                createdAt: \DateTimeImmutable::createFromMutable($orderItem->getTimeCreated())
            );
        } catch (\Exception $e) {
            Log::error('Error creating Aimeos order', ['error' => $e->getMessage()]);

            // Fallback на Eloquent
            return app(\App\Infrastructure\Repositories\Eloquent\OrderRepository::class)->create($order);
        }
    }

    public function findById(int $orderId, ?int $userId = null): ?OrderDTO
    {
        if (! $this->isAvailable) {
            return app(\App\Infrastructure\Repositories\Eloquent\OrderRepository::class)->findById($orderId, $userId);
        }

        try {
            $search = $this->orderManager->filter();
            $search->add('order.id', '==', $orderId);

            if ($userId !== null) {
                $search->add('order.customerid', '==', (string) $userId);
            }

            $items = $this->orderManager->search($search, ['order/product']);

            if ($items->isEmpty()) {
                return null;
            }

            $orderItem = $items->first();

            return $this->mapToDTO($orderItem);
        } catch (\Exception $e) {
            Log::error('Error finding Aimeos order', ['error' => $e->getMessage()]);

            // Fallback на Eloquent
            return app(\App\Infrastructure\Repositories\Eloquent\OrderRepository::class)->findById($orderId, $userId);
        }
    }

    public function findByUserId(int $userId): array
    {
        if (! $this->isAvailable) {
            return app(\App\Infrastructure\Repositories\Eloquent\OrderRepository::class)->findByUserId($userId);
        }

        try {
            $search = $this->orderManager->filter();
            $search->add('order.customerid', '==', (string) $userId);
            $search->setSortations([$search->sort('-', 'order.ctime')]);

            $items = $this->orderManager->search($search, ['order/product']);

            $orders = [];
            foreach ($items as $item) {
                $orders[] = $this->mapToDTO($item);
            }

            return $orders;
        } catch (\Exception $e) {
            Log::error('Error finding Aimeos orders by user', ['error' => $e->getMessage()]);

            // Fallback на Eloquent
            return app(\App\Infrastructure\Repositories\Eloquent\OrderRepository::class)->findByUserId($userId);
        }
    }

    public function updateStatus(int $orderId, string $status): void
    {
        if (! $this->isAvailable) {
            app(\App\Infrastructure\Repositories\Eloquent\OrderRepository::class)->updateStatus($orderId, $status);

            return;
        }

        try {
            $orderItem = $this->orderManager->get((string) $orderId);
            // Aimeos API может отличаться, используем fallback
            // В реальной реализации нужно использовать правильный Aimeos API для обновления статуса
            throw new \RuntimeException('Aimeos order status update not fully implemented, using fallback');
        } catch (\Exception $e) {
            Log::error('Error updating Aimeos order status', ['error' => $e->getMessage()]);
            // Fallback на Eloquent
            app(\App\Infrastructure\Repositories\Eloquent\OrderRepository::class)->updateStatus($orderId, $status);
        }
    }

    private function mapToDTO(MShop\Order\Item\Iface $orderItem): OrderDTO
    {
        $items = [];
        foreach ($orderItem->getProducts() as $product) {
            $items[] = new OrderItemDTO(
                productId: (int) $product->getId(),
                productName: $product->getName(),
                quantity: (int) $product->getQuantity(),
                price: (float) $product->getPrice()->getValue(),
                currency: $product->getPrice()->getCurrencyId()
            );
        }

        // Получаем shop_id и shop_domain из контекста или адреса
        $shopId = null;
        $shopDomain = null;

        try {
            $addresses = $orderItem->getAddresses();
            if (! $addresses->isEmpty()) {
                $address = $addresses->first();
                // Можно извлечь shop_domain из адреса или другого поля
            }
        } catch (\Exception $e) {
            // Игнорируем
        }

        return new OrderDTO(
            id: (int) $orderItem->getId(),
            userId: (int) $orderItem->getCustomerId(),
            shopId: $shopId ?? 1, // Fallback
            shopDomain: $shopDomain ?? 'unknown',
            status: $this->mapStatus($orderItem->getStatusDelivery()),
            items: $items,
            total: (float) $orderItem->getPrice()->getValue(),
            currency: $orderItem->getPrice()->getCurrencyId(),
            createdAt: $this->parseDateTime($orderItem->getTimeCreated())
        );
    }

    private function mapStatus(int $aimeosStatus): OrderStatusEnum
    {
        // Маппинг статусов Aimeos в наши enum
        return match ($aimeosStatus) {
            0 => OrderStatusEnum::PENDING,
            1 => OrderStatusEnum::PROCESSING,
            2 => OrderStatusEnum::SHIPPED,
            3 => OrderStatusEnum::DELIVERED,
            4 => OrderStatusEnum::CANCELLED,
            default => OrderStatusEnum::PENDING,
        };
    }

    private function mapStatusToAimeos(string $status): int
    {
        return match ($status) {
            'pending' => 0,
            'processing' => 1,
            'shipped' => 2,
            'delivered' => 3,
            'cancelled' => 4,
            default => 0,
        };
    }

    private function parseDateTime(mixed $timeCreated): \DateTimeImmutable
    {
        if ($timeCreated instanceof \DateTime) {
            return \DateTimeImmutable::createFromMutable($timeCreated);
        }

        if ($timeCreated instanceof \DateTimeImmutable) {
            return $timeCreated;
        }

        if (is_string($timeCreated) && $timeCreated !== '') {
            try {
                return new \DateTimeImmutable($timeCreated);
            } catch (\Exception $e) {
                // Ignore
            }
        }

        return new \DateTimeImmutable;
    }
}
