<?php

declare(strict_types=1);

namespace App\Infrastructure\Search;

use App\Domains\Product\DTOs\ProductDTO;
use Meilisearch\Client;

final class MeilisearchService
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    public function indexProduct(ProductDTO $product): void
    {
        $index = $this->client->index('products');
        
        $index->addDocuments([
            [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription() ?? '',
                'shop_domain' => $product->getShopDomain(),
                'shop_id' => $product->getShopId(),
                'url' => $product->getUrl(),
                'image_url' => $product->getImageUrl() ?? '',
            ]
        ]);
    }

    public function search(string $query, int $limit = 20): array
    {
        $index = $this->client->index('products');
        
        $results = $index->search($query, [
            'limit' => $limit,
        ]);

        return $results->getHits();
    }

    public function deleteProduct(int $productId): void
    {
        $index = $this->client->index('products');
        $index->deleteDocument($productId);
    }
}




