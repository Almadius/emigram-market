<?php

declare(strict_types=1);

namespace App\Http\Actions\Order;

use App\Domains\Cart\Services\CartService;
use App\Domains\Cart\Services\CartSplitService;
use App\Domains\Order\Commands\CreateOrderCommand;
use App\Domains\Order\Services\OrderService;
use App\Exceptions\BadRequestException;
use App\Http\Requests\Api\V1\Order\CreateOrderRequest;
use App\Http\Resources\Api\V1\Order\OrderResource;
use Illuminate\Http\JsonResponse;

final readonly class CreateOrderAction
{
    public function __construct(
        private OrderService $orderService,
        private CartService $cartService,
        private CartSplitService $cartSplitService,
    ) {}

    public function execute(CreateOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $request->user()->id;

        $cart = $this->cartService->getCart($userId);

        $items = $this->cartSplitService->getItemsForShop(
            $cart,
            $validated['shop_domain']
        );

        if (empty($items)) {
            throw new BadRequestException('No items for this shop in cart. Add items to cart before checkout');
        }

        $command = new CreateOrderCommand(
            userId: $userId,
            shopId: $validated['shop_id'],
            shopDomain: $validated['shop_domain'],
            items: $items,
            currency: $validated['currency'] ?? 'EUR'
        );

        $order = $this->orderService->createOrder($command);

        // Remove only items from this shop, not the entire cart
        $this->cartService->removeItemsByShop($userId, $validated['shop_domain']);

        return (new OrderResource($order))->response()->setStatusCode(201);
    }
}
