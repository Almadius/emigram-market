<?php

declare(strict_types=1);

namespace App\Http\Actions\Cart;

use App\Domains\Cart\Services\CartService;
use App\Http\Requests\Api\V1\Cart\UpdateCartItemRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

final readonly class UpdateCartItemAction
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function execute(UpdateCartItemRequest $request, int $productId): JsonResponse
    {
        $validated = $request->validated();

        $this->cartService->updateItem(
            $request->user()->id,
            $productId,
            $validated['quantity']
        );

        return Response::json(['message' => 'Item updated']);
    }
}
