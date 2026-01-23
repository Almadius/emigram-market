<?php

declare(strict_types=1);

namespace App\Domains\Crawler\DTOs;

final readonly class CrawlRequestDTO
{
    public function __construct(
        private string $url,
        private string $shopDomain,
        private array $selectors,
        private ?string $proxy = null,
    ) {
        if (empty(trim($this->url))) {
            throw new \InvalidArgumentException('URL cannot be empty');
        }
        if (empty(trim($this->shopDomain))) {
            throw new \InvalidArgumentException('Shop domain cannot be empty');
        }
        if (empty($this->selectors)) {
            throw new \InvalidArgumentException('Selectors cannot be empty');
        }
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getShopDomain(): string
    {
        return $this->shopDomain;
    }

    public function getSelectors(): array
    {
        return $this->selectors;
    }

    public function getProxy(): ?string
    {
        return $this->proxy;
    }
}




