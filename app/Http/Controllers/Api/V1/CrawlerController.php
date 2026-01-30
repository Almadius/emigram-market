<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Actions\Crawler\CrawlAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Crawler\CrawlRequest;
use Illuminate\Http\JsonResponse;

final class CrawlerController extends Controller
{
    public function __construct(
        private readonly CrawlAction $crawlAction,
    ) {}

    public function crawl(CrawlRequest $request): JsonResponse
    {
        return $this->crawlAction->execute($request);
    }
}
