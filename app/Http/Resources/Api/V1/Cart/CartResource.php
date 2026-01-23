<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Cart;

use App\Domains\Cart\DTOs\CartDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CartDTO $cart */
        $cart = $this->resource;

        return [
            'items' => CartItemResource::collection($cart->getItems()),
            'total' => $cart->getTotal(),
        ];
    }
}
