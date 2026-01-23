<?php

declare(strict_types=1);

namespace App\Domains\Delivery\DTOs;

use App\Domains\Delivery\Enums\DeliveryProviderEnum;

final readonly class CalculateDeliveryResponseDTO
{
    public function __construct(
        private DeliveryProviderEnum $provider,
        private float $cost,
        private string $currency,
        private int $estimatedDays,
        private ?string $trackingNumber = null,
        private ?string $serviceLevel = null,
    ) {
        if ($cost < 0) {
            throw new \InvalidArgumentException('Cost cannot be negative');
        }
        if ($estimatedDays < 0) {
            throw new \InvalidArgumentException('Estimated days cannot be negative');
        }
    }

    public function getProvider(): DeliveryProviderEnum
    {
        return $this->provider;
    }

    public function getCost(): float
    {
        return $this->cost;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getEstimatedDays(): int
    {
        return $this->estimatedDays;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function getServiceLevel(): ?string
    {
        return $this->serviceLevel;
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->provider->value,
            'cost' => $this->cost,
            'currency' => $this->currency,
            'estimated_days' => $this->estimatedDays,
            'tracking_number' => $this->trackingNumber,
            'service_level' => $this->serviceLevel,
        ];
    }
}


