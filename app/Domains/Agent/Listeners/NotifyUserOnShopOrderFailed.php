<?php

declare(strict_types=1);

namespace App\Domains\Agent\Listeners;

use App\Domains\Agent\Events\ShopOrderFailed;
use App\Domains\User\Contracts\UserRepositoryInterface;
use App\Mail\ShopOrderFailedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Слушатель для уведомления пользователя об ошибке создания заказа в магазине
 */
final class NotifyUserOnShopOrderFailed implements ShouldQueue
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function handle(ShopOrderFailed $event): void
    {
        $order = $event->order;
        $user = $this->userRepository->findById($order->getUserId());

        if ($user === null) {
            Log::warning('NotifyUserOnShopOrderFailed: User not found', [
                'order_id' => $order->getId(),
                'user_id' => $order->getUserId(),
            ]);
            return;
        }

        Log::info('Sending email notification about shop order failure', [
            'order_id' => $order->getId(),
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail(),
            'error' => $event->error,
        ]);

        try {
            Mail::to($user->getEmail())->send(new \App\Mail\ShopOrderFailedMail($order, $event->error));
            
            Log::info('Email notification sent successfully', [
                'order_id' => $order->getId(),
                'user_email' => $user->getEmail(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'order_id' => $order->getId(),
                'user_email' => $user->getEmail(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}

