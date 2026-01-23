<?php

declare(strict_types=1);

namespace App\Domains\Agent\Services;

use App\Domains\Agent\Contracts\ShopIntegrationInterface;
use App\Domains\Agent\DTOs\ShopOrderRequestDTO;
use App\Domains\Agent\DTOs\ShopOrderResponseDTO;
use App\Domains\Agent\Exceptions\ShopIntegrationException;
use App\Domains\Order\Contracts\OrderRepositoryInterface;
use App\Domains\Order\DTOs\OrderDTO;
use App\Domains\Order\Enums\OrderStatusEnum;
use App\Domains\User\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для агентской модели - автоматическое создание заказов в магазинах
 */
final class AgentService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ShopIntegrationFactory $integrationFactory,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * Создает заказ в магазине от имени EMIGRAM
     *
     * @param int $emigramOrderId ID заказа в EMIGRAM
     * @return ShopOrderResponseDTO Ответ от магазина
     * @throws ShopIntegrationException
     */
    public function createOrderInShop(int $emigramOrderId): ShopOrderResponseDTO
    {
        $order = $this->orderRepository->findById($emigramOrderId);
        
        if ($order === null) {
            throw ShopIntegrationException::orderCreationFailed('unknown', 'Order not found');
        }

        // Группируем товары по магазинам
        $itemsByShop = $this->groupItemsByShop($order);
        
        if (empty($itemsByShop)) {
            throw ShopIntegrationException::orderCreationFailed($order->getShopDomain(), 'No items found');
        }

        // Получаем интеграцию для магазина
        $integration = $this->integrationFactory->getIntegration($order->getShopDomain());
        
        if (!$integration->isAvailable()) {
            throw ShopIntegrationException::shopUnavailable($order->getShopDomain());
        }

        // Формируем запрос для магазина
        $request = $this->buildShopOrderRequest($order, $itemsByShop[$order->getShopDomain()]);

        try {
            // Создаем заказ в магазине
            $response = $integration->createOrder($request);

            // Обновляем заказ в EMIGRAM с информацией от магазина
            $this->updateOrderWithShopInfo($order, $response);

            Log::info('Order created in shop', [
                'emigram_order_id' => $emigramOrderId,
                'shop_domain' => $order->getShopDomain(),
                'shop_order_id' => $response->getShopOrderId(),
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to create order in shop', [
                'emigram_order_id' => $emigramOrderId,
                'shop_domain' => $order->getShopDomain(),
                'error' => $e->getMessage(),
            ]);

            throw ShopIntegrationException::orderCreationFailed($order->getShopDomain(), $e->getMessage());
        }
    }

    /**
     * Обновляет статус заказа в магазине
     *
     * @param int $emigramOrderId ID заказа в EMIGRAM
     * @return string Новый статус
     * @throws ShopIntegrationException
     */
    public function syncOrderStatus(int $emigramOrderId): string
    {
        $order = $this->orderRepository->findById($emigramOrderId);
        
        if ($order === null) {
            throw ShopIntegrationException::orderCreationFailed('unknown', 'Order not found');
        }

        // Получаем shop_order_id из заказа
        $shopOrderId = $order->getShopOrderId();
        
        if ($shopOrderId === null) {
            throw ShopIntegrationException::orderCreationFailed($order->getShopDomain(), 'Shop order ID not found');
        }

        $integration = $this->integrationFactory->getIntegration($order->getShopDomain());
        $status = $integration->getOrderStatus($shopOrderId);

        // Обновляем статус заказа
        $emigramStatus = $this->mapShopStatusToEmigramStatus($status);
        $this->orderRepository->updateStatus($emigramOrderId, $emigramStatus->value);

        return $status;
    }

    /**
     * Группирует товары заказа по магазинам
     *
     * @param OrderDTO $order Заказ
     * @return array<string, array> Товары по магазинам
     */
    private function groupItemsByShop(OrderDTO $order): array
    {
        $itemsByShop = [];
        
        foreach ($order->getItems() as $item) {
            // OrderItemDTO не имеет shop_domain, используем shop_domain заказа
            $shopDomain = $order->getShopDomain();
            
            if (!isset($itemsByShop[$shopDomain])) {
                $itemsByShop[$shopDomain] = [];
            }
            
            $itemsByShop[$shopDomain][] = [
                'product_id' => $item->getProductId(),
                'quantity' => $item->getQuantity(),
                'price' => $item->getPrice(),
            ];
        }
        
        return $itemsByShop;
    }

    /**
     * Формирует запрос для создания заказа в магазине
     *
     * @param OrderDTO $order Заказ EMIGRAM
     * @param array $items Товары для магазина
     * @return ShopOrderRequestDTO
     */
    private function buildShopOrderRequest(OrderDTO $order, array $items): ShopOrderRequestDTO
    {
        // Получаем адреса доставки и оплаты из заказа
        // Пока используем пустые массивы, так как OrderDTO не содержит адреса
        // В будущем можно добавить поля в OrderDTO или получать из User
        $shippingAddress = [];
        $billingAddress = [];

        // Получаем email пользователя
        $user = $this->userRepository->findById($order->getUserId());
        $customerEmail = $user?->getEmail();

        return new ShopOrderRequestDTO(
            emigramOrderId: $order->getId(),
            shopDomain: $order->getShopDomain(),
            items: $items,
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
            customerEmail: $customerEmail,
            customerPhone: null,
            metadata: [
                'emigram_order_id' => $order->getId(),
                'user_id' => $order->getUserId(),
            ],
        );
    }

    /**
     * Обновляет заказ с информацией от магазина
     *
     * @param OrderDTO $order Заказ EMIGRAM
     * @param ShopOrderResponseDTO $response Ответ от магазина
     */
    private function updateOrderWithShopInfo(OrderDTO $order, ShopOrderResponseDTO $response): void
    {
        $metadata = [
            'shop_status' => $response->getStatus(),
        ];

        if ($response->getTrackingNumber() !== null) {
            $metadata['tracking_number'] = $response->getTrackingNumber();
        }

        if ($response->getTrackingUrl() !== null) {
            $metadata['tracking_url'] = $response->getTrackingUrl();
        }

        $metadata = array_merge($metadata, $response->getMetadata());

        $this->orderRepository->updateShopOrderInfo(
            $order->getId(),
            $response->getShopOrderId(),
            $metadata
        );

        Log::info('Order updated with shop info', [
            'order_id' => $order->getId(),
            'shop_order_id' => $response->getShopOrderId(),
            'shop_status' => $response->getStatus(),
        ]);
    }

    /**
     * Маппит статус магазина в статус EMIGRAM
     *
     * @param string $shopStatus Статус в магазине
     * @return OrderStatusEnum Статус EMIGRAM
     */
    private function mapShopStatusToEmigramStatus(string $shopStatus): OrderStatusEnum
    {
        return match (strtolower($shopStatus)) {
            'pending', 'processing', 'confirmed' => OrderStatusEnum::PROCESSING,
            'shipped', 'in_transit' => OrderStatusEnum::SHIPPED,
            'delivered', 'completed' => OrderStatusEnum::DELIVERED,
            'cancelled', 'canceled' => OrderStatusEnum::CANCELLED,
            default => OrderStatusEnum::PENDING,
        };
    }
}
