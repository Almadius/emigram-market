<?php

declare(strict_types=1);

namespace App\Domains\Shop\Services;

use App\Domains\Shop\Contracts\ShopProductSourceInterface;
use App\Domains\Shop\Sources\ApiProductSource;
use Illuminate\Support\Facades\Log;

/**
 * Фабрика для создания источников товаров из магазинов
 */
final class ShopProductSourceFactory
{
    /**
     * @var array<string, ShopProductSourceInterface>
     */
    private array $sources = [];

    public function __construct()
    {
        // Регистрируем доступные источники
    }

    /**
     * Получает источник товаров для магазина
     *
     * @param string $shopDomain Домен магазина
     * @return ShopProductSourceInterface Источник товаров
     */
    public function getSource(string $shopDomain): ShopProductSourceInterface
    {
        // Кэшируем источники
        if (!isset($this->sources[$shopDomain])) {
            $this->sources[$shopDomain] = $this->createSource($shopDomain);
        }

        return $this->sources[$shopDomain];
    }

    /**
     * Создает источник товаров для магазина
     *
     * @param string $shopDomain Домен магазина
     * @return ShopProductSourceInterface
     */
    private function createSource(string $shopDomain): ShopProductSourceInterface
    {
        // Проверяем, есть ли специфичный источник для магазина
        $specificSource = $this->getSpecificSource($shopDomain);
        
        if ($specificSource !== null) {
            return $specificSource;
        }

        // Используем универсальный источник через API
        return new ApiProductSource();
    }

    /**
     * Получает специфичный источник для магазина (если есть)
     *
     * @param string $shopDomain Домен магазина
     * @return ShopProductSourceInterface|null
     */
    private function getSpecificSource(string $shopDomain): ?ShopProductSourceInterface
    {
        // Здесь можно добавить логику для специфичных источников
        // Например: ShopifyProductSource, WooCommerceProductSource и т.д.
        
        // Пример:
        // if (str_contains($shopDomain, 'myshopify.com')) {
        //     return new ShopifyProductSource();
        // }
        
        return null;
    }
}









