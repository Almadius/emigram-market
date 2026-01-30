<?php

declare(strict_types=1);

namespace App\Http\Actions\Product;

use App\Domains\Product\Services\ProductService;
use App\Http\Requests\Api\V1\Product\IndexProductRequest;
use Illuminate\Http\JsonResponse;

final readonly class ListProductsAction
{
    public function __construct(
        private ProductService $productService,
    ) {}

    public function execute(IndexProductRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $filters = $request->only(['search', 'shop_id']);
        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 20);
        $userId = $request->user()?->id;

        $list = $this->productService->search($filters, $page, $perPage, $userId);

        return response()->json($list);
    }
}
