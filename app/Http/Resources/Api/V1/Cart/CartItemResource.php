<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Cart;

use App\Domains\Cart\DTOs\CartItemDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CartItemDTO $item */
        $item = $this->resource;

        return [
            'product_id' => $item->getProductId(),
            'product_name' => $item->getProductName(),
            'quantity' => $item->getQuantity(),
            'price' => $item->getPrice(),
            'total' => $item->getTotal(),
            'currency' => $item->getCurrency(),
            'shop_id' => $item->getShopId(),
            'shop_domain' => $item->getShopDomain(),
        ];
    }
}
