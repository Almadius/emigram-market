<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Eloquent;

use App\Domains\Product\Contracts\ProductRepositoryInterface;
use App\Domains\Product\DTOs\ProductDTO;
use App\Domains\Product\DTOs\ProductListDTO;
use App\Models\Product;

final class ProductRepository implements ProductRepositoryInterface
{
    public function findById(int $productId): ?ProductDTO
    {
        $product = Product::with('shop')->find($productId);

        if ($product === null) {
            return null;
        }

        return $this->mapToDTO($product);
    }

    public function search(array $filters = [], int $page = 1, int $perPage = 20): ProductListDTO
    {
        $query = Product::with('shop')->where('is_active', true);

        // Применяем фильтры
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('description', 'like', '%'.$filters['search'].'%');
            });
        }

        if (isset($filters['shop_id'])) {
            $query->where('shop_id', $filters['shop_id']);
        }

        if (isset($filters['shop_domain'])) {
            $query->whereHas('shop', function ($q) use ($filters) {
                $q->where('domain', $filters['shop_domain']);
            });
        }

        $total = $query->count();
        $products = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(fn (Product $product) => $this->mapToDTO($product))
            ->toArray();

        return new ProductListDTO(
            products: $products,
            total: $total,
            page: $page,
            perPage: $perPage
        );
    }

    public function searchByQuery(string $query, int $limit = 20): array
    {
        $products = Product::with('shop')
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%'.$query.'%')
                    ->orWhere('description', 'like', '%'.$query.'%');
            })
            ->limit($limit)
            ->get()
            ->map(fn (Product $product) => $this->mapToDTO($product))
            ->toArray();

        return $products;
    }

    public function findByUrl(string $url): ?ProductDTO
    {
        $product = Product::with('shop')->where('url', $url)->first();

        if ($product === null) {
            return null;
        }

        return $this->mapToDTO($product);
    }

    public function create(ProductDTO $product): ProductDTO
    {
        $model = Product::create([
            'shop_id' => $product->getShopId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'url' => $product->getUrl(),
            'image_url' => $product->getImageUrl(),
            'price' => $product->getPrice(),
            'currency' => $product->getCurrency(),
            'is_active' => true,
        ]);

        return $this->mapToDTO($model->fresh(['shop']));
    }

    public function update(ProductDTO $product): ProductDTO
    {
        $model = Product::findOrFail($product->getId());

        $model->update([
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'url' => $product->getUrl(),
            'image_url' => $product->getImageUrl(),
            'price' => $product->getPrice(),
            'currency' => $product->getCurrency(),
        ]);

        return $this->mapToDTO($model->fresh(['shop']));
    }

    private function mapToDTO(Product $product): ProductDTO
    {
        return new ProductDTO(
            id: $product->id,
            name: $product->name,
            url: $product->url,
            description: $product->description,
            imageUrl: $product->image_url,
            price: $product->price !== null ? (float) $product->price : null,
            currency: $product->currency,
            shopId: $product->shop_id,
            shopDomain: $product->shop?->domain
        );
    }
}
