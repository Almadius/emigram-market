<?php

declare(strict_types=1);

namespace App\Domains\Agent\Contracts;

use App\Domains\Agent\DTOs\CreateShopOrderRequestDTO;
use App\Domains\Agent\DTOs\CreateShopOrderResponseDTO;

/**
 * Интерфейс для агентов магазинов
 * Каждый магазин может иметь свой адаптер для создания заказов
 */
interface ShopAgentInterface
{
    /**
     * Создает заказ в магазине от имени EMIGRAM
     */
    public function createOrder(CreateShopOrderRequestDTO $request): CreateShopOrderResponseDTO;

    /**
     * Проверяет, поддерживается ли магазин этим агентом
     */
    public function supports(string $shopDomain): bool;

    /**
     * Получает статус заказа в магазине
     */
    public function getOrderStatus(string $shopOrderId, string $shopDomain): ?string;
}

