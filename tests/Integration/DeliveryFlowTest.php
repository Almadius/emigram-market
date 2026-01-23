<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domains\Delivery\DTOs\CalculateDeliveryRequestDTO;
use App\Domains\Delivery\Enums\DeliveryProviderEnum;
use App\Domains\Delivery\Services\DeliveryService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Integration test for delivery flow:
 * 1. Calculate delivery cost for single provider
 * 2. Compare delivery options from multiple providers
 * 3. Track delivery status
 */
final class DeliveryFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private DeliveryService $deliveryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->deliveryService = $this->app->make(DeliveryService::class);
    }

    public function testCalculateDeliveryForDHL(): void
    {
        $request = new CalculateDeliveryRequestDTO(
            provider: DeliveryProviderEnum::DHL,
            fromCountry: 'DE',
            fromCity: 'Berlin',
            fromPostalCode: '10115',
            toCountry: 'FR',
            toCity: 'Paris',
            toPostalCode: '75001',
            weight: 2.5,
            value: 100.0,
            currency: 'EUR'
        );

        $response = $this->deliveryService->calculate($request);

        $this->assertNotNull($response);
        $this->assertEquals(DeliveryProviderEnum::DHL, $response->getProvider());
        $this->assertIsFloat($response->getCost());
        $this->assertGreaterThanOrEqual(0, $response->getCost());
        $this->assertEquals('EUR', $response->getCurrency());
        $this->assertIsInt($response->getEstimatedDays());
        $this->assertGreaterThanOrEqual(0, $response->getEstimatedDays());
    }

    public function testCalculateDeliveryForUPS(): void
    {
        $request = new CalculateDeliveryRequestDTO(
            provider: DeliveryProviderEnum::UPS,
            fromCountry: 'DE',
            fromCity: 'Berlin',
            fromPostalCode: '10115',
            toCountry: 'FR',
            toCity: 'Paris',
            toPostalCode: '75001',
            weight: 2.5,
            value: 100.0,
            currency: 'EUR'
        );

        $response = $this->deliveryService->calculate($request);

        $this->assertNotNull($response);
        $this->assertEquals(DeliveryProviderEnum::UPS, $response->getProvider());
        $this->assertIsFloat($response->getCost());
        $this->assertGreaterThanOrEqual(0, $response->getCost());
        $this->assertEquals('EUR', $response->getCurrency());
    }

    public function testCompareDeliveryProviders(): void
    {
        $request = new CalculateDeliveryRequestDTO(
            provider: DeliveryProviderEnum::DHL, // Provider is required but will be overridden in compare
            fromCountry: 'DE',
            fromCity: 'Berlin',
            fromPostalCode: '10115',
            toCountry: 'FR',
            toCity: 'Paris',
            toPostalCode: '75001',
            weight: 2.5,
            value: 100.0,
            currency: 'EUR'
        );

        $results = $this->deliveryService->compareProviders($request);

        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results), 'Should have at least one provider result');

        // Verify each result structure
        foreach ($results as $result) {
            $this->assertInstanceOf(
                \App\Domains\Delivery\DTOs\CalculateDeliveryResponseDTO::class,
                $result
            );
            $this->assertIsFloat($result->getCost());
            $this->assertGreaterThanOrEqual(0, $result->getCost());
            $this->assertIsInt($result->getEstimatedDays());
        }
    }

    public function testTrackDeliveryDHL(): void
    {
        $trackingNumber = 'ABC123456789';
        
        $tracking = $this->deliveryService->track(
            DeliveryProviderEnum::DHL,
            $trackingNumber
        );

        $this->assertIsArray($tracking);
        $this->assertArrayHasKey('tracking_number', $tracking);
        $this->assertEquals($trackingNumber, $tracking['tracking_number']);
        $this->assertArrayHasKey('status', $tracking);
    }

    public function testTrackDeliveryUPS(): void
    {
        $trackingNumber = 'UPS123456789';
        
        $tracking = $this->deliveryService->track(
            DeliveryProviderEnum::UPS,
            $trackingNumber
        );

        $this->assertIsArray($tracking);
        $this->assertArrayHasKey('tracking_number', $tracking);
        $this->assertEquals($trackingNumber, $tracking['tracking_number']);
        $this->assertArrayHasKey('status', $tracking);
    }

    public function testDeliveryCalculationViaAPI(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        // Calculate delivery via API
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/delivery/calculate', [
                'provider' => 'dhl',
                'from_country' => 'DE',
                'from_city' => 'Berlin',
                'from_postal_code' => '10115',
                'to_country' => 'FR',
                'to_city' => 'Paris',
                'to_postal_code' => '75001',
                'weight' => 2.5,
                'value' => 100.0,
                'currency' => 'EUR',
            ]);

        $response->assertStatus(200);
        $responseData = $response->json();
        
        $data = $responseData['data'] ?? $responseData;
        $this->assertArrayHasKey('provider', $data);
        $this->assertArrayHasKey('cost', $data);
        $this->assertArrayHasKey('currency', $data);
        $this->assertEquals('dhl', $data['provider']);
    }

    public function testDeliveryComparisonViaAPI(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        // Compare delivery options via API
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/delivery/compare', [
                'from_country' => 'DE',
                'from_city' => 'Berlin',
                'from_postal_code' => '10115',
                'to_country' => 'FR',
                'to_city' => 'Paris',
                'to_postal_code' => '75001',
                'weight' => 2.5,
                'value' => 100.0,
                'currency' => 'EUR',
            ]);

        $response->assertStatus(200);
        $responseData = $response->json();
        
        $data = $responseData['data'] ?? $responseData;
        $this->assertArrayHasKey('options', $data);
        $options = $data['options'];
        $this->assertIsArray($options);
        $this->assertGreaterThanOrEqual(1, count($options));
    }
}

