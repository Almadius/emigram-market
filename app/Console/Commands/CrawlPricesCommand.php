<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Crawler\Jobs\CrawlPriceJob;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class CrawlPricesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawler:crawl-prices 
                            {--shop= : Specific shop domain to crawl}
                            {--limit= : Limit number of products to crawl}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl prices for active products from active shops';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting price crawler...');

        $shops = $this->getShopsToCrawl();

        if ($shops->isEmpty()) {
            $this->warn('No active shops found to crawl.');

            return self::FAILURE;
        }

        $totalJobs = 0;

        foreach ($shops as $shop) {
            $this->info("Processing shop: {$shop->domain}");

            $products = $this->getProductsForShop($shop);

            if ($products->isEmpty()) {
                $this->warn("  No active products found for {$shop->domain}");

                continue;
            }

            $selectors = $this->getSelectorsForShop($shop);

            if (empty($selectors)) {
                $this->warn("  No parsing selectors configured for {$shop->domain}");

                continue;
            }

            foreach ($products as $product) {
                CrawlPriceJob::dispatch(
                    url: $product->url,
                    shopDomain: $shop->domain,
                    selectors: $selectors,
                    proxy: null // Can be configured per shop later
                );

                $totalJobs++;
            }

            $this->info("  Queued {$products->count()} products for {$shop->domain}");
        }

        $this->info("Total jobs queued: {$totalJobs}");
        Log::info('Price crawler completed', ['jobs_queued' => $totalJobs]);

        return self::SUCCESS;
    }

    private function getShopsToCrawl()
    {
        $query = Shop::where('is_active', true);

        if ($this->option('shop')) {
            $query->where('domain', $this->option('shop'));
        }

        return $query->get();
    }

    private function getProductsForShop(Shop $shop)
    {
        $query = Product::where('shop_id', $shop->id)
            ->where('is_active', true)
            ->whereNotNull('url');

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        return $query->get();
    }

    private function getSelectorsForShop(Shop $shop): array
    {
        $selectors = $shop->parsing_selectors ?? config('crawler.default_selectors', []);

        if (empty($selectors)) {
            // Default selectors as fallback
            return [
                'price' => ['.price', '[data-price]', '.product-price'],
                'currency' => ['.currency', '[data-currency]'],
                'name' => ['h1', '.product-title', '[data-product-name]'],
            ];
        }

        return is_string($selectors) ? json_decode($selectors, true) : $selectors;
    }
}
