<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Actions\Shop;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class SyncProductsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_products_requires_authentication(): void
    {
        $shop = Shop::factory()->create();

        $response = $this->postJson("/api/v1/shops/{$shop->id}/sync-products");

        $response->assertStatus(401);
    }

    public function test_sync_products_queues_job_when_use_queue_is_true(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $shop = Shop::factory()->create([
            'is_active' => true,
        ]);

        \Illuminate\Support\Facades\Queue::fake();

        $response = $this->postJson("/api/v1/shops/{$shop->id}/sync-products", [
            'use_queue' => true,
            'max_pages' => 5,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Product synchronization queued',
            'shop_id' => $shop->id,
        ]);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Domains\Shop\Jobs\SyncShopProductsJob::class);
    }

    public function test_sync_products_fails_for_inactive_shop(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $shop = Shop::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->postJson("/api/v1/shops/{$shop->id}/sync-products");

        $response->assertStatus(500);
        $response->assertJsonStructure([
            'error',
        ]);
    }

    public function test_sync_products_validates_max_pages(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $shop = Shop::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->postJson("/api/v1/shops/{$shop->id}/sync-products", [
            'max_pages' => 200, // Превышает максимум
        ]);

        $response->assertStatus(422);
    }
}
