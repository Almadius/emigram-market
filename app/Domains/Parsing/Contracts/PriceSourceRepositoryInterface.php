<?php

declare(strict_types=1);

namespace App\Domains\Parsing\Contracts;

use App\Domains\Parsing\DTOs\ParsedPriceDTO;

interface PriceSourceRepositoryInterface
{
    /**
     * @return array<ParsedPriceDTO>
     */
    public function findByProduct(string $shopDomain, string $productUrl): array;

    public function save(ParsedPriceDTO $dto): void;
}





