<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для сбора базовых метрик приложения
 */
final class MetricsService
{
    private const METRICS_PREFIX = 'metrics:';

    /**
     * Увеличивает счетчик метрики
     */
    public function increment(string $metric, int $value = 1, array $tags = []): void
    {
        $key = $this->buildKey($metric, $tags);
        Cache::increment($key, $value);

        // Устанавливаем TTL 24 часа для метрик
        Cache::put($key, Cache::get($key, 0), 86400);
    }

    /**
     * Устанавливает значение метрики
     */
    public function set(string $metric, float $value, array $tags = []): void
    {
        $key = $this->buildKey($metric, $tags);
        Cache::put($key, $value, 86400);
    }

    /**
     * Записывает время выполнения операции
     */
    public function recordTiming(string $metric, float $milliseconds, array $tags = []): void
    {
        $this->set("{$metric}.duration", $milliseconds, $tags);
        $this->increment("{$metric}.count", 1, $tags);
    }

    /**
     * Получает значение метрики
     */
    public function get(string $metric, array $tags = []): float
    {
        $key = $this->buildKey($metric, $tags);

        return (float) Cache::get($key, 0);
    }

    /**
     * Получает все метрики
     */
    public function getAll(): array
    {
        // В реальной реализации можно использовать Redis SCAN или хранить список метрик
        return [
            'orders_created' => $this->get('orders.created'),
            'orders_failed' => $this->get('orders.failed'),
            'shop_orders_created' => $this->get('shop_orders.created'),
            'shop_orders_failed' => $this->get('shop_orders.failed'),
            'products_synced' => $this->get('products.synced'),
            'price_resolves' => $this->get('price.resolves'),
            'slow_requests' => $this->get('requests.slow'),
        ];
    }

    /**
     * Сбрасывает метрики
     */
    public function reset(): void
    {
        // В реальной реализации нужно удалить все ключи метрик
        Log::info('Metrics reset requested');
    }

    /**
     * Строит ключ для метрики
     */
    private function buildKey(string $metric, array $tags): string
    {
        $tagString = empty($tags) ? '' : ':'.md5(json_encode($tags));

        return self::METRICS_PREFIX.$metric.$tagString;
    }
}
