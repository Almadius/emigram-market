<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Order;

use App\Domains\Order\DTOs\OrderDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var OrderDTO $order */
        $order = $this->resource;

        return [
            'id' => $order->getId(),
            'status' => $order->getStatus()->value,
            'total' => $order->getTotal(),
            'currency' => $order->getCurrency(),
            'shop_domain' => $order->getShopDomain(),
            'created_at' => $order->getCreatedAt()?->format('c'),
            'items' => OrderItemResource::collection($order->getItems()),
        ];
    }
}
