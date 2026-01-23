<?php

declare(strict_types=1);

namespace App\Domains\Agent\Services;

use App\Domains\Agent\Contracts\ShopIntegrationInterface;
use App\Domains\Agent\Integrations\GenericShopIntegration;
use Illuminate\Support\Facades\Log;

/**
 * Фабрика для создания интеграций с магазинами
 */
final class ShopIntegrationFactory
{
    /**
     * @var array<string, ShopIntegrationInterface>
     */
    private array $integrations = [];

    public function __construct()
    {
        // Регистрируем доступные интеграции
        // В будущем можно добавить специфичные интеграции для каждого магазина
    }

    /**
     * Получает интеграцию для магазина
     *
     * @param string $shopDomain Домен магазина
     * @return ShopIntegrationInterface Интеграция
     */
    public function getIntegration(string $shopDomain): ShopIntegrationInterface
    {
        // Кэшируем интеграции
        if (!isset($this->integrations[$shopDomain])) {
            $this->integrations[$shopDomain] = $this->createIntegration($shopDomain);
        }

        return $this->integrations[$shopDomain];
    }

    /**
     * Создает интеграцию для магазина
     *
     * @param string $shopDomain Домен магазина
     * @return ShopIntegrationInterface
     */
    private function createIntegration(string $shopDomain): ShopIntegrationInterface
    {
        // Проверяем, есть ли специфичная интеграция для магазина
        $specificIntegration = $this->getSpecificIntegration($shopDomain);
        
        if ($specificIntegration !== null) {
            return $specificIntegration;
        }

        // Используем универсальную интеграцию
        return new GenericShopIntegration($shopDomain);
    }

    /**
     * Получает специфичную интеграцию для магазина (если есть)
     *
     * @param string $shopDomain Домен магазина
     * @return ShopIntegrationInterface|null
     */
    private function getSpecificIntegration(string $shopDomain): ?ShopIntegrationInterface
    {
        // Здесь можно добавить логику для специфичных интеграций
        // Например: AmazonIntegration, ShopifyIntegration и т.д.
        
        // Пример:
        // if (str_contains($shopDomain, 'amazon')) {
        //     return new AmazonShopIntegration($shopDomain);
        // }
        
        return null;
    }
}

