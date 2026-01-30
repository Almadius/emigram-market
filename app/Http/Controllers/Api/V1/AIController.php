<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Actions\AI\ChatAction;
use App\Http\Actions\AI\SearchAnalogsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AI\ChatRequest;
use App\Http\Requests\Api\V1\AI\SearchAnalogsRequest;
use Illuminate\Http\JsonResponse;

final class AIController extends Controller
{
    public function __construct(
        private readonly ChatAction $chatAction,
        private readonly SearchAnalogsAction $searchAnalogsAction,
    ) {}

    public function chat(ChatRequest $request): JsonResponse
    {
        return $this->chatAction->execute($request);
    }

    public function searchAnalogs(SearchAnalogsRequest $request, int $productId): JsonResponse
    {
        return $this->searchAnalogsAction->execute($request, $productId);
    }
}
