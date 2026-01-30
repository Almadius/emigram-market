<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Eloquent;

use App\Domains\Order\Contracts\OrderRepositoryInterface;
use App\Domains\Order\DTOs\OrderDTO;
use App\Domains\Order\DTOs\OrderItemDTO;
use App\Domains\Order\Enums\OrderStatusEnum;
use App\Models\Order;
use App\Models\OrderItem;

final class OrderRepository implements OrderRepositoryInterface
{
    public function create(OrderDTO $order): OrderDTO
    {
        $orderModel = Order::create([
            'user_id' => $order->getUserId(),
            'shop_id' => $order->getShopId(),
            'shop_domain' => $order->getShopDomain(),
            'status' => $order->getStatus()->value,
            'total' => $order->getTotal(),
            'currency' => $order->getCurrency(),
        ]);

        foreach ($order->getItems() as $item) {
            OrderItem::create([
                'order_id' => $orderModel->id,
                'product_id' => $item->getProductId(),
                'product_name' => $item->getProductName(),
                'quantity' => $item->getQuantity(),
                'price' => $item->getPrice(),
                'currency' => $item->getCurrency(),
            ]);
        }

        return new OrderDTO(
            id: $orderModel->id,
            userId: $orderModel->user_id,
            shopId: $orderModel->shop_id,
            shopDomain: $orderModel->shop_domain,
            status: OrderStatusEnum::from($orderModel->status),
            items: $order->getItems(),
            total: (float) $orderModel->total,
            currency: $orderModel->currency,
            createdAt: \DateTimeImmutable::createFromMutable($orderModel->created_at),
            shopOrderId: $orderModel->shop_order_id,
            metadata: $orderModel->metadata ?? []
        );
    }

    public function findById(int $orderId, ?int $userId = null): ?OrderDTO
    {
        $query = Order::with('items');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $order = $query->find($orderId);

        if ($order === null) {
            return null;
        }

        $items = $order->items->map(function (OrderItem $item) {
            return new OrderItemDTO(
                productId: $item->product_id,
                productName: $item->product_name,
                quantity: $item->quantity,
                price: (float) $item->price,
                currency: $item->currency
            );
        })->toArray();

        return new OrderDTO(
            id: $order->id,
            userId: $order->user_id,
            shopId: $order->shop_id,
            shopDomain: $order->shop_domain,
            status: OrderStatusEnum::from($order->status),
            items: $items,
            total: (float) $order->total,
            currency: $order->currency,
            createdAt: \DateTimeImmutable::createFromMutable($order->created_at),
            shopOrderId: $order->shop_order_id,
            metadata: $order->metadata ?? []
        );
    }

    /**
     * @return array<OrderDTO>
     */
    public function findByUserId(int $userId): array
    {
        $orders = Order::with('items')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $orders->map(function (Order $order) {
            $items = $order->items->map(function (OrderItem $item) {
                return new OrderItemDTO(
                    productId: $item->product_id,
                    productName: $item->product_name,
                    quantity: $item->quantity,
                    price: (float) $item->price,
                    currency: $item->currency
                );
            })->toArray();

            return new OrderDTO(
                id: $order->id,
                userId: $order->user_id,
                shopId: $order->shop_id,
                shopDomain: $order->shop_domain,
                status: OrderStatusEnum::from($order->status),
                items: $items,
                total: (float) $order->total,
                currency: $order->currency,
                createdAt: \DateTimeImmutable::createFromMutable($order->created_at),
                shopOrderId: $order->shop_order_id,
                metadata: $order->metadata ?? []
            );
        })->toArray();
    }

    public function updateStatus(int $orderId, string $status): void
    {
        Order::where('id', $orderId)->update(['status' => $status]);
    }

    public function updateShopOrderInfo(int $orderId, string $shopOrderId, array $metadata = []): void
    {
        $order = Order::find($orderId);

        if ($order === null) {
            return;
        }

        $existingMetadata = $order->metadata ?? [];
        $mergedMetadata = array_merge($existingMetadata, $metadata);

        $order->update([
            'shop_order_id' => $shopOrderId,
            'metadata' => $mergedMetadata,
        ]);
    }

    public function findByShopOrderId(string $shopOrderId, string $shopDomain): ?OrderDTO
    {
        $order = Order::where('shop_order_id', $shopOrderId)
            ->where('shop_domain', $shopDomain)
            ->with('items')
            ->first();

        if ($order === null) {
            return null;
        }

        $items = $order->items->map(function (OrderItem $item) {
            return new OrderItemDTO(
                productId: $item->product_id,
                productName: $item->product_name,
                quantity: $item->quantity,
                price: (float) $item->price,
                currency: $item->currency
            );
        })->toArray();

        return new OrderDTO(
            id: $order->id,
            userId: $order->user_id,
            shopId: $order->shop_id,
            shopDomain: $order->shop_domain,
            status: OrderStatusEnum::from($order->status),
            items: $items,
            total: (float) $order->total,
            currency: $order->currency,
            createdAt: \DateTimeImmutable::createFromMutable($order->created_at),
            shopOrderId: $order->shop_order_id,
            metadata: $order->metadata ?? []
        );
    }
}
