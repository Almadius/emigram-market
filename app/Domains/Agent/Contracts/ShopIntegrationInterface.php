<?php

declare(strict_types=1);

namespace App\Domains\Agent\Contracts;

use App\Domains\Agent\DTOs\ShopOrderRequestDTO;
use App\Domains\Agent\DTOs\ShopOrderResponseDTO;

/**
 * Интерфейс для интеграции с магазинами
 * Каждый магазин должен реализовать этот интерфейс
 */
interface ShopIntegrationInterface
{
    /**
     * Создает заказ в магазине
     *
     * @param  ShopOrderRequestDTO  $request  Данные заказа
     * @return ShopOrderResponseDTO Ответ от магазина
     *
     * @throws \App\Domains\Agent\Exceptions\ShopIntegrationException
     */
    public function createOrder(ShopOrderRequestDTO $request): ShopOrderResponseDTO;

    /**
     * Получает статус заказа в магазине
     *
     * @param  string  $shopOrderId  ID заказа в магазине
     * @return string Статус заказа
     *
     * @throws \App\Domains\Agent\Exceptions\ShopIntegrationException
     */
    public function getOrderStatus(string $shopOrderId): string;

    /**
     * Отменяет заказ в магазине
     *
     * @param  string  $shopOrderId  ID заказа в магазине
     * @return bool Успешность отмены
     *
     * @throws \App\Domains\Agent\Exceptions\ShopIntegrationException
     */
    public function cancelOrder(string $shopOrderId): bool;

    /**
     * Проверяет доступность магазина
     *
     * @return bool Доступен ли магазин
     */
    public function isAvailable(): bool;
}
