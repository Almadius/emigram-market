<?php

declare(strict_types=1);

namespace App\Http\Actions\AI;

use App\Domains\AI\Contracts\AIServiceInterface;
use App\Domains\AI\DTOs\AIChatRequestDTO;
use App\Http\Requests\Api\V1\AI\ChatRequest;
use App\Http\Resources\Api\V1\AI\AIChatResource;
use Illuminate\Http\JsonResponse;

final readonly class ChatAction
{
    public function __construct(
        private AIServiceInterface $aiService,
    ) {
    }

    public function execute(ChatRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dto = new AIChatRequestDTO(
            userId: $request->user()->id,
            message: $validated['message'],
            context: $validated['context'] ?? null
        );

        $response = $this->aiService->chat($dto);

        return (new AIChatResource($response))->response();
    }
}


