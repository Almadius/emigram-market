<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Eloquent;

use App\Domains\Cart\Contracts\CartRepositoryInterface;
use App\Domains\Cart\DTOs\CartDTO;
use App\Domains\Cart\DTOs\CartItemDTO;
use App\Models\Cart;
use App\Models\CartItem;

final class CartRepository implements CartRepositoryInterface
{
    public function getCart(int $userId): CartDTO
    {
        $cart = Cart::firstOrCreate(['user_id' => $userId]);
        
        $items = $cart->items()->get()->map(function (CartItem $item) {
            return new CartItemDTO(
                productId: $item->product_id,
                productName: $item->product_name,
                quantity: $item->quantity,
                price: (float) $item->price,
                currency: $item->currency,
                shopId: $item->shop_id,
                shopDomain: $item->shop_domain
            );
        })->toArray();

        return new CartDTO(
            items: $items,
            userId: $userId
        );
    }

    public function addItem(int $userId, CartItemDTO $item): void
    {
        $cart = Cart::firstOrCreate(['user_id' => $userId]);
        
        $existingItem = $cart->items()
            ->where('product_id', $item->getProductId())
            ->first();

        if ($existingItem !== null) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $item->getQuantity(),
            ]);
        } else {
            $cart->items()->create([
                'product_id' => $item->getProductId(),
                'product_name' => $item->getProductName(),
                'quantity' => $item->getQuantity(),
                'price' => $item->getPrice(),
                'currency' => $item->getCurrency(),
                'shop_id' => $item->getShopId(),
                'shop_domain' => $item->getShopDomain(),
            ]);
        }
    }

    public function removeItem(int $userId, int $productId): void
    {
        $cart = Cart::where('user_id', $userId)->first();
        
        if ($cart !== null) {
            $cart->items()->where('product_id', $productId)->delete();
        }
    }

    public function updateItem(int $userId, int $productId, int $quantity): void
    {
        $cart = Cart::where('user_id', $userId)->first();
        
        if ($cart !== null) {
            $cart->items()
                ->where('product_id', $productId)
                ->update(['quantity' => $quantity]);
        }
    }

    public function clear(int $userId): void
    {
        $cart = Cart::where('user_id', $userId)->first();
        
        if ($cart !== null) {
            $cart->items()->delete();
        }
    }

    public function removeItemsByShop(int $userId, string $shopDomain): void
    {
        $cart = Cart::where('user_id', $userId)->first();
        
        if ($cart !== null) {
            $cart->items()->where('shop_domain', $shopDomain)->delete();
        }
    }
}




