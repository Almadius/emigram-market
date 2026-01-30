<?php

declare(strict_types=1);

namespace App\Domains\Cart\Services;

use App\Domains\Cart\Contracts\CartRepositoryInterface;
use App\Domains\Cart\DTOs\CartDTO;
use App\Domains\Cart\DTOs\CartItemDTO;

final class CartService
{
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
    ) {}

    public function getCart(int $userId): CartDTO
    {
        return $this->cartRepository->getCart($userId);
    }

    public function addItem(int $userId, CartItemDTO $item): void
    {
        $this->cartRepository->addItem($userId, $item);
    }

    public function removeItem(int $userId, int $productId): void
    {
        $this->cartRepository->removeItem($userId, $productId);
    }

    public function updateItem(int $userId, int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($userId, $productId);

            return;
        }

        $this->cartRepository->updateItem($userId, $productId, $quantity);
    }

    public function clear(int $userId): void
    {
        $this->cartRepository->clear($userId);
    }

    public function removeItemsByShop(int $userId, string $shopDomain): void
    {
        $this->cartRepository->removeItemsByShop($userId, $shopDomain);
    }
}
