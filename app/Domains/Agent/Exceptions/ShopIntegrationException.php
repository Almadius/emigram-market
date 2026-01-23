<?php

declare(strict_types=1);

namespace App\Domains\Agent\Exceptions;

use Exception;

/**
 * Исключение для ошибок интеграции с магазинами
 */
final class ShopIntegrationException extends Exception
{
    public static function shopUnavailable(string $shopDomain): self
    {
        return new self("Shop {$shopDomain} is not available");
    }

    public static function orderCreationFailed(string $shopDomain, string $reason): self
    {
        return new self("Failed to create order in shop {$shopDomain}: {$reason}");
    }

    public static function invalidResponse(string $shopDomain): self
    {
        return new self("Invalid response from shop {$shopDomain}");
    }
}

