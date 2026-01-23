<?php

declare(strict_types=1);

namespace App\Domains\Pricing\DTOs;

use App\Domains\Pricing\ValueObjects\Price;

final readonly class PriceResolveResponseDTO
{
    public function __construct(
        private Price $price,
        private array $rules = [],
    ) {
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function toArray(): array
    {
        return [
            'price' => [
                'store_price' => $this->price->getStorePrice(),
                'emigram_price' => $this->price->getEmigramPrice(),
                'currency' => $this->price->getCurrency(),
                'savings_absolute' => $this->price->getSavingsAbsolute(),
                'savings_percent' => $this->price->getSavingsPercent(),
            ],
            'rules' => $this->rules,
        ];
    }
}


