<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Actions\Order\CreateOrderAction;
use App\Http\Actions\Order\ListOrdersAction;
use App\Http\Actions\Order\ShowOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\CreateOrderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    public function __construct(
        private readonly ListOrdersAction $listOrdersAction,
        private readonly CreateOrderAction $createOrderAction,
        private readonly ShowOrderAction $showOrderAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->listOrdersAction->execute($request);
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        return $this->createOrderAction->execute($request);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->showOrderAction->execute($request, $id);
    }
}
