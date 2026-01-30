<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Actions\Metrics;

use App\Models\User;
use App\Services\MetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class GetMetricsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_metrics_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/metrics');

        $response->assertStatus(401);
    }

    public function test_get_metrics_returns_metrics(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $metrics = app(MetricsService::class);
        $metrics->increment('orders.created', 5);
        $metrics->increment('shop_orders.created', 3);

        $response = $this->getJson('/api/v1/metrics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'metrics' => [
                'orders_created',
                'orders_failed',
                'shop_orders_created',
                'shop_orders_failed',
                'products_synced',
                'price_resolves',
                'slow_requests',
            ],
            'timestamp',
        ]);
    }
}
