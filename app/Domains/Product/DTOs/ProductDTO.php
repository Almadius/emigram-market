<?php

declare(strict_types=1);

namespace App\Domains\Product\DTOs;

final readonly class ProductDTO
{
    public function __construct(
        private int $id,
        private string $name,
        private string $url,
        private ?string $description = null,
        private ?string $imageUrl = null,
        private ?float $price = null,
        private string $currency = 'EUR',
        private ?int $shopId = null,
        private ?string $shopDomain = null,
    ) {
        if ($id < 0) {
            throw new \InvalidArgumentException('Product ID cannot be negative');
        }
        if (empty($name)) {
            throw new \InvalidArgumentException('Product name cannot be empty');
        }
        if (empty($url)) {
            throw new \InvalidArgumentException('Product URL cannot be empty');
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getShopId(): ?int
    {
        return $this->shopId;
    }

    public function getShopDomain(): ?string
    {
        return $this->shopDomain;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'description' => $this->description,
            'image_url' => $this->imageUrl,
            'price' => $this->price,
            'currency' => $this->currency,
            'shop_id' => $this->shopId,
            'shop_domain' => $this->shopDomain,
        ];
    }
}




