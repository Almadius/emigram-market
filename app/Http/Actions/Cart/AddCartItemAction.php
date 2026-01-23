<?php

declare(strict_types=1);

namespace App\Http\Actions\Cart;

use App\Domains\Cart\DTOs\CartItemDTO;
use App\Domains\Cart\Services\CartService;
use App\Domains\Product\Services\ProductService;
use App\Http\Requests\Api\V1\Cart\AddCartItemRequest;
use Illuminate\Http\JsonResponse;
use App\Exceptions\NotFoundException;
use Illuminate\Support\Facades\Response;

final readonly class AddCartItemAction
{
    public function __construct(
        private CartService $cartService,
        private ProductService $productService,
    ) {
    }

    public function execute(AddCartItemRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $request->user()->id;

        $product = $this->productService->findById($validated['product_id'], $userId);

        if ($product === null) {
            throw new NotFoundException('Product not found');
        }

        $price = $product->getEmigramPrice()?->getEmigramPrice()
            ?? $product->getStorePrice()?->getStorePrice()
            ?? $product->getProduct()->getPrice()
            ?? 0.0;

        $currency = $product->getEmigramPrice()?->getCurrency()
            ?? $product->getStorePrice()?->getCurrency()
            ?? $product->getProduct()->getCurrency()
            ?? 'EUR';

        $item = new CartItemDTO(
            productId: $validated['product_id'],
            productName: $product->getProduct()->getName(),
            quantity: $validated['quantity'],
            price: $price,
            currency: $currency,
            shopId: $product->getProduct()->getShopId(),
            shopDomain: $product->getProduct()->getShopDomain(),
        );

        $this->cartService->addItem($userId, $item);

        return Response::json(['message' => 'Item added to cart'], 201);
    }
}

