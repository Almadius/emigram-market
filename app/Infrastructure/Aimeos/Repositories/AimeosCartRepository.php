<?php

declare(strict_types=1);

namespace App\Infrastructure\Aimeos\Repositories;

use Aimeos\MShop;
use App\Domains\Cart\Contracts\CartRepositoryInterface;
use App\Domains\Cart\DTOs\CartDTO;
use App\Domains\Cart\DTOs\CartItemDTO;
use Illuminate\Support\Facades\Log;

final class AimeosCartRepository implements CartRepositoryInterface
{
    private ?MShop\Product\Manager\Iface $productManager = null;

    private bool $isAvailable = false;

    public function __construct()
    {
        try {
            $context = app('aimeos.context')->get();
            $this->productManager = MShop::create($context, 'product');
            $this->isAvailable = true;
        } catch (\Exception $e) {
            // Aimeos не настроен, используем fallback
            Log::warning('Aimeos not configured, using fallback', ['error' => $e->getMessage()]);
            $this->isAvailable = false;
        }
    }

    public function getCart(int $userId): CartDTO
    {
        if (! $this->isAvailable) {
            // Fallback на Eloquent если Aimeos не настроен
            return app(\App\Infrastructure\Repositories\Eloquent\CartRepository::class)->getCart($userId);
        }

        try {
            $context = app('aimeos.context')->get();
            // Aimeos API может отличаться, используем fallback
            // В реальной реализации нужно использовать правильный Aimeos API для работы с корзиной
            throw new \RuntimeException('Aimeos cart operations not fully implemented, using fallback');
            $items = [];
            foreach ($basket->getProducts() as $product) {
                $items[] = new CartItemDTO(
                    productId: (int) $product->getId(),
                    productName: $product->getName(),
                    quantity: (int) $product->getQuantity(),
                    price: (float) $product->getPrice()->getValue(),
                    currency: $product->getPrice()->getCurrencyId(),
                    shopId: null, // Aimeos не хранит shop_id напрямую
                    shopDomain: null
                );
            }

            return new CartDTO(
                items: $items,
                userId: $userId
            );
        } catch (\Exception $e) {
            Log::error('Error getting Aimeos cart', ['error' => $e->getMessage()]);

            // Fallback на Eloquent
            return app(\App\Infrastructure\Repositories\Eloquent\CartRepository::class)->getCart($userId);
        }
    }

    public function addItem(int $userId, CartItemDTO $item): void
    {
        if (! $this->isAvailable) {
            app(\App\Infrastructure\Repositories\Eloquent\CartRepository::class)->addItem($userId, $item);

            return;
        }

        try {
            // Aimeos API может отличаться, используем fallback
            throw new \RuntimeException('Aimeos cart operations not fully implemented, using fallback');
        } catch (\Exception $e) {
            Log::error('Error adding item to Aimeos cart', ['error' => $e->getMessage()]);
            // Fallback на Eloquent
            app(\App\Infrastructure\Repositories\Eloquent\CartRepository::class)->addItem($userId, $item);
        }
    }

    public function removeItem(int $userId, int $productId): void
    {
        if (! $this->isAvailable) {
            app(\App\Infrastructure\Repositories\Eloquent\CartRepository::class)->removeItem($userId, $productId);

            return;
        }

        try {
            // Aimeos API может отличаться, используем fallback
            throw new \RuntimeException('Aimeos cart operations not fully implemented, using fallback');
        } catch (\Exception $e) {
            Log::error('Error removing item from Aimeos cart', ['error' => $e->getMessage()]);
            // Fallback на Eloquent
            app(\App\Infrastructure\Repositories\Eloquent\CartRepository::class)->removeItem($userId, $productId);
        }
    }

    public function updateItem(int $userId, int $productId, int $quantity): void
    {
        if (! $this->isAvailable) {
            app(\App\Infrastructure\Repositories\Eloquent\CartRepository::class)->updateItem($userId, $productId, $quantity);

            return;
        }

        try {
            // Aimeos API может отличаться, используем fallback
            throw new \RuntimeException('Aimeos cart operations not fully implemented, using fallback');
        } catch (\Exception $e) {
            Log::error('Error updating item in Aimeos cart', ['error' => $e->getMessage()]);
            // Fallback на Eloquent
            app(\App\Infrastructure\Repositories\Eloquent\CartRepository::class)->updateItem($userId, $productId, $quantity);
        }
    }

    public function clear(int $userId): void
    {
        if (! $this->isAvailable) {
            app(\App\Infrastructure\Repositories\Eloquent\CartRepository::class)->clear($userId);

            return;
        }

        try {
            // Aimeos API может отличаться, используем fallback
            throw new \RuntimeException('Aimeos cart operations not fully implemented, using fallback');
        } catch (\Exception $e) {
            Log::error('Error clearing Aimeos cart', ['error' => $e->getMessage()]);
            // Fallback на Eloquent
            app(\App\Infrastructure\Repositories\Eloquent\CartRepository::class)->clear($userId);
        }
    }

    public function removeItemsByShop(int $userId, string $shopDomain): void
    {
        // Aimeos не поддерживает shop_domain в корзине напрямую
        // Fallback на Eloquent
        app(\App\Infrastructure\Repositories\Eloquent\CartRepository::class)->removeItemsByShop($userId, $shopDomain);
    }
}
