<?php

declare(strict_types=1);

namespace App\Domains\Agent\DTOs;

/**
 * DTO для создания заказа в магазине
 */
final readonly class ShopOrderRequestDTO
{
    public function __construct(
        private int $emigramOrderId,
        private string $shopDomain,
        private array $items, // [['product_id' => int, 'quantity' => int, 'price' => float]]
        private array $shippingAddress, // ['name', 'street', 'city', 'postal_code', 'country']
        private array $billingAddress,
        private ?string $customerEmail = null,
        private ?string $customerPhone = null,
        private array $metadata = [], // Дополнительные данные
    ) {}

    public function getEmigramOrderId(): int
    {
        return $this->emigramOrderId;
    }

    public function getShopDomain(): string
    {
        return $this->shopDomain;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getShippingAddress(): array
    {
        return $this->shippingAddress;
    }

    public function getBillingAddress(): array
    {
        return $this->billingAddress;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'emigram_order_id' => $this->emigramOrderId,
            'shop_domain' => $this->shopDomain,
            'items' => $this->items,
            'shipping_address' => $this->shippingAddress,
            'billing_address' => $this->billingAddress,
            'customer_email' => $this->customerEmail,
            'customer_phone' => $this->customerPhone,
            'metadata' => $this->metadata,
        ];
    }
}
