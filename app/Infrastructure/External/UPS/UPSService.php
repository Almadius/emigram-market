<?php

declare(strict_types=1);

namespace App\Infrastructure\External\UPS;

use App\Domains\Delivery\Contracts\DeliveryServiceInterface;
use App\Domains\Delivery\DTOs\CalculateDeliveryRequestDTO;
use App\Domains\Delivery\DTOs\CalculateDeliveryResponseDTO;
use App\Domains\Delivery\Enums\DeliveryProviderEnum;
use Illuminate\Support\Facades\Log;

final class UPSService implements DeliveryServiceInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiSecret,
        private readonly bool $sandbox = false,
    ) {}

    public function calculate(CalculateDeliveryRequestDTO $request): CalculateDeliveryResponseDTO
    {
        try {
            // In a real implementation, this would call UPS API
            // For MVP, we'll use a mock calculation
            $baseUrl = $this->sandbox
                ? 'https://wwwcie.ups.com/api/rating'
                : 'https://onlinetools.ups.com/api/rating';

            // Mock calculation for MVP
            $distance = $this->calculateDistance(
                $request->getFromCountry(),
                $request->getToCountry()
            );

            $baseCost = $request->getWeight() * 4.5; // €4.5 per kg
            $distanceCost = $distance * 0.4; // €0.4 per km
            $cost = $baseCost + $distanceCost;
            $estimatedDays = (int) ceil($distance / 750); // ~750 km per day

            return new CalculateDeliveryResponseDTO(
                provider: DeliveryProviderEnum::UPS,
                cost: round($cost, 2),
                currency: $request->getCurrency(),
                estimatedDays: max(1, $estimatedDays),
                serviceLevel: 'Ground'
            );
        } catch (\Exception $e) {
            Log::error('UPS calculation error', [
                'error' => $e->getMessage(),
                'request' => $request->toArray() ?? 'N/A',
            ]);

            // Return fallback values
            return new CalculateDeliveryResponseDTO(
                provider: DeliveryProviderEnum::UPS,
                cost: 12.0,
                currency: $request->getCurrency(),
                estimatedDays: 4,
                serviceLevel: 'Ground'
            );
        }
    }

    public function createShipment(CalculateDeliveryRequestDTO $request): string
    {
        // In a real implementation, this would create a shipment in UPS
        // and return a tracking number
        return 'UPS'.strtoupper(uniqid('', true));
    }

    public function track(string $trackingNumber): array
    {
        // In a real implementation, this would query UPS tracking API
        return [
            'tracking_number' => $trackingNumber,
            'status' => 'in_transit',
            'current_location' => 'Unknown',
            'estimated_delivery' => null,
        ];
    }

    private function calculateDistance(string $fromCountry, string $toCountry): float
    {
        // Mock distance calculation
        // In a real implementation, use a geocoding service
        $distances = [
            'DE' => ['NL' => 300, 'BE' => 250, 'FR' => 500],
            'NL' => ['DE' => 300, 'BE' => 150, 'FR' => 400],
            'BE' => ['DE' => 250, 'NL' => 150, 'FR' => 300],
            'FR' => ['DE' => 500, 'NL' => 400, 'BE' => 300],
        ];

        return (float) ($distances[$fromCountry][$toCountry] ?? 1000);
    }
}
