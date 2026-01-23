<?php

declare(strict_types=1);

namespace App\Http\Actions\Order;

use App\Domains\Order\Services\OrderService;
use App\Http\Resources\Api\V1\Order\OrderListResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ListOrdersAction
{
    public function __construct(
        private OrderService $orderService,
    ) {
    }

    public function execute(Request $request): JsonResponse
    {
        $orders = $this->orderService->getUserOrders($request->user()->id);

        return (new OrderListResource($orders))->response();
    }
}


