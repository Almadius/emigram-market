<?php

declare(strict_types=1);

namespace App\Domains\Agent\DTOs;

/**
 * DTO для ответа от магазина при создании заказа
 */
final readonly class ShopOrderResponseDTO
{
    public function __construct(
        private string $shopOrderId, // ID заказа в магазине
        private string $status, // Статус заказа в магазине
        private ?string $trackingNumber = null,
        private ?string $trackingUrl = null,
        private array $metadata = [], // Дополнительные данные от магазина
    ) {}

    public function getShopOrderId(): string
    {
        return $this->shopOrderId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function getTrackingUrl(): ?string
    {
        return $this->trackingUrl;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'shop_order_id' => $this->shopOrderId,
            'status' => $this->status,
            'tracking_number' => $this->trackingNumber,
            'tracking_url' => $this->trackingUrl,
            'metadata' => $this->metadata,
        ];
    }
}
