<?php

declare(strict_types=1);

namespace App\Domains\AI\DTOs;

final readonly class AIChatRequestDTO
{
    public function __construct(
        private int $userId,
        private string $message,
        private ?string $context = null,
    ) {
        if ($this->userId <= 0) {
            throw new \InvalidArgumentException('User ID must be positive');
        }
        if (empty(trim($this->message))) {
            throw new \InvalidArgumentException('Message cannot be empty');
        }
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }
}
