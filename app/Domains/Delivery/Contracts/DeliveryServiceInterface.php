<?php

declare(strict_types=1);

namespace App\Domains\Delivery\Contracts;

use App\Domains\Delivery\DTOs\CalculateDeliveryRequestDTO;
use App\Domains\Delivery\DTOs\CalculateDeliveryResponseDTO;

interface DeliveryServiceInterface
{
    /**
     * Calculate delivery cost and estimated time
     */
    public function calculate(CalculateDeliveryRequestDTO $request): CalculateDeliveryResponseDTO;

    /**
     * Create shipment and get tracking number
     */
    public function createShipment(CalculateDeliveryRequestDTO $request): string;

    /**
     * Track shipment by tracking number
     */
    public function track(string $trackingNumber): array;
}
