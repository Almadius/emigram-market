<?php

declare(strict_types=1);

namespace App\Http\Actions\Cart;

use App\Domains\Cart\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

final readonly class RemoveCartItemAction
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function execute(Request $request, int $productId): JsonResponse
    {
        $this->cartService->removeItem($request->user()->id, $productId);

        return Response::json(['message' => 'Item removed from cart']);
    }
}
