<?php

declare(strict_types=1);

namespace App\Domains\Shop\Exceptions;

use Exception;

/**
 * Исключение для ошибок синхронизации товаров из магазинов
 */
final class ShopSyncException extends Exception
{
    public static function shopUnavailable(string $shopDomain): self
    {
        return new self("Shop {$shopDomain} is not available for synchronization");
    }

    public static function fetchFailed(string $shopDomain, string $reason): self
    {
        return new self("Failed to fetch products from shop {$shopDomain}: {$reason}");
    }

    public static function invalidResponse(string $shopDomain): self
    {
        return new self("Invalid response from shop {$shopDomain}");
    }

    public static function productNotFound(string $shopDomain, string $productUrl): self
    {
        return new self("Product not found in shop {$shopDomain}: {$productUrl}");
    }
}
