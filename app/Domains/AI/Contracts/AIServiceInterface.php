<?php

declare(strict_types=1);

namespace App\Domains\AI\Contracts;

use App\Domains\AI\DTOs\AIChatRequestDTO;
use App\Domains\AI\DTOs\AIChatResponseDTO;
use App\Domains\AI\DTOs\SearchAnalogRequestDTO;

interface AIServiceInterface
{
    public function chat(AIChatRequestDTO $request): AIChatResponseDTO;

    public function searchAnalogs(SearchAnalogRequestDTO $request): array;
}
