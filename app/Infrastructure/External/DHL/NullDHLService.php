<?php

declare(strict_types=1);

namespace App\Infrastructure\External\DHL;

use App\Domains\Delivery\Contracts\DeliveryServiceInterface;
use App\Domains\Delivery\DTOs\CalculateDeliveryRequestDTO;
use App\Domains\Delivery\DTOs\CalculateDeliveryResponseDTO;
use App\Domains\Delivery\Enums\DeliveryProviderEnum;

final class NullDHLService implements DeliveryServiceInterface
{
    public function calculate(CalculateDeliveryRequestDTO $request): CalculateDeliveryResponseDTO
    {
        // Mock calculation for development
        $baseCost = $request->getWeight() * 5.0;
        $cost = $baseCost + 10.0; // Base shipping cost

        return new CalculateDeliveryResponseDTO(
            provider: DeliveryProviderEnum::DHL,
            cost: round($cost, 2),
            currency: $request->getCurrency(),
            estimatedDays: 5,
            serviceLevel: 'Standard'
        );
    }

    public function createShipment(CalculateDeliveryRequestDTO $request): string
    {
        return 'DHL_MOCK_'.strtoupper(uniqid('', true));
    }

    public function track(string $trackingNumber): array
    {
        return [
            'tracking_number' => $trackingNumber,
            'status' => 'in_transit',
            'current_location' => 'Mock Location',
            'estimated_delivery' => date('Y-m-d', strtotime('+3 days')),
        ];
    }
}
