<?php

declare(strict_types=1);

namespace App\Domains\Cart\Services;

use App\Domains\Cart\DTOs\CartDTO;
use App\Domains\Cart\DTOs\CartItemDTO;

final class CartSplitService
{
    /**
     * Разделяет корзину по магазинам для checkout
     *
     * @return array<string, array<CartItemDTO>>
     */
    public function splitByShop(CartDTO $cart): array
    {
        return $cart->splitByShop();
    }

    /**
     * Получает товары для конкретного магазина
     *
     * @return array<CartItemDTO>
     */
    public function getItemsForShop(CartDTO $cart, string $shopDomain): array
    {
        $split = $this->splitByShop($cart);

        return $split[$shopDomain] ?? [];
    }
}
