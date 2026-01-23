<?php

declare(strict_types=1);

namespace App\Domains\Order\Contracts;

use App\Domains\Order\DTOs\OrderDTO;

interface OrderRepositoryInterface
{
    public function create(OrderDTO $order): OrderDTO;

    public function findById(int $orderId, ?int $userId = null): ?OrderDTO;

    /**
     * @return array<OrderDTO>
     */
    public function findByUserId(int $userId): array;

    public function updateStatus(int $orderId, string $status): void;

    /**
     * Обновляет информацию о заказе в магазине
     *
     * @param int $orderId ID заказа
     * @param string $shopOrderId ID заказа в магазине
     * @param array $metadata Метаданные (tracking_number, tracking_url, shop_status и т.д.)
     */
    public function updateShopOrderInfo(int $orderId, string $shopOrderId, array $metadata = []): void;

    /**
     * Находит заказ по shop_order_id и shop_domain
     */
    public function findByShopOrderId(string $shopOrderId, string $shopDomain): ?OrderDTO;
}



