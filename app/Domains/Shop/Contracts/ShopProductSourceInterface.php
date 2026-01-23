<?php

declare(strict_types=1);

namespace App\Domains\Shop\Contracts;

use App\Domains\Product\DTOs\ProductDTO;

/**
 * Интерфейс для получения товаров из магазина
 * Каждый магазин может реализовать свой способ получения товаров (API, парсинг, и т.д.)
 */
interface ShopProductSourceInterface
{
    /**
     * Получает список товаров из магазина
     *
     * @param string $shopDomain Домен магазина
     * @param int $page Номер страницы
     * @param int $perPage Количество товаров на странице
     * @return array<ProductDTO> Список товаров
     * @throws \App\Domains\Shop\Exceptions\ShopSyncException
     */
    public function fetchProducts(string $shopDomain, int $page = 1, int $perPage = 100): array;

    /**
     * Получает конкретный товар по URL
     *
     * @param string $shopDomain Домен магазина
     * @param string $productUrl URL товара
     * @return ProductDTO|null Товар или null, если не найден
     * @throws \App\Domains\Shop\Exceptions\ShopSyncException
     */
    public function fetchProductByUrl(string $shopDomain, string $productUrl): ?ProductDTO;

    /**
     * Проверяет доступность источника товаров
     *
     * @param string $shopDomain Домен магазина
     * @return bool Доступен ли источник
     */
    public function isAvailable(string $shopDomain): bool;
}









