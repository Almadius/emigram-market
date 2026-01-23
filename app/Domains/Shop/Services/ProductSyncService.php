<?php

declare(strict_types=1);

namespace App\Domains\Shop\Services;

use App\Domains\Product\Contracts\ProductRepositoryInterface;
use App\Domains\Product\DTOs\ProductDTO;
use App\Domains\Shop\Contracts\ShopProductSourceInterface;
use App\Domains\Shop\Exceptions\ShopSyncException;
use App\Models\Shop;
use App\Services\MetricsService;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для синхронизации товаров из магазинов
 */
final class ProductSyncService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ShopProductSourceFactory $sourceFactory,
        private readonly MetricsService $metrics,
    ) {
    }

    /**
     * Синхронизирует товары из магазина
     *
     * @param int $shopId ID магазина
     * @param int $maxPages Максимальное количество страниц для синхронизации
     * @return int Количество синхронизированных товаров
     * @throws ShopSyncException
     */
    public function syncShopProducts(int $shopId, int $maxPages = 10): int
    {
        $shop = Shop::find($shopId);
        
        if ($shop === null) {
            throw ShopSyncException::shopUnavailable("id:{$shopId}");
        }

        if (!$shop->is_active) {
            throw ShopSyncException::shopUnavailable($shop->domain);
        }

        $source = $this->sourceFactory->getSource($shop->domain);
        
        if (!$source->isAvailable($shop->domain)) {
            throw ShopSyncException::shopUnavailable($shop->domain);
        }

        $syncedCount = 0;
        $page = 1;

        try {
            while ($page <= $maxPages) {
                $products = $source->fetchProducts($shop->domain, $page, 100);
                
                if (empty($products)) {
                    break; // Больше нет товаров
                }

                foreach ($products as $product) {
                    $this->syncProduct($product, $shop);
                    $syncedCount++;
                }

                // Если получили меньше товаров, чем запрашивали, значит это последняя страница
                if (count($products) < 100) {
                    break;
                }

                $page++;
            }

            Log::info('Shop products synchronized', [
                'shop_id' => $shopId,
                'shop_domain' => $shop->domain,
                'synced_count' => $syncedCount,
            ]);

            // Записываем метрику
            $this->metrics->increment('products.synced', $syncedCount, [
                'shop_domain' => $shop->domain,
            ]);

            return $syncedCount;
        } catch (ShopSyncException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to sync shop products', [
                'shop_id' => $shopId,
                'shop_domain' => $shop->domain,
                'error' => $e->getMessage(),
            ]);

            throw ShopSyncException::fetchFailed($shop->domain, $e->getMessage());
        }
    }

    /**
     * Синхронизирует конкретный товар по URL
     *
     * @param string $shopDomain Домен магазина
     * @param string $productUrl URL товара
     * @return ProductDTO Синхронизированный товар
     * @throws ShopSyncException
     */
    public function syncProductByUrl(string $shopDomain, string $productUrl): ProductDTO
    {
        $shop = Shop::where('domain', $shopDomain)->first();
        
        if ($shop === null) {
            throw ShopSyncException::shopUnavailable($shopDomain);
        }

        $source = $this->sourceFactory->getSource($shopDomain);
        $product = $source->fetchProductByUrl($shopDomain, $productUrl);

        if ($product === null) {
            throw ShopSyncException::productNotFound($shopDomain, $productUrl);
        }

        return $this->syncProduct($product, $shop);
    }

    /**
     * Синхронизирует один товар
     *
     * @param ProductDTO $product Товар для синхронизации
     * @param Shop $shop Магазин
     * @return ProductDTO Синхронизированный товар
     */
    private function syncProduct(ProductDTO $product, Shop $shop): ProductDTO
    {
        // Проверяем, существует ли товар с таким URL
        $existingProduct = $this->productRepository->findByUrl($product->getUrl());

        if ($existingProduct !== null) {
            // Товар уже существует, обновляем его
            // Создаем обновленный DTO с существующим ID
            $updatedProduct = new ProductDTO(
                id: $existingProduct->getId(),
                name: $product->getName(),
                url: $product->getUrl(),
                description: $product->getDescription(),
                imageUrl: $product->getImageUrl(),
                price: $product->getPrice(),
                currency: $product->getCurrency(),
                shopId: $product->getShopId() ?? $shop->id,
                shopDomain: $product->getShopDomain() ?? $shop->domain,
            );

            Log::debug('Product updated', [
                'product_id' => $existingProduct->getId(),
                'url' => $product->getUrl(),
            ]);

            return $this->productRepository->update($updatedProduct);
        }

        // Создаем новый товар
        // Устанавливаем shop_id и shop_domain, если они не установлены
        $productToCreate = new ProductDTO(
            id: 0, // Будет установлен после создания
            name: $product->getName(),
            url: $product->getUrl(),
            description: $product->getDescription(),
            imageUrl: $product->getImageUrl(),
            price: $product->getPrice(),
            currency: $product->getCurrency(),
            shopId: $product->getShopId() ?? $shop->id,
            shopDomain: $product->getShopDomain() ?? $shop->domain,
        );

        Log::info('New product created', [
            'name' => $product->getName(),
            'url' => $product->getUrl(),
            'shop_id' => $shop->id,
        ]);

        return $this->productRepository->create($productToCreate);
    }
}

