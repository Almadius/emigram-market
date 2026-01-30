<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\AI;

use App\Domains\AI\DTOs\AIChatResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AIChatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var AIChatResponseDTO $response */
        $response = $this->resource;

        if ($response->hasError()) {
            return [
                'error' => $response->getError(),
            ];
        }

        return [
            'response' => $response->getResponse(),
            'suggested_products' => $response->getSuggestedProducts(),
        ];
    }

    public function withResponse(Request $request, $response): void
    {
        /** @var AIChatResponseDTO $dto */
        $dto = $this->resource;

        if ($dto->hasError()) {
            $response->setStatusCode(500);
        }
    }
}
