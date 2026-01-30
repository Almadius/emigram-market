<?php

declare(strict_types=1);

namespace App\Domains\Delivery\DTOs;

use App\Domains\Delivery\Enums\DeliveryProviderEnum;

final readonly class CalculateDeliveryRequestDTO
{
    public function __construct(
        private DeliveryProviderEnum $provider,
        private string $fromCountry,
        private string $fromCity,
        private string $fromPostalCode,
        private string $toCountry,
        private string $toCity,
        private string $toPostalCode,
        private float $weight, // in kg
        private float $value, // in EUR
        private string $currency = 'EUR',
    ) {
        if ($weight <= 0) {
            throw new \InvalidArgumentException('Weight must be positive');
        }
        if ($value < 0) {
            throw new \InvalidArgumentException('Value cannot be negative');
        }
    }

    public function getProvider(): DeliveryProviderEnum
    {
        return $this->provider;
    }

    public function getFromCountry(): string
    {
        return $this->fromCountry;
    }

    public function getFromCity(): string
    {
        return $this->fromCity;
    }

    public function getFromPostalCode(): string
    {
        return $this->fromPostalCode;
    }

    public function getToCountry(): string
    {
        return $this->toCountry;
    }

    public function getToCity(): string
    {
        return $this->toCity;
    }

    public function getToPostalCode(): string
    {
        return $this->toPostalCode;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}
