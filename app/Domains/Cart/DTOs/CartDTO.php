<?php

declare(strict_types=1);

namespace App\Domains\Cart\DTOs;

final readonly class CartDTO
{
    /**
     * @param  array<CartItemDTO>  $items
     */
    public function __construct(
        private array $items,
        private int $userId,
    ) {}

    /**
     * @return array<CartItemDTO>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getTotal(): float
    {
        return array_sum(array_map(fn (CartItemDTO $item) => $item->getTotal(), $this->items));
    }

    /**
     * Разделяет корзину по магазинам
     *
     * @return array<string, array<CartItemDTO>>
     */
    public function splitByShop(): array
    {
        $split = [];

        foreach ($this->items as $item) {
            $shopKey = $item->getShopDomain() ?? 'unknown';
            if (! isset($split[$shopKey])) {
                $split[$shopKey] = [];
            }
            $split[$shopKey][] = $item;
        }

        return $split;
    }
}
