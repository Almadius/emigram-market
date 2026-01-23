<?php

declare(strict_types=1);

namespace App\Domains\Crawler\DTOs;

final readonly class CrawlResultDTO
{
    public function __construct(
        private bool $success,
        private ?float $price = null,
        private ?string $currency = null,
        private ?string $productName = null,
        private ?string $error = null,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}




