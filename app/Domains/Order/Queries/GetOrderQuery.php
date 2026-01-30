<?php

declare(strict_types=1);

namespace App\Domains\Order\Queries;

final readonly class GetOrderQuery
{
    public function __construct(
        private int $orderId,
        private ?int $userId = null,
    ) {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Order ID must be positive');
        }
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }
}
