<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Agent\Services\AgentService;
use App\Domains\Agent\Services\ShopIntegrationFactory;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider для регистрации агентской модели
 */
final class AgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Регистрируем фабрику интеграций
        $this->app->singleton(ShopIntegrationFactory::class);

        // Регистрируем AgentService
        $this->app->singleton(AgentService::class);
    }

    public function boot(): void
    {
        //
    }
}

