<?php

declare(strict_types=1);

namespace App\Domains\Product\Services;

use App\Domains\Parsing\Services\PriceAggregationService;
use App\Domains\Pricing\DTOs\PriceResolveRequestDTO;
use App\Domains\Pricing\Services\PriceService;
use App\Domains\Product\Contracts\ProductRepositoryInterface;
use App\Domains\Pricing\ValueObjects\Price;
use App\Domains\Product\DTOs\ProductDTO;
use App\Domains\Product\DTOs\ProductListDTO;
use App\Domains\Product\DTOs\ProductWithPriceDTO;
use App\Domains\Pricing\Contracts\DiscountRepositoryInterface;
use Illuminate\Support\Facades\Cache;

final class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly PriceService $priceService,
        private readonly PriceAggregationService $priceAggregationService,
        private readonly DiscountRepositoryInterface $discountRepository,
    ) {
    }

    public function findById(int $productId, ?int $userId = null): ?ProductWithPriceDTO
    {
        // Кэшируем продукт на 5 минут
        $cacheKey = "product:{$productId}";
        $product = Cache::remember($cacheKey, 300, function () use ($productId) {
            return $this->productRepository->findById($productId);
        });
        
        if ($product === null) {
            return null;
        }

        // Цены не кэшируем, так как они зависят от userId и могут меняться
        return $this->enrichWithPrice($product, $userId);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{data: array, total: int, page: int, per_page: int}
     */
    public function search(array $filters = [], int $page = 1, int $perPage = 20, ?int $userId = null): array
    {
        // Кэшируем результаты поиска на 2 минуты (без учета userId для списка продуктов)
        $cacheKey = 'products:search:' . md5(json_encode($filters) . ":{$page}:{$perPage}");
        
        $list = Cache::remember($cacheKey, 120, function () use ($filters, $page, $perPage) {
            return $this->productRepository->search($filters, $page, $perPage);
        });

        // Обогащаем продукты ценами (не кэшируем, так как зависят от userId)
        $productsWithPrice = array_map(
            fn(ProductDTO $product) => $this->enrichWithPrice($product, $userId),
            $list->getProducts()
        );

        // Получаем discount rules для пользователя если есть (кэшируем на 10 минут)
        $discountRules = [];
        if ($userId !== null) {
            $discountCacheKey = "discount_rules:user:{$userId}";
            $discountRules = Cache::remember($discountCacheKey, 600, function () use ($userId) {
                return $this->discountRepository->getRulesForUser($userId);
            });
        }

        return [
            'data' => array_map(
                fn($p) => $p->toArrayWithRules($discountRules),
                $productsWithPrice
            ),
            'total' => $list->getTotal(),
            'page' => $list->getPage(),
            'per_page' => $list->getPerPage(),
        ];
    }

    private function enrichWithPrice(ProductDTO $product, ?int $userId): ProductWithPriceDTO
    {
        $storePrice = null;
        $emigramPrice = null;

        if ($product->getPrice() !== null && $product->getShopDomain() !== null) {
            // Получаем лучшую цену из парсинга
            $bestPrice = $this->priceAggregationService->aggregateBestPrice(
                $product->getShopDomain(),
                $product->getUrl()
            );

            $storePriceValue = $bestPrice?->getPrice() ?? $product->getPrice();

            // Рассчитываем персональную цену если есть userId
            if ($userId !== null) {
                try {
                    $priceRequest = new PriceResolveRequestDTO(
                        userId: $userId,
                        shopDomain: $product->getShopDomain(),
                        productUrl: $product->getUrl(),
                        storePrice: $storePriceValue,
                        currency: $product->getCurrency()
                    );

                    $priceResponse = $this->priceService->resolvePrice($priceRequest);
                    $emigramPrice = $priceResponse->getPrice();
                    $storePrice = new Price(
                        storePrice: $storePriceValue,
                        emigramPrice: $storePriceValue,
                        currency: $product->getCurrency()
                    );
                } catch (\Exception $e) {
                    // Если ошибка расчёта, используем store price
                    $storePrice = new Price(
                        storePrice: $storePriceValue,
                        emigramPrice: $storePriceValue,
                        currency: $product->getCurrency()
                    );
                }
            } else {
                $storePrice = new Price(
                    storePrice: $storePriceValue,
                    emigramPrice: $storePriceValue,
                    currency: $product->getCurrency()
                );
            }
        }

        return new ProductWithPriceDTO(
            product: $product,
            emigramPrice: $emigramPrice,
            storePrice: $storePrice
        );
    }
}
