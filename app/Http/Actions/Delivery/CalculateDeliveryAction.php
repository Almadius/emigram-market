<?php

declare(strict_types=1);

namespace App\Http\Actions\Delivery;

use App\Domains\Delivery\DTOs\CalculateDeliveryRequestDTO;
use App\Domains\Delivery\Enums\DeliveryProviderEnum;
use App\Domains\Delivery\Services\DeliveryService;
use App\Http\Requests\Api\V1\Delivery\CalculateDeliveryRequest;
use App\Http\Resources\Api\V1\Delivery\DeliveryResource;
use Illuminate\Http\JsonResponse;

final readonly class CalculateDeliveryAction
{
    public function __construct(
        private DeliveryService $deliveryService,
    ) {
    }

    public function execute(CalculateDeliveryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dto = new CalculateDeliveryRequestDTO(
            provider: DeliveryProviderEnum::from($validated['provider']),
            fromCountry: $validated['from_country'],
            fromCity: $validated['from_city'],
            fromPostalCode: $validated['from_postal_code'],
            toCountry: $validated['to_country'],
            toCity: $validated['to_city'],
            toPostalCode: $validated['to_postal_code'],
            weight: (float) $validated['weight'],
            value: (float) $validated['value'],
            currency: $validated['currency'] ?? 'EUR'
        );

        $response = $this->deliveryService->calculate($dto);

        return (new DeliveryResource($response))->response();
    }
}


