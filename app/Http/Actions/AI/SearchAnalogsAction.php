<?php

declare(strict_types=1);

namespace App\Http\Actions\AI;

use App\Domains\AI\Contracts\AIServiceInterface;
use App\Domains\AI\DTOs\SearchAnalogRequestDTO;
use App\Http\Requests\Api\V1\AI\SearchAnalogsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class SearchAnalogsAction
{
    public function __construct(
        private AIServiceInterface $aiService,
    ) {
    }

    public function execute(SearchAnalogsRequest $request, int $productId): JsonResponse
    {
        $validated = $request->validated();

        $dto = new SearchAnalogRequestDTO(
            productId: $productId,
            maxPrice: isset($validated['max_price']) ? (float) $validated['max_price'] : null
        );

        $analogs = $this->aiService->searchAnalogs($dto);

        return response()->json([
            'analogs' => $analogs,
        ]);
    }
}


