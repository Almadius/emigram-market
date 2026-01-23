<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Crawler;

use App\Domains\Crawler\DTOs\CrawlResultDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CrawlResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CrawlResultDTO $result */
        $result = $this->resource;

        if (!$result->isSuccess()) {
            return [
                'success' => false,
                'error' => $result->getError(),
            ];
        }

        return [
            'success' => true,
            'price' => $result->getPrice(),
            'currency' => $result->getCurrency(),
            'product_name' => $result->getProductName(),
        ];
    }
}
