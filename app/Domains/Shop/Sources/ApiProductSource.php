<?php

declare(strict_types=1);

namespace App\Domains\Shop\Sources;

use App\Domains\Product\DTOs\ProductDTO;
use App\Domains\Shop\Contracts\ShopProductSourceInterface;
use App\Domains\Shop\Exceptions\ShopSyncException;
use App\Models\Shop;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Источник товаров через REST API магазина
 */
final class ApiProductSource implements ShopProductSourceInterface
{
    public function fetchProducts(string $shopDomain, int $page = 1, int $perPage = 100): array
    {
        $apiUrl = $this->getApiUrl($shopDomain);
        $apiKey = $this->getApiKey($shopDomain);

        if ($apiUrl === null || $apiKey === null) {
            throw ShopSyncException::shopUnavailable($shopDomain);
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Accept' => 'application/json',
                ])
                ->get("{$apiUrl}/api/products", [
                    'page' => $page,
                    'per_page' => $perPage,
                ]);

            if (!$response->successful()) {
                throw ShopSyncException::fetchFailed(
                    $shopDomain,
                    "HTTP {$response->status()}: {$response->body()}"
                );
            }

            $data = $response->json();
            $productsData = $data['data'] ?? $data['products'] ?? $data ?? [];

            if (!is_array($productsData)) {
                throw ShopSyncException::invalidResponse($shopDomain);
            }

            $shop = Shop::where('domain', $shopDomain)->first();
            $shopId = $shop?->id;

            return array_map(
                fn(array $productData) => $this->mapToProductDTO($productData, $shopDomain, $shopId),
                $productsData
            );
        } catch (ShopSyncException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('ApiProductSource: Failed to fetch products', [
                'shop_domain' => $shopDomain,
                'error' => $e->getMessage(),
            ]);

            throw ShopSyncException::fetchFailed($shopDomain, $e->getMessage());
        }
    }

    public function fetchProductByUrl(string $shopDomain, string $productUrl): ?ProductDTO
    {
        $apiUrl = $this->getApiUrl($shopDomain);
        $apiKey = $this->getApiKey($shopDomain);

        if ($apiUrl === null || $apiKey === null) {
            throw ShopSyncException::shopUnavailable($shopDomain);
        }

        try {
            // Кодируем URL для передачи в API
            $encodedUrl = urlencode($productUrl);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Accept' => 'application/json',
                ])
                ->get("{$apiUrl}/api/products/by-url", [
                    'url' => $productUrl,
                ]);

            if ($response->status() === 404) {
                return null;
            }

            if (!$response->successful()) {
                throw ShopSyncException::fetchFailed(
                    $shopDomain,
                    "HTTP {$response->status()}: {$response->body()}"
                );
            }

            $productData = $response->json();
            $productData = $productData['data'] ?? $productData;

            if (!is_array($productData)) {
                throw ShopSyncException::invalidResponse($shopDomain);
            }

            $shop = Shop::where('domain', $shopDomain)->first();
            $shopId = $shop?->id;

            return $this->mapToProductDTO($productData, $shopDomain, $shopId);
        } catch (ShopSyncException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('ApiProductSource: Failed to fetch product by URL', [
                'shop_domain' => $shopDomain,
                'product_url' => $productUrl,
                'error' => $e->getMessage(),
            ]);

            throw ShopSyncException::fetchFailed($shopDomain, $e->getMessage());
        }
    }

    public function isAvailable(string $shopDomain): bool
    {
        $apiUrl = $this->getApiUrl($shopDomain);
        $apiKey = $this->getApiKey($shopDomain);

        return $apiUrl !== null && $apiKey !== null;
    }

    /**
     * Маппит данные из API в ProductDTO
     *
     * @param array<string, mixed> $productData Данные товара из API
     * @param string $shopDomain Домен магазина
     * @param int|null $shopId ID магазина
     * @return ProductDTO
     */
    private function mapToProductDTO(array $productData, string $shopDomain, ?int $shopId): ProductDTO
    {
        // Используем временный ID (0), так как товар еще не создан в БД
        // В реальной реализации ID будет установлен после создания через репозиторий
        return new ProductDTO(
            id: 0, // Будет установлен после создания
            name: $productData['name'] ?? $productData['title'] ?? '',
            url: $productData['url'] ?? $productData['link'] ?? '',
            description: $productData['description'] ?? null,
            imageUrl: $productData['image_url'] ?? $productData['image'] ?? $productData['imageUrl'] ?? null,
            price: isset($productData['price']) ? (float) $productData['price'] : null,
            currency: $productData['currency'] ?? 'EUR',
            shopId: $shopId,
            shopDomain: $shopDomain,
        );
    }

    /**
     * Получает API URL для магазина
     */
    private function getApiUrl(string $shopDomain): ?string
    {
        return config("shops.{$shopDomain}.api_url")
            ?? env("SHOP_{$shopDomain}_API_URL");
    }

    /**
     * Получает API ключ для магазина
     */
    private function getApiKey(string $shopDomain): ?string
    {
        return config("shops.{$shopDomain}.api_key")
            ?? env("SHOP_{$shopDomain}_API_KEY");
    }
}









