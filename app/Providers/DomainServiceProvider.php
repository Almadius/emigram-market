<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\AI\Contracts\AIServiceInterface;
use App\Domains\AI\Services\AIService;
use App\Domains\Crawler\Contracts\CrawlerServiceInterface;
use App\Domains\Crawler\Services\CrawlerService;
use App\Domains\Delivery\Contracts\DeliveryServiceInterface;
use App\Domains\Delivery\Enums\DeliveryProviderEnum;
use App\Domains\Delivery\Services\DeliveryService;
use App\Domains\Installment\Contracts\InstallmentRepositoryInterface;
use App\Domains\Installment\Contracts\StripeServiceInterface;
use App\Domains\Installment\Services\InstallmentService;
use App\Domains\Pricing\Contracts\DiscountRepositoryInterface;
use App\Domains\Pricing\Contracts\PriceCalculatorInterface;
use App\Domains\Pricing\Services\DiscountService;
use App\Domains\Pricing\Services\PriceCalculator;
use App\Domains\Pricing\Services\PriceService;
use App\Domains\Parsing\Contracts\PriceSourceRepositoryInterface;
use App\Domains\Cart\Contracts\CartRepositoryInterface;
use App\Domains\Order\Contracts\OrderRepositoryInterface;
use App\Domains\Product\Contracts\ProductRepositoryInterface;
use App\Domains\User\Contracts\UserRepositoryInterface;
use App\Infrastructure\Aimeos\Repositories\AimeosCartRepository;
use App\Infrastructure\Aimeos\Repositories\AimeosOrderRepository;
use App\Infrastructure\Aimeos\Repositories\AimeosProductRepository;
use App\Infrastructure\External\Stripe\StripeService;
use App\Infrastructure\Repositories\Eloquent\DiscountRepository;
use App\Infrastructure\Repositories\Eloquent\InstallmentRepository;
use App\Infrastructure\Repositories\Eloquent\PriceSourceRepository;
use App\Infrastructure\Repositories\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;
use Meilisearch\Client;
use Stripe\StripeClient;

final class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Contracts → Implementations
        $this->app->bind(
            PriceCalculatorInterface::class,
            PriceCalculator::class
        );

        $this->app->bind(
            DiscountRepositoryInterface::class,
            DiscountRepository::class
        );

        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        $this->app->bind(
            PriceSourceRepositoryInterface::class,
            PriceSourceRepository::class
        );

        // Используем Eloquent ProductRepository для MVP (независимо от Aimeos)
        $this->app->bind(
            ProductRepositoryInterface::class,
            \App\Infrastructure\Repositories\Eloquent\ProductRepository::class
        );

        $this->app->bind(
            CartRepositoryInterface::class,
            \App\Infrastructure\Repositories\Eloquent\CartRepository::class
        );

        $this->app->bind(
            OrderRepositoryInterface::class,
            \App\Infrastructure\Repositories\Eloquent\OrderRepository::class
        );

        // Installment domain
        $this->app->bind(
            InstallmentRepositoryInterface::class,
            InstallmentRepository::class
        );

        $this->app->bind(
            StripeServiceInterface::class,
            function ($app) {
                $stripeSecret = config('services.stripe.secret');
                if (empty($stripeSecret)) {
                    // Return a mock/null service if Stripe is not configured
                    return new \App\Infrastructure\External\Stripe\NullStripeService();
                }
                return new StripeService(
                    new StripeClient($stripeSecret)
                );
            }
        );

        $this->app->singleton(InstallmentService::class);

        // AI domain
        $this->app->bind(
            AIServiceInterface::class,
            AIService::class
        );

        // Crawler domain
        $this->app->bind(
            CrawlerServiceInterface::class,
            CrawlerService::class
        );

        // Meilisearch
        $this->app->singleton(Client::class, function ($app) {
            return new Client(
                config('meilisearch.host'),
                config('meilisearch.key')
            );
        });

        // Delivery domain
        $this->app->bind(
            DeliveryServiceInterface::class . '.' . DeliveryProviderEnum::DHL->value,
            function ($app) {
                $dhlKey = config('services.dhl.api_key');
                $dhlSecret = config('services.dhl.api_secret');
                
                if (empty($dhlKey) || empty($dhlSecret)) {
                    return new \App\Infrastructure\External\DHL\NullDHLService();
                }
                
                return new \App\Infrastructure\External\DHL\DHLService(
                    apiKey: $dhlKey,
                    apiSecret: $dhlSecret,
                    sandbox: config('services.dhl.sandbox', false)
                );
            }
        );

        $this->app->bind(
            DeliveryServiceInterface::class . '.' . DeliveryProviderEnum::UPS->value,
            function ($app) {
                $upsKey = config('services.ups.api_key');
                $upsSecret = config('services.ups.api_secret');
                
                if (empty($upsKey) || empty($upsSecret)) {
                    return new \App\Infrastructure\External\UPS\NullUPSService();
                }
                
                return new \App\Infrastructure\External\UPS\UPSService(
                    apiKey: $upsKey,
                    apiSecret: $upsSecret,
                    sandbox: config('services.ups.sandbox', false)
                );
            }
        );

        $this->app->singleton(DeliveryService::class, function ($app) {
            $providers = [
                DeliveryProviderEnum::DHL->value => $app->make(
                    DeliveryServiceInterface::class . '.' . DeliveryProviderEnum::DHL->value
                ),
                DeliveryProviderEnum::UPS->value => $app->make(
                    DeliveryServiceInterface::class . '.' . DeliveryProviderEnum::UPS->value
                ),
            ];

            return new DeliveryService($providers);
        });

        // Services (singleton для производительности)
        $this->app->singleton(PriceService::class);
        $this->app->singleton(DiscountService::class);
    }
}
