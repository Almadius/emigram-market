<?php

declare(strict_types=1);

namespace App\Http\Actions\Product;

use App\Domains\Product\Services\ProductService;
use App\Exceptions\NotFoundException;
use App\Http\Resources\Api\V1\Product\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ShowProductAction
{
    public function __construct(
        private ProductService $productService,
    ) {}

    public function execute(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()?->id;
        $product = $this->productService->findById($id, $userId);

        if ($product === null) {
            throw new NotFoundException('Product not found');
        }

        return (new ProductResource($product))->response();
    }
}
