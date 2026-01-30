<?php

declare(strict_types=1);

namespace App\Http\Actions\Delivery;

use App\Domains\Delivery\DTOs\CalculateDeliveryRequestDTO;
use App\Domains\Delivery\Enums\DeliveryProviderEnum;
use App\Domains\Delivery\Services\DeliveryService;
use App\Http\Requests\Api\V1\Delivery\CompareDeliveryRequest;
use App\Http\Resources\Api\V1\Delivery\DeliveryListResource;
use Illuminate\Http\JsonResponse;

final readonly class CompareDeliveryAction
{
    public function __construct(
        private DeliveryService $deliveryService,
    ) {}

    public function execute(CompareDeliveryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dto = new CalculateDeliveryRequestDTO(
            provider: DeliveryProviderEnum::DHL,
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

        $results = $this->deliveryService->compareProviders($dto);

        return (new DeliveryListResource($results))->response();
    }
}
