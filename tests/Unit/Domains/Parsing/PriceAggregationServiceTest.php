<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Parsing;

use App\Domains\Parsing\Contracts\PriceSourceRepositoryInterface;
use App\Domains\Parsing\DTOs\ParsedPriceDTO;
use App\Domains\Parsing\Enums\PriceSourceEnum;
use App\Domains\Parsing\Services\PriceAggregationService;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

final class PriceAggregationServiceTest extends TestCase
{
    use WithFaker;

    public function testPrefersFreshExtensionOverStaleCrawlerEvenIfCrawlerIsCheaper(): void
    {
        $productUrl = 'https://example.com/p/' . uniqid('p1-', true);
        $repo = new class implements PriceSourceRepositoryInterface {
            public function findByProduct(string $shopDomain, string $productUrl): array
            {
                return [
                    new ParsedPriceDTO(
                        shopDomain: $shopDomain,
                        productUrl: $productUrl,
                        price: 100.0,
                        currency: 'EUR',
                        source: PriceSourceEnum::EXTENSION,
                        parsedAt: new \DateTimeImmutable('-5 minutes')
                    ),
                    new ParsedPriceDTO(
                        shopDomain: $shopDomain,
                        productUrl: $productUrl,
                        price: 80.0,
                        currency: 'EUR',
                        source: PriceSourceEnum::CRAWLER,
                        parsedAt: new \DateTimeImmutable('-20 hours')
                    ),
                ];
            }

            public function save(ParsedPriceDTO $dto): void
            {
                // not needed
            }
        };

        $service = new PriceAggregationService($repo);
        $best = $service->aggregateBestPrice('example.com', $productUrl);

        $this->assertNotNull($best);
        $this->assertSame(PriceSourceEnum::EXTENSION, $best->getSource());
        $this->assertSame(100.0, $best->getPrice());
    }

    public function testPenalizesExtremeOutlierPrice(): void
    {
        $productUrl = 'https://example.com/p/' . uniqid('p2-', true);
        $repo = new class implements PriceSourceRepositoryInterface {
            public function findByProduct(string $shopDomain, string $productUrl): array
            {
                return [
                    new ParsedPriceDTO($shopDomain, $productUrl, 199.0, 'EUR', PriceSourceEnum::EXTENSION, new \DateTimeImmutable('-10 minutes')),
                    new ParsedPriceDTO($shopDomain, $productUrl, 205.0, 'EUR', PriceSourceEnum::WEBVIEW, new \DateTimeImmutable('-30 minutes')),
                    // outlier (ошибка парсинга, например "19.90" прочитали как "1.99")
                    new ParsedPriceDTO($shopDomain, $productUrl, 1.99, 'EUR', PriceSourceEnum::EXTENSION, new \DateTimeImmutable('-2 minutes')),
                ];
            }

            public function save(ParsedPriceDTO $dto): void
            {
            }
        };

        $service = new PriceAggregationService($repo);
        $best = $service->aggregateBestPrice('example.com', $productUrl);

        $this->assertNotNull($best);
        $this->assertGreaterThan(50, $best->getPrice());
        $this->assertSame('EUR', $best->getCurrency());
    }

    public function testPrefersDominantCurrencyGroup(): void
    {
        $productUrl = 'https://example.com/p/' . uniqid('p3-', true);
        $repo = new class implements PriceSourceRepositoryInterface {
            public function findByProduct(string $shopDomain, string $productUrl): array
            {
                return [
                    new ParsedPriceDTO($shopDomain, $productUrl, 100.0, 'EUR', PriceSourceEnum::EXTENSION, new \DateTimeImmutable('-10 minutes')),
                    new ParsedPriceDTO($shopDomain, $productUrl, 102.0, 'EUR', PriceSourceEnum::CRAWLER, new \DateTimeImmutable('-1 hour')),
                    new ParsedPriceDTO($shopDomain, $productUrl, 120.0, 'USD', PriceSourceEnum::WEBVIEW, new \DateTimeImmutable('-5 minutes')),
                ];
            }

            public function save(ParsedPriceDTO $dto): void
            {
            }
        };

        $service = new PriceAggregationService($repo);
        $best = $service->aggregateBestPrice('example.com', $productUrl);

        $this->assertNotNull($best);
        $this->assertSame('EUR', $best->getCurrency());
    }
}


