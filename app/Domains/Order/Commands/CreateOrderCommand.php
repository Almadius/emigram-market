<?php

declare(strict_types=1);

namespace App\Domains\Order\Commands;

use App\Domains\Cart\DTOs\CartItemDTO;

final readonly class CreateOrderCommand
{
    /**
     * @param array<CartItemDTO> $items
     */
    public function __construct(
        private int $userId,
        private int $shopId,
        private string $shopDomain,
        private array $items,
        private string $currency,
    ) {
        if ($userId <= 0) {
            throw new \InvalidArgumentException('User ID must be positive');
        }
        if (empty($shopDomain)) {
            throw new \InvalidArgumentException('Shop domain cannot be empty');
        }
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getShopId(): int
    {
        return $this->shopId;
    }

    public function getShopDomain(): string
    {
        return $this->shopDomain;
    }

    /**
     * @return array<CartItemDTO>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}




