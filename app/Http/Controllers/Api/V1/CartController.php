<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Actions\Cart\AddCartItemAction;
use App\Http\Actions\Cart\ClearCartAction;
use App\Http\Actions\Cart\RemoveCartItemAction;
use App\Http\Actions\Cart\ShowCartAction;
use App\Http\Actions\Cart\UpdateCartItemAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cart\AddCartItemRequest;
use App\Http\Requests\Api\V1\Cart\UpdateCartItemRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CartController extends Controller
{
    public function __construct(
        private readonly ShowCartAction $showCartAction,
        private readonly AddCartItemAction $addCartItemAction,
        private readonly RemoveCartItemAction $removeCartItemAction,
        private readonly UpdateCartItemAction $updateCartItemAction,
        private readonly ClearCartAction $clearCartAction,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return $this->showCartAction->execute($request);
    }

    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        return $this->addCartItemAction->execute($request);
    }

    public function removeItem(Request $request, int $productId): JsonResponse
    {
        return $this->removeCartItemAction->execute($request, $productId);
    }

    public function updateItem(UpdateCartItemRequest $request, int $productId): JsonResponse
    {
        return $this->updateCartItemAction->execute($request, $productId);
    }

    public function clear(Request $request): JsonResponse
    {
        return $this->clearCartAction->execute($request);
    }
}
