<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Actions\Installment;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class CalculateInstallmentActionTest extends TestCase
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

    public function testCalculateInstallmentSuccessfully(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/installments/calculate', [
                'amount' => 1000.0,
                'months' => 12,
                'currency' => 'EUR',
            ]);

        $response->assertStatus(200);
        $responseData = $response->json();
        
        $data = $responseData['data'] ?? $responseData;
        $this->assertArrayHasKey('approved', $data);
        $this->assertArrayHasKey('limit', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertIsBool($data['approved']);
        $this->assertArrayHasKey('max_amount', $data['limit']);
        $this->assertArrayHasKey('max_months', $data['limit']);
    }

    public function testCalculateInstallmentWithInvalidAmount(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/installments/calculate', [
                'amount' => -100.0,
                'months' => 12,
                'currency' => 'EUR',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'VALIDATION_ERROR',
        ]);
    }
}


