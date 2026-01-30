<?php

declare(strict_types=1);

namespace App\Http\Actions\Cart;

use App\Domains\Cart\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

final readonly class ClearCartAction
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function execute(Request $request): JsonResponse
    {
        $this->cartService->clear($request->user()->id);

        return Response::json(['message' => 'Cart cleared']);
    }
}
