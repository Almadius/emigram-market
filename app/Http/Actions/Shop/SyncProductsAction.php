<?php

declare(strict_types=1);

namespace App\Http\Actions\Shop;

use App\Domains\Shop\Jobs\SyncShopProductsJob;
use App\Domains\Shop\Services\ProductSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Action для синхронизации товаров из магазина
 */
final class SyncProductsAction
{
    public function __construct(
        private readonly ProductSyncService $syncService,
    ) {}

    public function __invoke(Request $request, ?int $shopId = null): JsonResponse
    {
        $validated = $request->validate([
            'shop_id' => ['sometimes', 'integer', 'exists:shops,id'],
            'max_pages' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'use_queue' => ['sometimes', 'boolean'],
        ]);

        $shopId = $shopId ?? $validated['shop_id'] ?? null;
        $maxPages = $validated['max_pages'] ?? 10;
        $useQueue = $validated['use_queue'] ?? false;

        if ($shopId === null) {
            return response()->json([
                'error' => 'Shop ID is required',
            ], 400);
        }

        try {
            if ($useQueue) {
                SyncShopProductsJob::dispatch($shopId, $maxPages);

                return response()->json([
                    'message' => 'Product synchronization queued',
                    'shop_id' => $shopId,
                ]);
            }

            $syncedCount = $this->syncService->syncShopProducts($shopId, $maxPages);

            return response()->json([
                'message' => 'Products synchronized successfully',
                'shop_id' => $shopId,
                'synced_count' => $syncedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to sync products: '.$e->getMessage(),
            ], 500);
        }
    }
}
