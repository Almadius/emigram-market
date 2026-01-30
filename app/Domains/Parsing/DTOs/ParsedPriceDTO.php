<?php

declare(strict_types=1);

namespace App\Domains\Parsing\DTOs;

use App\Domains\Parsing\Enums\PriceSourceEnum;

final readonly class ParsedPriceDTO
{
    public function __construct(
        private string $shopDomain,
        private string $productUrl,
        private float $price,
        private string $currency,
        private PriceSourceEnum $source,
        private \DateTimeImmutable $parsedAt,
    ) {
        if ($price < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }
    }

    public function getShopDomain(): string
    {
        return $this->shopDomain;
    }

    public function getProductUrl(): string
    {
        return $this->productUrl;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getSource(): PriceSourceEnum
    {
        return $this->source;
    }

    public function getParsedAt(): \DateTimeImmutable
    {
        return $this->parsedAt;
    }
}
