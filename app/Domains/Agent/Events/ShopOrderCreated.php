<?php

declare(strict_types=1);

namespace App\Domains\Agent\Events;

use App\Domains\Agent\DTOs\CreateShopOrderResponseDTO;
use App\Domains\Order\DTOs\OrderDTO;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие создания заказа в магазине
 */
final class ShopOrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly OrderDTO $emigramOrder,
        public readonly CreateShopOrderResponseDTO $shopOrderResponse,
    ) {
    }
}

