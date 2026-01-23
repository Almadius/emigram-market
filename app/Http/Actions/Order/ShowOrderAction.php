<?php

declare(strict_types=1);

namespace App\Http\Actions\Order;

use App\Domains\Order\Queries\GetOrderQuery;
use App\Domains\Order\Services\OrderService;
use App\Http\Resources\Api\V1\Order\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Exceptions\NotFoundException;

final readonly class ShowOrderAction
{
    public function __construct(
        private OrderService $orderService,
    ) {
    }

    public function execute(Request $request, int $id): JsonResponse
    {
        $query = new GetOrderQuery(
            orderId: $id,
            userId: $request->user()->id
        );

        $order = $this->orderService->getOrder($query);

        if ($order === null) {
            throw new NotFoundException('Order not found');
        }

        return (new OrderResource($order))->response();
    }
}

