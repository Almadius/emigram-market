<?php

declare(strict_types=1);

namespace App\Domains\AI\DTOs;

final readonly class AIChatResponseDTO
{
    public function __construct(
        private string $response,
        private array $suggestedProducts = [],
        private ?string $error = null,
    ) {}

    public function getResponse(): string
    {
        return $this->response;
    }

    public function getSuggestedProducts(): array
    {
        return $this->suggestedProducts;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }
}
