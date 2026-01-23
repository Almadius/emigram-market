<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для проверки подписи webhook запросов от магазинов
 */
final class VerifyWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $shopDomain = $request->route('shopDomain');
        
        if ($shopDomain === null) {
            return response()->json(['error' => 'Shop domain required'], 400);
        }

        // Получаем секретный ключ для магазина
        $webhookSecret = $this->getWebhookSecret($shopDomain);

        if ($webhookSecret === null) {
            Log::warning('Webhook: No secret configured for shop', [
                'shop_domain' => $shopDomain,
            ]);

            // В development можно пропустить проверку
            if (app()->environment('local', 'testing')) {
                return $next($request);
            }

            return response()->json(['error' => 'Webhook not configured'], 403);
        }

        // Проверяем подпись
        $signature = $request->header('X-Webhook-Signature');
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        if (!hash_equals($expectedSignature, $signature ?? '')) {
            Log::warning('Webhook: Invalid signature', [
                'shop_domain' => $shopDomain,
                'provided' => $signature,
                'expected' => $expectedSignature,
            ]);

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        return $next($request);
    }

    /**
     * Получает секретный ключ для webhook магазина
     */
    private function getWebhookSecret(string $shopDomain): ?string
    {
        // Получаем из конфигурации или из БД (Shop model)
        return config("shops.{$shopDomain}.webhook_secret")
            ?? env("SHOP_{$shopDomain}_WEBHOOK_SECRET");
    }
}









