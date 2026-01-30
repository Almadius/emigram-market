<?php

declare(strict_types=1);

namespace App\Domains\Agent\Adapters;

use App\Domains\Agent\Contracts\ShopAgentInterface;
use App\Domains\Agent\DTOs\CreateShopOrderRequestDTO;
use App\Domains\Agent\DTOs\CreateShopOrderResponseDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Универсальный агент для магазинов с REST API
 * Используется как fallback для магазинов без специфичного адаптера
 */
final class GenericShopAgent implements ShopAgentInterface
{
    public function createOrder(CreateShopOrderRequestDTO $request): CreateShopOrderResponseDTO
    {
        $shopDomain = $request->getShopDomain();

        // Пытаемся найти API endpoint магазина
        $apiUrl = $this->getApiUrl($shopDomain);

        if ($apiUrl === null) {
            return CreateShopOrderResponseDTO::failure(
                "No API endpoint configured for shop: {$shopDomain}"
            );
        }

        try {
            // Формируем данные для заказа
            $orderData = [
                'items' => $request->getItems(),
                'shipping_address' => $request->getShippingAddress(),
                'customer' => [
                    'name' => $request->getCustomerName(),
                    'email' => $request->getCustomerEmail(),
                    'phone' => $request->getCustomerPhone(),
                ],
                'metadata' => array_merge(
                    $request->getMetadata(),
                    [
                        'emigram_order_id' => $request->getEmigramOrder()->getId(),
                        'created_at' => now()->toIso8601String(),
                    ]
                ),
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$apiUrl}/orders", $orderData);

            if ($response->successful()) {
                $data = $response->json();

                return CreateShopOrderResponseDTO::success(
                    shopOrderId: (string) ($data['order_id'] ?? $data['id'] ?? ''),
                    shopOrderNumber: $data['order_number'] ?? null,
                    trackingNumber: $data['tracking_number'] ?? null,
                    status: $data['status'] ?? 'pending',
                    metadata: $data,
                );
            }

            return CreateShopOrderResponseDTO::failure(
                "API returned error: {$response->status()} - {$response->body()}"
            );
        } catch (\Exception $e) {
            Log::error('GenericShopAgent: Failed to create order', [
                'shop_domain' => $shopDomain,
                'error' => $e->getMessage(),
            ]);

            return CreateShopOrderResponseDTO::failure(
                "Failed to create order: {$e->getMessage()}"
            );
        }
    }

    public function supports(string $shopDomain): bool
    {
        // Универсальный агент поддерживает все магазины
        // Но имеет низкий приоритет (используется как fallback)
        return true;
    }

    public function getOrderStatus(string $shopOrderId, string $shopDomain): ?string
    {
        $apiUrl = $this->getApiUrl($shopDomain);

        if ($apiUrl === null) {
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->get("{$apiUrl}/orders/{$shopOrderId}/status");

            if ($response->successful()) {
                $data = $response->json();

                return $data['status'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning('GenericShopAgent: Failed to get order status', [
                'shop_order_id' => $shopOrderId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Получает API URL для магазина из конфигурации
     */
    private function getApiUrl(string $shopDomain): ?string
    {
        // Можно хранить в БД (Shop model) или конфиге
        // Для примера используем конфиг
        $config = config("shops.{$shopDomain}");

        return $config['api_url'] ?? null;
    }
}
