<?php

declare(strict_types=1);

namespace App\Http\Actions\Crawler;

use App\Domains\Crawler\Contracts\CrawlerServiceInterface;
use App\Domains\Crawler\DTOs\CrawlRequestDTO;
use App\Domains\Crawler\Jobs\CrawlPriceJob;
use App\Http\Requests\Api\V1\Crawler\CrawlRequest;
use App\Http\Resources\Api\V1\Crawler\CrawlResultResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

final readonly class CrawlAction
{
    public function __construct(
        private CrawlerServiceInterface $crawlerService,
    ) {}

    public function execute(CrawlRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dto = new CrawlRequestDTO(
            url: $validated['url'],
            shopDomain: $validated['shop_domain'],
            selectors: $validated['selectors'],
            proxy: $validated['proxy'] ?? null
        );

        if ($validated['async'] ?? false) {
            CrawlPriceJob::dispatch(
                $dto->getUrl(),
                $dto->getShopDomain(),
                $dto->getSelectors(),
                $dto->getProxy()
            );

            return Response::json([
                'message' => 'Crawl job dispatched',
                'status' => 'queued',
            ]);
        }

        $result = $this->crawlerService->crawl($dto);

        if (! $result->isSuccess()) {
            return (new CrawlResultResource($result))->response()->setStatusCode(400);
        }

        return (new CrawlResultResource($result))->response();
    }
}
