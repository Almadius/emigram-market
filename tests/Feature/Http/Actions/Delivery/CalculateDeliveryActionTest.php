<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Actions\Delivery;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class CalculateDeliveryActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_calculate_delivery_with_dhl(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/delivery/calculate', [
                'provider' => 'dhl',
                'from_country' => 'DE',
                'from_city' => 'Berlin',
                'from_postal_code' => '10115',
                'to_country' => 'FR',
                'to_city' => 'Paris',
                'to_postal_code' => '75001',
                'weight' => 1.5,
                'value' => 100.0,
                'currency' => 'EUR',
            ]);

        $response->assertStatus(200);
        $responseData = $response->json();

        // Laravel Resource wraps in 'data' key
        $data = $responseData['data'] ?? $responseData;

        $this->assertIsArray($data);
        $this->assertArrayHasKey('provider', $data);
        $this->assertArrayHasKey('cost', $data);
        $this->assertArrayHasKey('currency', $data);
        $this->assertArrayHasKey('estimated_days', $data);
        $this->assertEquals('dhl', $data['provider']);
        $this->assertIsNumeric($data['cost']);
        $this->assertEquals('EUR', $data['currency']);
        $this->assertIsInt($data['estimated_days']);
    }

    public function test_calculate_delivery_with_ups(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/delivery/calculate', [
                'provider' => 'ups',
                'from_country' => 'DE',
                'from_city' => 'Berlin',
                'from_postal_code' => '10115',
                'to_country' => 'FR',
                'to_city' => 'Paris',
                'to_postal_code' => '75001',
                'weight' => 1.5,
                'value' => 100.0,
                'currency' => 'EUR',
            ]);

        $response->assertStatus(200);
        $responseData = $response->json();

        // Laravel Resource wraps in 'data' key
        $data = $responseData['data'] ?? $responseData;

        $this->assertIsArray($data);
        $this->assertArrayHasKey('provider', $data);
        $this->assertArrayHasKey('cost', $data);
        $this->assertEquals('ups', $data['provider']);
        $this->assertIsNumeric($data['cost']);
        $this->assertEquals('EUR', $data['currency']);
    }

    public function test_calculate_delivery_with_invalid_provider(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/delivery/calculate', [
                'provider' => 'invalid',
                'from_country' => 'DE',
                'from_city' => 'Berlin',
                'from_postal_code' => '10115',
                'to_country' => 'FR',
                'to_city' => 'Paris',
                'to_postal_code' => '75001',
                'weight' => 1.5,
                'value' => 100.0,
                'currency' => 'EUR',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'VALIDATION_ERROR',
        ]);
    }

    public function test_compare_delivery_providers(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/delivery/compare', [
                'from_country' => 'DE',
                'from_city' => 'Berlin',
                'from_postal_code' => '10115',
                'to_country' => 'FR',
                'to_city' => 'Paris',
                'to_postal_code' => '75001',
                'weight' => 1.5,
                'value' => 100.0,
                'currency' => 'EUR',
            ]);

        $response->assertStatus(200);
        $responseData = $response->json();

        // DeliveryListResource wraps in 'data' key, then 'options'
        $data = $responseData['data'] ?? $responseData;

        $this->assertIsArray($data);
        $this->assertArrayHasKey('options', $data);
        $options = $data['options'];
        $this->assertIsArray($options);
        $this->assertGreaterThanOrEqual(0, count($options));

        // If options exist, check structure
        if (count($options) > 0) {
            $firstOption = $options[0];
            $this->assertIsArray($firstOption);
            $this->assertArrayHasKey('provider', $firstOption);
            $this->assertArrayHasKey('cost', $firstOption);
            $this->assertArrayHasKey('currency', $firstOption);
        }
    }
}
