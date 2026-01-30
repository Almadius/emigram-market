<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Actions\Product\ListProductsAction;
use App\Http\Actions\Product\ShowProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Product\IndexProductRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ListProductsAction $listProductsAction,
        private readonly ShowProductAction $showProductAction,
    ) {}

    public function index(IndexProductRequest $request): JsonResponse
    {
        return $this->listProductsAction->execute($request);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->showProductAction->execute($request, $id);
    }
}
