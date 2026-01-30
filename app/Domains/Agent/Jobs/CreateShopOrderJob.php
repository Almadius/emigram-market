<?php

declare(strict_types=1);

namespace App\Domains\Agent\Jobs;

use App\Domains\Agent\Events\ShopOrderFailed;
use App\Domains\Agent\Services\AgentService;
use App\Domains\Order\Contracts\OrderRepositoryInterface;
use App\Services\MetricsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * Job для фонового создания заказа в магазине
 */
final class CreateShopOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Количество попыток выполнения job
     */
    public int $tries = 3;

    /**
     * Время ожидания перед повторной попыткой (в секундах)
     */
    public int $backoff = 60;

    /**
     * Таймаут выполнения job (в секундах)
     */
    public int $timeout = 120;

    public function __construct(
        private readonly int $emigramOrderId,
        private readonly array $customerData,
    ) {}

    public function handle(
        AgentService $agentService,
        OrderRepositoryInterface $orderRepository,
        MetricsService $metrics,
    ): void {
        $order = $orderRepository->findById($this->emigramOrderId);

        if ($order === null) {
            Log::error('CreateShopOrderJob: Order not found', [
                'order_id' => $this->emigramOrderId,
            ]);

            return;
        }

        Log::info('CreateShopOrderJob: Creating order in shop', [
            'emigram_order_id' => $this->emigramOrderId,
            'shop_domain' => $order->getShopDomain(),
        ]);

        try {
            $startTime = microtime(true);
            $response = $agentService->createOrderInShop($this->emigramOrderId);
            $duration = (microtime(true) - $startTime) * 1000;

            Log::info('CreateShopOrderJob: Order created successfully in shop', [
                'emigram_order_id' => $this->emigramOrderId,
                'shop_order_id' => $response->getShopOrderId(),
                'shop_status' => $response->getStatus(),
                'tracking_number' => $response->getTrackingNumber(),
            ]);

            // Записываем метрики
            $metrics->increment('shop_orders.created', 1, [
                'shop_domain' => $order->getShopDomain(),
            ]);
            $metrics->recordTiming('shop_orders.create', $duration, [
                'shop_domain' => $order->getShopDomain(),
            ]);

            // shop_order_id уже сохранен в AgentService::updateOrderWithShopInfo()
        } catch (\App\Domains\Agent\Exceptions\ShopIntegrationException $e) {
            Log::error('CreateShopOrderJob: Failed to create order in shop', [
                'emigram_order_id' => $this->emigramOrderId,
                'error' => $e->getMessage(),
            ]);

            // Записываем метрику ошибки
            $metrics->increment('shop_orders.failed', 1, [
                'shop_domain' => $order->getShopDomain(),
            ]);

            // Отправляем событие для уведомления пользователя
            if ($order !== null) {
                Event::dispatch(new ShopOrderFailed($order, $e->getMessage()));
            }
        }
    }

    /**
     * Определяет, нужно ли повторять попытку при ошибке
     */
    public function shouldRetryUntil(): \DateTime
    {
        // Повторяем попытки в течение 1 часа
        return now()->addHour();
    }

    /**
     * Обработка неудачной попытки
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(5);
    }

    /**
     * Обработка окончательной неудачи после всех попыток
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CreateShopOrderJob: Job failed permanently after all retries', [
            'emigram_order_id' => $this->emigramOrderId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Отправляем событие об окончательной неудаче
        $orderRepository = app(\App\Domains\Order\Contracts\OrderRepositoryInterface::class);
        $order = $orderRepository->findById($this->emigramOrderId);

        if ($order !== null) {
            Event::dispatch(new \App\Domains\Agent\Events\ShopOrderFailed(
                $order,
                "Failed after {$this->attempts()} attempts: {$exception->getMessage()}"
            ));
        }
    }
}
