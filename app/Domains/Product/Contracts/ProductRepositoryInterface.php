<?php

declare(strict_types=1);

namespace App\Domains\Product\Contracts;

use App\Domains\Product\DTOs\ProductDTO;
use App\Domains\Product\DTOs\ProductListDTO;

interface ProductRepositoryInterface
{
    public function findById(int $productId): ?ProductDTO;

    /**
     * @param array<string, mixed> $filters
     */
    public function search(array $filters = [], int $page = 1, int $perPage = 20): ProductListDTO;

    /**
     * Search products by query string (for AI and Meilisearch)
     * @return array<ProductDTO>
     */
    public function searchByQuery(string $query, int $limit = 20): array;

    public function findByUrl(string $url): ?ProductDTO;

    public function create(ProductDTO $product): ProductDTO;

    public function update(ProductDTO $product): ProductDTO;
}

