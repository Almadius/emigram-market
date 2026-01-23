<?php

declare(strict_types=1);

namespace App\Domains\Agent\DTOs;

use App\Domains\Order\DTOs\OrderDTO;

/**
 * DTO для запроса создания заказа в магазине
 */
final readonly class CreateShopOrderRequestDTO
{
    /**
     * @param array<array{product_id: int, product_url: string, quantity: int, price: float}> $items
     */
    public function __construct(
        private OrderDTO $emigramOrder,
        private string $shopDomain,
        private array $items,
        private string $shippingAddress,
        private string $customerName,
        private string $customerEmail,
        private ?string $customerPhone = null,
        private array $metadata = [],
    ) {
        if (empty($shopDomain)) {
            throw new \InvalidArgumentException('Shop domain cannot be empty');
        }
        if (empty($shippingAddress)) {
            throw new \InvalidArgumentException('Shipping address cannot be empty');
        }
        if (empty($customerName)) {
            throw new \InvalidArgumentException('Customer name cannot be empty');
        }
        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid customer email');
        }
    }

    public function getEmigramOrder(): OrderDTO
    {
        return $this->emigramOrder;
    }

    public function getShopDomain(): string
    {
        return $this->shopDomain;
    }

    /**
     * @return array<array{product_id: int, product_url: string, quantity: int, price: float}>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getShippingAddress(): string
    {
        return $this->shippingAddress;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}

