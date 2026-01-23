<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Order;

use App\Domains\Order\DTOs\OrderItemDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var OrderItemDTO $item */
        $item = $this->resource;

        return [
            'product_id' => $item->getProductId(),
            'product_name' => $item->getProductName(),
            'quantity' => $item->getQuantity(),
            'price' => $item->getPrice(),
            'total' => $item->getTotal(),
        ];
    }
}
