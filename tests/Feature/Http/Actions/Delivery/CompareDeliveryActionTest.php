<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Actions\Delivery;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class CompareDeliveryActionTest extends TestCase
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

    public function test_compare_delivery_successfully(): void
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
                'weight' => 2.5,
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

        // Should return array of delivery options from different providers
        if (count($options) > 0) {
            $delivery = $options[0];
            $this->assertArrayHasKey('provider', $delivery);
            $this->assertArrayHasKey('cost', $delivery);
            $this->assertArrayHasKey('currency', $delivery);
        }
    }

    public function test_compare_delivery_with_invalid_data(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/delivery/compare', [
                'from_country' => '',
                'to_country' => 'FR',
                'weight' => -1,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'VALIDATION_ERROR',
        ]);
    }
}
