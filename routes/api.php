<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Infrastructure\Parsing\Extension\Controllers\PriceResolveController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

// Публичные endpoints (без авторизации)
Route::get('/v1/products', [ProductController::class, 'index'])
    ->name('api.v1.products.index');
Route::get('/v1/products/{id}', [ProductController::class, 'show'])
    ->name('api.v1.products.show');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/v1/price/resolve', PriceResolveController::class)
        ->name('api.v1.price.resolve');

    Route::get('/v1/cart', [\App\Http\Controllers\Api\V1\CartController::class, 'show'])
        ->name('api.v1.cart.show');
    Route::post('/v1/cart/items', [\App\Http\Controllers\Api\V1\CartController::class, 'addItem'])
        ->name('api.v1.cart.add');
    Route::delete('/v1/cart/items/{productId}', [\App\Http\Controllers\Api\V1\CartController::class, 'removeItem'])
        ->name('api.v1.cart.remove');
    Route::put('/v1/cart/items/{productId}', [\App\Http\Controllers\Api\V1\CartController::class, 'updateItem'])
        ->name('api.v1.cart.update');
    Route::delete('/v1/cart', [\App\Http\Controllers\Api\V1\CartController::class, 'clear'])
        ->name('api.v1.cart.clear');

    Route::get('/v1/orders', [\App\Http\Controllers\Api\V1\OrderController::class, 'index'])
        ->name('api.v1.orders.index');
    Route::post('/v1/orders', [\App\Http\Controllers\Api\V1\OrderController::class, 'store'])
        ->name('api.v1.orders.store');
    Route::get('/v1/orders/{id}', [\App\Http\Controllers\Api\V1\OrderController::class, 'show'])
        ->name('api.v1.orders.show');

    Route::post('/v1/installments/calculate', [\App\Http\Controllers\Api\V1\InstallmentController::class, 'calculate'])
        ->name('api.v1.installments.calculate');

    Route::post('/v1/ai/chat', [\App\Http\Controllers\Api\V1\AIController::class, 'chat'])
        ->name('api.v1.ai.chat');
    Route::get('/v1/ai/products/{productId}/analogs', [\App\Http\Controllers\Api\V1\AIController::class, 'searchAnalogs'])
        ->name('api.v1.ai.analogs');

    Route::post('/v1/crawler/crawl', [\App\Http\Controllers\Api\V1\CrawlerController::class, 'crawl'])
        ->name('api.v1.crawler.crawl');

    Route::post('/v1/delivery/calculate', [\App\Http\Controllers\Api\V1\DeliveryController::class, 'calculate'])
        ->name('api.v1.delivery.calculate');
    Route::post('/v1/delivery/compare', [\App\Http\Controllers\Api\V1\DeliveryController::class, 'compare'])
        ->name('api.v1.delivery.compare');
    Route::get('/v1/delivery/track/{provider}/{trackingNumber}', [\App\Http\Controllers\Api\V1\DeliveryController::class, 'track'])
        ->name('api.v1.delivery.track');

    Route::post('/v1/shops/{shopId}/sync-products', \App\Http\Actions\Shop\SyncProductsAction::class)
        ->name('api.v1.shops.sync-products');

    Route::get('/v1/shops/{shopDomain}/parsing-config', \App\Http\Actions\Shop\GetParsingConfigAction::class)
        ->where('shopDomain', '[A-Za-z0-9\.\-]+')
        ->name('api.v1.shops.parsing-config');
});

// Webhook endpoints (без аутентификации, но с проверкой подписи)
Route::post('/v1/webhooks/shops/{shopDomain}/order-status', \App\Http\Actions\Shop\WebhookStatusAction::class)
    ->name('api.v1.webhooks.shop.order-status')
    ->middleware(\App\Http\Middleware\VerifyWebhookSignature::class);

// Metrics endpoint (требует аутентификации)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/v1/metrics', \App\Http\Actions\Metrics\GetMetricsAction::class)
        ->name('api.v1.metrics');
});
