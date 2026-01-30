<?php

declare(strict_types=1);

namespace App\Http\Actions\Shop;

use App\Domains\Agent\Services\AgentService;
use App\Domains\Order\Contracts\OrderRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Action для получения webhook обновлений статусов заказов от магазинов
 */
final class WebhookStatusAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly AgentService $agentService,
    ) {}

    public function __invoke(Request $request, string $shopDomain): JsonResponse
    {
        $validated = $request->validate([
            'shop_order_id' => ['required', 'string'],
            'status' => ['required', 'string'],
            'tracking_number' => ['sometimes', 'string', 'nullable'],
            'tracking_url' => ['sometimes', 'string', 'nullable'],
            'metadata' => ['sometimes', 'array'],
        ]);

        try {
            $shopOrderId = $validated['shop_order_id'];

            // Ищем заказ по shop_order_id
            $order = $this->findOrderByShopOrderId($shopOrderId, $shopDomain);

            if ($order === null) {
                Log::warning('Webhook: Order not found', [
                    'shop_domain' => $shopDomain,
                    'shop_order_id' => $shopOrderId,
                ]);

                return response()->json([
                    'error' => 'Order not found',
                ], 404);
            }

            // Обновляем статус заказа
            $this->updateOrderStatus(
                $order,
                $validated['status'],
                $validated['tracking_number'] ?? null,
                $validated['tracking_url'] ?? null,
                $validated['metadata'] ?? [],
                $shopOrderId
            );

            Log::info('Webhook: Order status updated', [
                'order_id' => $order->getId(),
                'shop_domain' => $shopDomain,
                'shop_order_id' => $shopOrderId,
                'status' => $validated['status'],
            ]);

            return response()->json([
                'message' => 'Order status updated successfully',
                'order_id' => $order->getId(),
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook: Failed to update order status', [
                'shop_domain' => $shopDomain,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to process webhook',
            ], 500);
        }
    }

    /**
     * Находит заказ по shop_order_id
     */
    private function findOrderByShopOrderId(string $shopOrderId, string $shopDomain): ?\App\Domains\Order\DTOs\OrderDTO
    {
        return $this->orderRepository->findByShopOrderId($shopOrderId, $shopDomain);
    }

    /**
     * Обновляет статус заказа
     */
    private function updateOrderStatus(
        \App\Domains\Order\DTOs\OrderDTO $order,
        string $shopStatus,
        ?string $trackingNumber,
        ?string $trackingUrl,
        array $metadata,
        string $shopOrderId
    ): void {
        // Маппим статус магазина в статус EMIGRAM
        $emigramStatus = $this->mapShopStatusToEmigramStatus($shopStatus);

        // Обновляем статус заказа
        $this->orderRepository->updateStatus($order->getId(), $emigramStatus->value);

        // Обновляем метаданные (tracking_number, tracking_url)
        $existingMetadata = $order->getMetadata();
        $updatedMetadata = array_merge($existingMetadata, [
            'shop_status' => $shopStatus,
            'last_updated_at' => now()->toIso8601String(),
        ]);

        if ($trackingNumber !== null) {
            $updatedMetadata['tracking_number'] = $trackingNumber;
        }

        if ($trackingUrl !== null) {
            $updatedMetadata['tracking_url'] = $trackingUrl;
        }

        $updatedMetadata = array_merge($updatedMetadata, $metadata);

        // Обновляем метаданные через репозиторий
        // Используем shop_order_id из параметра (может быть обновлен)
        $this->orderRepository->updateShopOrderInfo(
            $order->getId(),
            $shopOrderId,
            $updatedMetadata
        );
    }

    /**
     * Маппит статус магазина в статус EMIGRAM
     */
    private function mapShopStatusToEmigramStatus(string $shopStatus): \App\Domains\Order\Enums\OrderStatusEnum
    {
        return match (strtolower($shopStatus)) {
            'pending', 'processing', 'confirmed' => \App\Domains\Order\Enums\OrderStatusEnum::PROCESSING,
            'shipped', 'in_transit', 'on_the_way' => \App\Domains\Order\Enums\OrderStatusEnum::SHIPPED,
            'delivered', 'completed' => \App\Domains\Order\Enums\OrderStatusEnum::DELIVERED,
            'cancelled', 'canceled', 'refunded' => \App\Domains\Order\Enums\OrderStatusEnum::CANCELLED,
            default => \App\Domains\Order\Enums\OrderStatusEnum::PENDING,
        };
    }
}
