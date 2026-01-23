<?php

declare(strict_types=1);

namespace App\Domains\Crawler\Jobs;

use App\Domains\Crawler\Contracts\CrawlerServiceInterface;
use App\Domains\Crawler\DTOs\CrawlRequestDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class CrawlPriceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $url,
        private readonly string $shopDomain,
        private readonly array $selectors,
        private readonly ?string $proxy = null,
    ) {
    }

    public function handle(CrawlerServiceInterface $crawlerService): void
    {
        try {
            $request = new CrawlRequestDTO(
                url: $this->url,
                shopDomain: $this->shopDomain,
                selectors: $this->selectors,
                proxy: $this->proxy
            );

            $result = $crawlerService->crawl($request);

            if (!$result->isSuccess()) {
                Log::warning('Crawl job failed', [
                    'url' => $this->url,
                    'error' => $result->getError()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Crawl job exception', [
                'url' => $this->url,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}




