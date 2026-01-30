<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Регистрируем MetricsService как singleton
        $this->app->singleton(\App\Services\MetricsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \App\Domains\Pricing\Events\PriceCalculated::class,
            \App\Listeners\Pricing\LogPriceCalculation::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Domains\Order\Events\OrderCreated::class,
            \App\Listeners\Order\SendOrderNotification::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Domains\Order\Events\OrderCreated::class,
            \App\Domains\User\Listeners\UpdateUserLevelOnOrderCreated::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Domains\Order\Events\OrderCreated::class,
            \App\Domains\Agent\Listeners\CreateShopOrderOnOrderCreated::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Domains\Order\Events\OrderStatusUpdated::class,
            \App\Listeners\Order\BroadcastOrderStatusUpdate::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Domains\Agent\Events\ShopOrderFailed::class,
            \App\Domains\Agent\Listeners\NotifyUserOnShopOrderFailed::class
        );
    }
}
