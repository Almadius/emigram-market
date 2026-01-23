<?php

declare(strict_types=1);

namespace App\Domains\Pricing\DTOs;

final readonly class PriceResolveRequestDTO
{
    public function __construct(
        private int $userId,
        private string $shopDomain,
        private string $productUrl,
        private float $storePrice,
        private string $currency,
        private array $context = [],
    ) {
        if ($userId <= 0) {
            throw new \InvalidArgumentException('User ID must be positive');
        }
        if ($storePrice < 0) {
            throw new \InvalidArgumentException('Store price cannot be negative');
        }
        if (empty($shopDomain)) {
            throw new \InvalidArgumentException('Shop domain cannot be empty');
        }
        if (empty($productUrl)) {
            throw new \InvalidArgumentException('Product URL cannot be empty');
        }
        if (empty($currency)) {
            throw new \InvalidArgumentException('Currency cannot be empty');
        }
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getShopDomain(): string
    {
        return $this->shopDomain;
    }

    public function getProductUrl(): string
    {
        return $this->productUrl;
    }

    public function getStorePrice(): float
    {
        return $this->storePrice;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}





