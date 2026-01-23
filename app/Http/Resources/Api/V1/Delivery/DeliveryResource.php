<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Delivery;

use App\Domains\Delivery\DTOs\CalculateDeliveryResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CalculateDeliveryResponseDTO $delivery */
        $delivery = $this->resource;

        return $delivery->toArray();
    }
}
