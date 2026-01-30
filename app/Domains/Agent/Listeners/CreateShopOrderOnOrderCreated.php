<?php

declare(strict_types=1);

namespace App\Domains\Agent\Listeners;

use App\Domains\Agent\Jobs\CreateShopOrderJob;
use App\Domains\Order\Events\OrderCreated;
use App\Domains\User\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Слушатель для автоматического создания заказа в магазине
 * при создании заказа в EMIGRAM MARKET
 */
final class CreateShopOrderOnOrderCreated implements ShouldQueue
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        // Получаем данные пользователя
        $user = $this->userRepository->findById($order->getUserId());

        $customerData = [
            'customer_email' => $user?->getEmail() ?? '',
            'metadata' => [],
        ];

        // Отправляем в очередь для фонового создания заказа в магазине
        CreateShopOrderJob::dispatch(
            $order->getId(),
            $customerData
        );
    }
}
