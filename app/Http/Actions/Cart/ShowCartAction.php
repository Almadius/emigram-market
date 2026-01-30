<?php

declare(strict_types=1);

namespace App\Http\Actions\Cart;

use App\Domains\Cart\Services\CartService;
use App\Http\Resources\Api\V1\Cart\CartResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ShowCartAction
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function execute(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user()->id);

        return (new CartResource($cart))->response();
    }
}
