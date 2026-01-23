<?php

declare(strict_types=1);

namespace App\Http\Actions\Metrics;

use App\Services\MetricsService;
use Illuminate\Http\JsonResponse;

/**
 * Action для получения метрик приложения
 */
final class GetMetricsAction
{
    public function __construct(
        private readonly MetricsService $metrics,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        // В production можно добавить аутентификацию и авторизацию
        // Только для администраторов или через API ключ
        
        return response()->json([
            'metrics' => $this->metrics->getAll(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}









