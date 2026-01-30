<?php

declare(strict_types=1);

namespace App\Domains\Delivery\Services;

use App\Domains\Delivery\Contracts\DeliveryServiceInterface;
use App\Domains\Delivery\DTOs\CalculateDeliveryRequestDTO;
use App\Domains\Delivery\DTOs\CalculateDeliveryResponseDTO;
use App\Domains\Delivery\Enums\DeliveryProviderEnum;

final class DeliveryService
{
    /**
     * @param  array<DeliveryServiceInterface>  $providers
     */
    public function __construct(
        private readonly array $providers,
    ) {}

    public function calculate(CalculateDeliveryRequestDTO $request): CalculateDeliveryResponseDTO
    {
        $provider = $this->getProvider($request->getProvider());

        return $provider->calculate($request);
    }

    public function createShipment(CalculateDeliveryRequestDTO $request): string
    {
        $provider = $this->getProvider($request->getProvider());

        return $provider->createShipment($request);
    }

    public function track(DeliveryProviderEnum $provider, string $trackingNumber): array
    {
        $providerService = $this->getProvider($provider);

        return $providerService->track($trackingNumber);
    }

    /**
     * Compare delivery options from all providers
     *
     * @return array<CalculateDeliveryResponseDTO>
     */
    public function compareProviders(CalculateDeliveryRequestDTO $request): array
    {
        $results = [];

        foreach (DeliveryProviderEnum::cases() as $providerEnum) {
            try {
                $providerService = $this->getProvider($providerEnum);

                $modifiedRequest = new CalculateDeliveryRequestDTO(
                    provider: $providerEnum,
                    fromCountry: $request->getFromCountry(),
                    fromCity: $request->getFromCity(),
                    fromPostalCode: $request->getFromPostalCode(),
                    toCountry: $request->getToCountry(),
                    toCity: $request->getToCity(),
                    toPostalCode: $request->getToPostalCode(),
                    weight: $request->getWeight(),
                    value: $request->getValue(),
                    currency: $request->getCurrency(),
                );

                $results[] = $providerService->calculate($modifiedRequest);
            } catch (\Exception $e) {
                // Skip provider if calculation fails
                continue;
            }
        }

        return $results;
    }

    private function getProvider(DeliveryProviderEnum $providerEnum): DeliveryServiceInterface
    {
        $provider = $this->providers[$providerEnum->value] ?? null;

        if ($provider === null) {
            throw new \InvalidArgumentException("Provider {$providerEnum->value} not available");
        }

        return $provider;
    }
}
