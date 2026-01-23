<?php

declare(strict_types=1);

namespace App\Domains\Order\Events;

use App\Domains\Order\DTOs\OrderDTO;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly OrderDTO $order,
    ) {
    }
}




