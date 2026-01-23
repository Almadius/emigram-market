<?php

declare(strict_types=1);

namespace App\Domains\Cart\Contracts;

use App\Domains\Cart\DTOs\CartDTO;
use App\Domains\Cart\DTOs\CartItemDTO;

interface CartRepositoryInterface
{
    public function getCart(int $userId): CartDTO;

    public function addItem(int $userId, CartItemDTO $item): void;

    public function removeItem(int $userId, int $productId): void;

    public function updateItem(int $userId, int $productId, int $quantity): void;

    public function clear(int $userId): void;

    public function removeItemsByShop(int $userId, string $shopDomain): void;
}




