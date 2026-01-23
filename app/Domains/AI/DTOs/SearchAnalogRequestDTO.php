<?php

declare(strict_types=1);

namespace App\Domains\AI\DTOs;

final readonly class SearchAnalogRequestDTO
{
    public function __construct(
        private int $productId,
        private ?string $description = null,
        private ?float $maxPrice = null,
    ) {
        if ($this->productId <= 0) {
            throw new \InvalidArgumentException('Product ID must be positive');
        }
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getMaxPrice(): ?float
    {
        return $this->maxPrice;
    }
}




