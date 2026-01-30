<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \App\Providers\AgentServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->statefulApi();

        // Мониторинг производительности для API запросов
        $middleware->api(prepend: [
            \App\Http\Middleware\PerformanceMonitoring::class,
        ]);

        // Настройка CORS для всех маршрутов
        $middleware->web(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Crawl prices every 30 minutes (configurable: 10-45 min)
        $crawlInterval = (int) config('crawler.interval_minutes', 30);

        $schedule->command('crawler:crawl-prices')
            ->cron("*/{$crawlInterval} * * * *")
            ->withoutOverlapping()
            ->runInBackground();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle API exceptions
        $exceptions->render(function (\App\Exceptions\ApiException $e, Request $request): ?Response {
            if (! $request->is('api/*')) {
                return null;
            }

            $response = [
                'error' => $e->getErrorCode(),
                'message' => $e->getMessage(),
            ];

            if ($e instanceof \App\Exceptions\ValidationException && $e->getErrors() !== []) {
                $response['errors'] = $e->getErrors();
            }

            return response()->json($response, $e->getStatusCode());
        });

        // Handle Laravel validation exceptions
        $exceptions->render(function (LaravelValidationException $e, Request $request): ?Response {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'error' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        });

        // Handle 404 for API
        $exceptions->render(function (NotFoundHttpException $e, Request $request): ?Response {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'error' => 'NOT_FOUND',
                'message' => 'Resource not found',
            ], 404);
        });

        // Handle authentication exceptions
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request): ?Response {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'error' => 'UNAUTHORIZED',
                'message' => 'Unauthenticated',
            ], 401);
        });
    })->create();
