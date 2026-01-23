<?php

declare(strict_types=1);

namespace App\Domains\Order\Events;

use App\Domains\Order\Enums\OrderStatusEnum;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $orderId,
        public readonly OrderStatusEnum $status,
        public readonly int $userId,
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel("user.{$this->userId}");
    }

    public function broadcastAs(): string
    {
        return 'order.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'status' => $this->status->value,
        ];
    }
}




