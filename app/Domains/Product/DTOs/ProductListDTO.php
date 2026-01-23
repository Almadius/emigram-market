<?php

declare(strict_types=1);

namespace App\Domains\Product\DTOs;

final readonly class ProductListDTO
{
    /**
     * @param array<ProductDTO> $products
     */
    public function __construct(
        private array $products,
        private int $total,
        private int $page,
        private int $perPage,
    ) {
    }

    /**
     * @return array<ProductDTO>
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function toArray(): array
    {
        return [
            'data' => array_map(fn(ProductDTO $product) => $product->toArray(), $this->products),
            'total' => $this->total,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];
    }
}




