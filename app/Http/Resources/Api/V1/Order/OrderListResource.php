<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class OrderListResource extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => OrderResource::collection($this->collection),
        ];
    }
}
