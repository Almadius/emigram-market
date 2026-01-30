<?php

declare(strict_types=1);

namespace App\Domains\Cart\DTOs;

final readonly class CartItemDTO
{
    public function __construct(
        private int $productId,
        private string $productName,
        private int $quantity,
        private float $price,
        private string $currency,
        private ?int $shopId = null,
        private ?string $shopDomain = null,
    ) {
        if ($productId <= 0) {
            throw new \InvalidArgumentException('Product ID must be positive');
        }
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }
        if ($price < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getShopId(): ?int
    {
        return $this->shopId;
    }

    public function getShopDomain(): ?string
    {
        return $this->shopDomain;
    }

    public function getTotal(): float
    {
        return $this->price * $this->quantity;
    }
}
