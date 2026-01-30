<?php

declare(strict_types=1);

namespace App\Domains\Product\DTOs;

use App\Domains\Pricing\ValueObjects\Price;

final readonly class ProductWithPriceDTO
{
    public function __construct(
        private ProductDTO $product,
        private ?Price $emigramPrice = null,
        private ?Price $storePrice = null,
    ) {}

    public function getProduct(): ProductDTO
    {
        return $this->product;
    }

    public function getEmigramPrice(): ?Price
    {
        return $this->emigramPrice;
    }

    public function getStorePrice(): ?Price
    {
        return $this->storePrice;
    }

    public function toArray(): array
    {
        $result = [
            'product' => $this->product->toArray(),
        ];

        if ($this->emigramPrice !== null) {
            $result['emigram_price'] = [
                'price' => $this->emigramPrice->getEmigramPrice(),
                'currency' => $this->emigramPrice->getCurrency(),
                'savings_absolute' => $this->emigramPrice->getSavingsAbsolute(),
                'savings_percent' => $this->emigramPrice->getSavingsPercent(),
            ];
        }

        if ($this->storePrice !== null) {
            $result['store_price'] = [
                'price' => $this->storePrice->getStorePrice(),
                'currency' => $this->storePrice->getCurrency(),
            ];
        }

        return $result;
    }

    /**
     * Добавляет discount rules к результату для отображения breakdown скидки
     */
    public function toArrayWithRules(array $rules = []): array
    {
        $result = $this->toArray();

        // Добавляем discount breakdown если есть emigram_price и rules
        if ($this->emigramPrice !== null && ! empty($rules)) {
            $result['discount_breakdown'] = [
                'base_discount' => $rules['base_discount'] ?? 0.0,
                'personal_discount' => $rules['personal_discount'] ?? 0.0,
                'user_level' => $rules['user_level'] ?? 1,
                'total_discount_percent' => ($rules['base_discount'] ?? 0.0) + ($rules['personal_discount'] ?? 0.0),
            ];
        }

        return $result;
    }
}
