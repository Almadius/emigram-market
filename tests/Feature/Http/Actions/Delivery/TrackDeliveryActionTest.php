<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Actions\Delivery;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class TrackDeliveryActionTest extends TestCase
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

    public function test_track_delivery_with_dhl(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/delivery/track/dhl/ABC123456789');

        $response->assertStatus(200);
        $responseData = $response->json();

        $this->assertArrayHasKey('tracking_number', $responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('ABC123456789', $responseData['tracking_number']);
    }

    public function test_track_delivery_with_ups(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/delivery/track/ups/UPS123456789');

        $response->assertStatus(200);
        $responseData = $response->json();

        $this->assertArrayHasKey('tracking_number', $responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('UPS123456789', $responseData['tracking_number']);
    }

    public function test_track_delivery_with_invalid_provider(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/delivery/track/invalid/ABC123456789');

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'BAD_REQUEST',
        ]);
    }
}
