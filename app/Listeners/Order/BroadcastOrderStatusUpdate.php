<?php

declare(strict_types=1);

namespace App\Listeners\Order;

use App\Domains\Order\Events\OrderStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;

final class BroadcastOrderStatusUpdate implements ShouldQueue
{
    public function handle(OrderStatusUpdated $event): void
    {
        // Event will be automatically broadcasted via ShouldBroadcast interface
    }
}




