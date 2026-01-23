<?php

declare(strict_types=1);

namespace App\Infrastructure\External\UPS;

use App\Domains\Delivery\Contracts\DeliveryServiceInterface;
use App\Domains\Delivery\DTOs\CalculateDeliveryRequestDTO;
use App\Domains\Delivery\DTOs\CalculateDeliveryResponseDTO;
use App\Domains\Delivery\Enums\DeliveryProviderEnum;

final class NullUPSService implements DeliveryServiceInterface
{
    public function calculate(CalculateDeliveryRequestDTO $request): CalculateDeliveryResponseDTO
    {
        // Mock calculation for development
        $baseCost = $request->getWeight() * 4.5;
        $cost = $baseCost + 8.0; // Base shipping cost

        return new CalculateDeliveryResponseDTO(
            provider: DeliveryProviderEnum::UPS,
            cost: round($cost, 2),
            currency: $request->getCurrency(),
            estimatedDays: 4,
            serviceLevel: 'Ground'
        );
    }

    public function createShipment(CalculateDeliveryRequestDTO $request): string
    {
        return 'UPS_MOCK_' . strtoupper(uniqid('', true));
    }

    public function track(string $trackingNumber): array
    {
        return [
            'tracking_number' => $trackingNumber,
            'status' => 'in_transit',
            'current_location' => 'Mock Location',
            'estimated_delivery' => date('Y-m-d', strtotime('+2 days')),
        ];
    }
}


