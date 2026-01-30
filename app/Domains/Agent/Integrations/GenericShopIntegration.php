<?php

declare(strict_types=1);

namespace App\Domains\Agent\Integrations;

use App\Domains\Agent\Contracts\ShopIntegrationInterface;
use App\Domains\Agent\DTOs\ShopOrderRequestDTO;
use App\Domains\Agent\DTOs\ShopOrderResponseDTO;
use App\Domains\Agent\Exceptions\ShopIntegrationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Универсальная интеграция с магазинами через API
 * Использует стандартный REST API для создания заказов
 */
final class GenericShopIntegration implements ShopIntegrationInterface
{
    public function __construct(
        private readonly string $shopDomain,
    ) {}

    public function createOrder(ShopOrderRequestDTO $request): ShopOrderResponseDTO
    {
        $apiUrl = $this->getApiUrl();
        $apiKey = $this->getApiKey();

        if ($apiUrl === null || $apiKey === null) {
            throw ShopIntegrationException::shopUnavailable($this->shopDomain);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post("{$apiUrl}/api/orders", [
                'items' => $request->getItems(),
                'shipping_address' => $request->getShippingAddress(),
                'billing_address' => $request->getBillingAddress(),
                'customer_email' => $request->getCustomerEmail(),
                'customer_phone' => $request->getCustomerPhone(),
                'metadata' => $request->getMetadata(),
            ]);

            if (! $response->successful()) {
                throw ShopIntegrationException::orderCreationFailed(
                    $this->shopDomain,
                    $response->body() ?: "HTTP {$response->status()}"
                );
            }

            $data = $response->json();

            if (! isset($data['order_id']) || ! isset($data['status'])) {
                throw ShopIntegrationException::invalidResponse($this->shopDomain);
            }

            return new ShopOrderResponseDTO(
                shopOrderId: (string) $data['order_id'],
                status: $data['status'],
                trackingNumber: $data['tracking_number'] ?? null,
                trackingUrl: $data['tracking_url'] ?? null,
                metadata: $data['metadata'] ?? [],
            );
        } catch (ShopIntegrationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Generic shop integration error', [
                'shop_domain' => $this->shopDomain,
                'error' => $e->getMessage(),
            ]);

            throw ShopIntegrationException::orderCreationFailed($this->shopDomain, $e->getMessage());
        }
    }

    public function getOrderStatus(string $shopOrderId): string
    {
        $apiUrl = $this->getApiUrl();
        $apiKey = $this->getApiKey();

        if ($apiUrl === null || $apiKey === null) {
            throw ShopIntegrationException::shopUnavailable($this->shopDomain);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Accept' => 'application/json',
            ])->get("{$apiUrl}/api/orders/{$shopOrderId}");

            if (! $response->successful()) {
                throw ShopIntegrationException::orderCreationFailed(
                    $this->shopDomain,
                    "Failed to get order status: HTTP {$response->status()}"
                );
            }

            $data = $response->json();

            return $data['status'] ?? 'unknown';
        } catch (\Exception $e) {
            Log::error('Failed to get order status', [
                'shop_domain' => $this->shopDomain,
                'shop_order_id' => $shopOrderId,
                'error' => $e->getMessage(),
            ]);

            throw ShopIntegrationException::orderCreationFailed($this->shopDomain, $e->getMessage());
        }
    }

    public function cancelOrder(string $shopOrderId): bool
    {
        $apiUrl = $this->getApiUrl();
        $apiKey = $this->getApiKey();

        if ($apiUrl === null || $apiKey === null) {
            throw ShopIntegrationException::shopUnavailable($this->shopDomain);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Accept' => 'application/json',
            ])->delete("{$apiUrl}/api/orders/{$shopOrderId}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to cancel order', [
                'shop_domain' => $this->shopDomain,
                'shop_order_id' => $shopOrderId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function isAvailable(): bool
    {
        $apiUrl = $this->getApiUrl();
        $apiKey = $this->getApiKey();

        return $apiUrl !== null && $apiKey !== null;
    }

    /**
     * Получает API URL для магазина
     */
    private function getApiUrl(): ?string
    {
        // Получаем из конфигурации или из модели Shop
        // Пример: config("shops.{$this->shopDomain}.api_url")
        // Или из базы данных через Shop модель

        return config("shops.{$this->shopDomain}.api_url")
            ?? env("SHOP_{$this->shopDomain}_API_URL");
    }

    /**
     * Получает API ключ для магазина
     */
    private function getApiKey(): ?string
    {
        // Получаем из конфигурации или из модели Shop
        // Пример: config("shops.{$this->shopDomain}.api_key")
        // Или из базы данных через Shop модель

        return config("shops.{$this->shopDomain}.api_key")
            ?? env("SHOP_{$this->shopDomain}_API_KEY");
    }
}
