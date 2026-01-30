<?php

declare(strict_types=1);

namespace App\Domains\Agent\Events;

use App\Domains\Order\DTOs\OrderDTO;

/**
 * Событие при неудачном создании заказа в магазине
 */
final class ShopOrderFailed
{
    public function __construct(
        public readonly OrderDTO $order,
        public readonly string $error,
    ) {}
}
