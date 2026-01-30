<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Eloquent;

use App\Domains\Parsing\Contracts\PriceSourceRepositoryInterface;
use App\Domains\Parsing\DTOs\ParsedPriceDTO;
use App\Domains\Parsing\Enums\PriceSourceEnum;
use App\Models\PriceSnapshot;

final class PriceSourceRepository implements PriceSourceRepositoryInterface
{
    /**
     * @return array<ParsedPriceDTO>
     */
    public function findByProduct(string $shopDomain, string $productUrl): array
    {
        $snapshots = PriceSnapshot::where('shop_domain', $shopDomain)
            ->where('product_url', $productUrl)
            ->orderBy('parsed_at', 'desc')
            ->get();

        $result = [];
        foreach ($snapshots as $snapshot) {
            $result[] = new ParsedPriceDTO(
                shopDomain: $snapshot->shop_domain,
                productUrl: $snapshot->product_url,
                price: (float) $snapshot->price,
                currency: $snapshot->currency,
                source: PriceSourceEnum::from($snapshot->source),
                parsedAt: \DateTimeImmutable::createFromMutable($snapshot->parsed_at)
            );
        }

        return $result;
    }

    public function save(ParsedPriceDTO $dto): void
    {
        PriceSnapshot::create([
            'shop_domain' => $dto->getShopDomain(),
            'product_url' => $dto->getProductUrl(),
            'price' => $dto->getPrice(),
            'currency' => $dto->getCurrency(),
            'source' => $dto->getSource()->value,
            'parsed_at' => $dto->getParsedAt(),
        ]);
    }
}
