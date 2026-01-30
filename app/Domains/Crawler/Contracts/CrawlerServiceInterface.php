<?php

declare(strict_types=1);

namespace App\Domains\Crawler\Contracts;

use App\Domains\Crawler\DTOs\CrawlRequestDTO;
use App\Domains\Crawler\DTOs\CrawlResultDTO;

interface CrawlerServiceInterface
{
    public function crawl(CrawlRequestDTO $request): CrawlResultDTO;
}
