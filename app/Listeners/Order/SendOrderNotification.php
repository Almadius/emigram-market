<?php

declare(strict_types=1);

namespace App\Listeners\Order;

use App\Domains\Order\Events\OrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class SendOrderNotification implements ShouldQueue
{
    public function handle(OrderCreated $event): void
    {
        Log::info('Order created', [
            'order_id' => $event->order->getId(),
            'user_id' => $event->order->getUserId(),
            'shop_domain' => $event->order->getShopDomain(),
            'total' => $event->order->getTotal(),
        ]);

        // Можно добавить отправку email/уведомления через Laravel Notifications
        // $user = User::find($event->order->getUserId());
        // $user->notify(new OrderCreatedNotification($event->order));
    }
}
