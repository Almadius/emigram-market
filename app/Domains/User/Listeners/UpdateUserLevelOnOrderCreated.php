<?php

declare(strict_types=1);

namespace App\Domains\User\Listeners;

use App\Domains\Order\Events\OrderCreated;
use App\Domains\User\Services\UserLevelService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Слушатель события создания заказа
 * Автоматически пересчитывает уровень пользователя после создания заказа
 */
final class UpdateUserLevelOnOrderCreated implements ShouldQueue
{
    public function __construct(
        private readonly UserLevelService $userLevelService,
    ) {}

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;
        $this->userLevelService->updateUserLevel($order->getUserId());
    }
}
