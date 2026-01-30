<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\MetricsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для мониторинга производительности запросов
 */
final class PerformanceMonitoring
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $duration = ($endTime - $startTime) * 1000; // в миллисекундах
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // в МБ

        // Записываем метрики
        $metrics = app(MetricsService::class);
        $metrics->recordTiming('requests.duration', $duration, [
            'method' => $request->method(),
            'route' => $request->route()?->getName() ?? 'unknown',
        ]);

        // Логируем медленные запросы (>200мс как указано в ТЗ)
        if ($duration > 200) {
            $metrics->increment('requests.slow', 1);

            Log::warning('Slow request detected', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'duration_ms' => round($duration, 2),
                'memory_mb' => round($memoryUsed, 2),
                'user_id' => $request->user()?->id,
            ]);
        }

        // Добавляем заголовки для мониторинга
        $response->headers->set('X-Response-Time', round($duration, 2).'ms');
        $response->headers->set('X-Memory-Used', round($memoryUsed, 2).'MB');

        return $response;
    }
}
