<?php

declare(strict_types=1);

namespace App\Domains\Shop\Jobs;

use App\Domains\Shop\Services\ProductSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job для фоновой синхронизации товаров из магазина
 */
final class SyncShopProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Количество попыток выполнения job
     */
    public int $tries = 3;

    /**
     * Время ожидания перед повторной попыткой (в секундах)
     */
    public int $backoff = 120;

    /**
     * Таймаут выполнения job (в секундах)
     */
    public int $timeout = 300;

    public function __construct(
        private readonly int $shopId,
        private readonly int $maxPages = 10,
    ) {}

    public function handle(ProductSyncService $syncService): void
    {
        try {
            $syncedCount = $syncService->syncShopProducts($this->shopId, $this->maxPages);

            Log::info('SyncShopProductsJob: Completed', [
                'shop_id' => $this->shopId,
                'synced_count' => $syncedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('SyncShopProductsJob: Failed', [
                'shop_id' => $this->shopId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Позволяем Laravel обработать повторные попытки
        }
    }

    /**
     * Обработка окончательной неудачи после всех попыток
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncShopProductsJob: Job failed permanently after all retries', [
            'shop_id' => $this->shopId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
