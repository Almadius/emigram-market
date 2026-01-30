<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Shop\Jobs\SyncShopProductsJob;
use App\Models\Shop;
use Illuminate\Console\Command;

/**
 * Команда для синхронизации товаров из магазинов
 */
final class SyncShopProductsCommand extends Command
{
    protected $signature = 'shop:sync-products 
                            {--shop-id= : ID магазина для синхронизации}
                            {--shop-domain= : Домен магазина для синхронизации}
                            {--all : Синхронизировать все активные магазины}
                            {--max-pages=10 : Максимальное количество страниц для синхронизации}
                            {--queue : Запустить синхронизацию в очереди}';

    protected $description = 'Синхронизирует товары из магазинов';

    public function handle(): int
    {
        $shopId = $this->option('shop-id');
        $shopDomain = $this->option('shop-domain');
        $all = $this->option('all');
        $maxPages = (int) $this->option('max-pages');
        $useQueue = $this->option('queue');

        if ($all) {
            return $this->syncAllShops($maxPages, $useQueue);
        }

        if ($shopId !== null) {
            return $this->syncShopById((int) $shopId, $maxPages, $useQueue);
        }

        if ($shopDomain !== null) {
            return $this->syncShopByDomain($shopDomain, $maxPages, $useQueue);
        }

        $this->error('Необходимо указать --shop-id, --shop-domain или --all');

        return Command::FAILURE;
    }

    private function syncAllShops(int $maxPages, bool $useQueue): int
    {
        $shops = Shop::where('is_active', true)->get();

        if ($shops->isEmpty()) {
            $this->warn('Нет активных магазинов для синхронизации');

            return Command::SUCCESS;
        }

        $this->info("Найдено {$shops->count()} активных магазинов");

        foreach ($shops as $shop) {
            if ($useQueue) {
                SyncShopProductsJob::dispatch($shop->id, $maxPages);
                $this->info("Добавлен в очередь: {$shop->name} (ID: {$shop->id})");
            } else {
                $this->syncShop($shop, $maxPages);
            }
        }

        return Command::SUCCESS;
    }

    private function syncShopById(int $shopId, int $maxPages, bool $useQueue): int
    {
        $shop = Shop::find($shopId);

        if ($shop === null) {
            $this->error("Магазин с ID {$shopId} не найден");

            return Command::FAILURE;
        }

        if ($useQueue) {
            SyncShopProductsJob::dispatch($shopId, $maxPages);
            $this->info("Добавлен в очередь: {$shop->name} (ID: {$shopId})");

            return Command::SUCCESS;
        }

        return $this->syncShop($shop, $maxPages) ? Command::SUCCESS : Command::FAILURE;
    }

    private function syncShopByDomain(string $shopDomain, int $maxPages, bool $useQueue): int
    {
        $shop = Shop::where('domain', $shopDomain)->first();

        if ($shop === null) {
            $this->error("Магазин с доменом {$shopDomain} не найден");

            return Command::FAILURE;
        }

        if ($useQueue) {
            SyncShopProductsJob::dispatch($shop->id, $maxPages);
            $this->info("Добавлен в очередь: {$shop->name} (ID: {$shop->id})");

            return Command::SUCCESS;
        }

        return $this->syncShop($shop, $maxPages) ? Command::SUCCESS : Command::FAILURE;
    }

    private function syncShop(Shop $shop, int $maxPages): bool
    {
        $this->info("Синхронизация товаров из магазина: {$shop->name} (ID: {$shop->id})");

        try {
            $syncService = app(\App\Domains\Shop\Services\ProductSyncService::class);
            $syncedCount = $syncService->syncShopProducts($shop->id, $maxPages);

            $this->info("Синхронизировано товаров: {$syncedCount}");

            return true;
        } catch (\Exception $e) {
            $this->error("Ошибка синхронизации: {$e->getMessage()}");

            return false;
        }
    }
}
