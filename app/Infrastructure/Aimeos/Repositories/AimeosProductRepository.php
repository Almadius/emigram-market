<?php

declare(strict_types=1);

namespace App\Infrastructure\Aimeos\Repositories;

use Aimeos\MShop;
use App\Domains\Product\Contracts\ProductRepositoryInterface;
use App\Domains\Product\DTOs\ProductDTO;
use App\Domains\Product\DTOs\ProductListDTO;

final class AimeosProductRepository implements ProductRepositoryInterface
{
    private ?MShop\Product\Manager\Iface $productManager = null;

    public function __construct()
    {
        try {
            $context = app('aimeos.context')->get();
            $this->productManager = MShop::create($context, 'product');
        } catch (\Exception $e) {
            // Если Aimeos не настроен, используем заглушку
            $this->productManager = null;
        }
    }

    public function findById(int $productId): ?ProductDTO
    {
        if ($this->productManager === null) {
            return null;
        }

        try {
            $item = $this->productManager->get((string) $productId, ['text', 'media', 'price']);

            return $this->mapToDTO($item);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function search(array $filters = [], int $page = 1, int $perPage = 20): ProductListDTO
    {
        if ($this->productManager === null) {
            return new ProductListDTO([], 0, $page, $perPage);
        }

        $search = $this->productManager->filter();

        // Применяем фильтры
        if (isset($filters['search'])) {
            $search->add('product.label', '~=', $filters['search']);
        }

        if (isset($filters['shop_id'])) {
            $search->add('product.siteid', '==', $filters['shop_id']);
        }

        // Пагинация
        $search->slice(($page - 1) * $perPage, $perPage);

        $items = $this->productManager->search($search, ['text', 'media', 'price']);
        $total = $items->count();

        $products = [];
        foreach ($items as $item) {
            $products[] = $this->mapToDTO($item);
        }

        return new ProductListDTO(
            products: $products,
            total: $total,
            page: $page,
            perPage: $perPage
        );
    }

    public function searchByQuery(string $query, int $limit = 20): array
    {
        if ($this->productManager === null) {
            return [];
        }

        $search = $this->productManager->filter();
        $search->add('product.label', '~=', $query);
        $search->slice(0, $limit);

        $items = $this->productManager->search($search, ['text', 'media', 'price']);

        $products = [];
        foreach ($items as $item) {
            $products[] = $this->mapToDTO($item);
        }

        return $products;
    }

    public function findByUrl(string $url): ?ProductDTO
    {
        if ($this->productManager === null) {
            return null;
        }

        $search = $this->productManager->filter();
        $search->add('product.url', '==', $url);
        $search->slice(0, 1);

        $items = $this->productManager->search($search, ['text', 'media', 'price']);

        if ($items->isEmpty()) {
            return null;
        }

        return $this->mapToDTO($items->first());
    }

    private function mapToDTO(MShop\Product\Item\Iface $item): ProductDTO
    {
        // Получаем текстовые данные
        $textItems = $item->getRefItems('text', 'name', 'default');
        $name = ! $textItems->isEmpty() ? $textItems->first()->getContent() : '';

        $textItems = $item->getRefItems('text', 'short', 'default');
        $description = ! $textItems->isEmpty() ? $textItems->first()->getContent() : null;

        // Получаем изображение
        $mediaItems = $item->getRefItems('media', 'default', 'default');
        $imageUrl = ! $mediaItems->isEmpty() ? $mediaItems->first()->getUrl() : null;

        // Получаем цену
        $priceItems = $item->getRefItems('price', 'default', 'default');
        $price = ! $priceItems->isEmpty() ? (float) $priceItems->first()->getValue() : null;
        $currency = ! $priceItems->isEmpty() ? $priceItems->first()->getCurrencyId() : 'EUR';

        // Получаем shop_id и shop_domain из контекста
        $shopId = null;
        $shopDomain = null;

        try {
            // Пытаемся получить из контекста Aimeos
            $context = app('aimeos.context')->get();
            $siteCode = $context->locale()->getSiteItem()->getCode();

            // Ищем магазин по domain или создаём связь
            $shop = \App\Models\Shop::where('domain', 'like', '%'.$siteCode.'%')->first();

            if ($shop) {
                $shopId = $shop->id;
                $shopDomain = $shop->domain;
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки, если Aimeos не настроен
        }

        return new ProductDTO(
            id: (int) $item->getId(),
            name: $name,
            url: $item->getUrl() ?: '',
            description: $description,
            imageUrl: $imageUrl,
            price: $price,
            currency: $currency,
            shopId: $shopId,
            shopDomain: $shopDomain,
        );
    }
}
