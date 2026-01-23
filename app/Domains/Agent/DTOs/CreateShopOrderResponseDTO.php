<?php

declare(strict_types=1);

namespace App\Domains\Agent\DTOs;

/**
 * DTO для ответа при создании заказа в магазине
 */
final readonly class CreateShopOrderResponseDTO
{
    public function __construct(
        private bool $success,
        private ?string $shopOrderId = null,
        private ?string $shopOrderNumber = null,
        private ?string $trackingNumber = null,
        private ?string $status = null,
        private ?string $errorMessage = null,
        private array $metadata = [],
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getShopOrderId(): ?string
    {
        return $this->shopOrderId;
    }

    public function getShopOrderNumber(): ?string
    {
        return $this->shopOrderNumber;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public static function success(
        string $shopOrderId,
        ?string $shopOrderNumber = null,
        ?string $trackingNumber = null,
        ?string $status = null,
        array $metadata = [],
    ): self {
        return new self(
            success: true,
            shopOrderId: $shopOrderId,
            shopOrderNumber: $shopOrderNumber,
            trackingNumber: $trackingNumber,
            status: $status,
            metadata: $metadata,
        );
    }

    public static function failure(string $errorMessage, array $metadata = []): self
    {
        return new self(
            success: false,
            errorMessage: $errorMessage,
            metadata: $metadata,
        );
    }
}

