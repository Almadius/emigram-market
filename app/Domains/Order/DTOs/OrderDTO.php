<?php

declare(strict_types=1);

namespace App\Domains\Order\DTOs;

use App\Domains\Order\Enums\OrderStatusEnum;

final readonly class OrderDTO
{
    /**
     * @param  array<OrderItemDTO>  $items
     */
    public function __construct(
        private int $id,
        private int $userId,
        private int $shopId,
        private string $shopDomain,
        private OrderStatusEnum $status,
        private array $items,
        private float $total,
        private string $currency,
        private ?\DateTimeImmutable $createdAt = null,
        private ?string $shopOrderId = null,
        private array $metadata = [],
    ) {
        // ID может быть 0 для новых заказов (будет установлен репозиторием)
        if ($id < 0) {
            throw new \InvalidArgumentException('Order ID cannot be negative');
        }
        if ($userId <= 0) {
            throw new \InvalidArgumentException('User ID must be positive');
        }
        if ($total < 0) {
            throw new \InvalidArgumentException('Total cannot be negative');
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getShopId(): int
    {
        return $this->shopId;
    }

    public function getShopDomain(): string
    {
        return $this->shopDomain;
    }

    public function getStatus(): OrderStatusEnum
    {
        return $this->status;
    }

    /**
     * @return array<OrderItemDTO>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getShopOrderId(): ?string
    {
        return $this->shopOrderId;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
