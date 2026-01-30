<?php

declare(strict_types=1);

namespace App\Http\Actions\Shop;

use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Возвращает parsing config (CSS selectors) для конкретного магазина.
 *
 * Best practice:
 * - селекторы хранятся в БД (управляются через админку)
 * - клиенты (extension/webview/crawler) получают конфиг через API и кэшируют
 */
final class GetParsingConfigAction
{
    public function __invoke(Request $request, string $shopDomain): JsonResponse
    {
        $shopDomain = strtolower(trim($shopDomain));

        if (! $this->isValidDomain($shopDomain)) {
            return response()->json([
                'message' => 'Invalid shop domain',
            ], 422);
        }

        $cacheKey = "shops:parsing-config:{$shopDomain}";

        $payload = Cache::remember($cacheKey, 300, function () use ($shopDomain) {
            /** @var Shop|null $shop */
            $shop = Shop::query()
                ->where('domain', $shopDomain)
                ->first();

            $selectors = $shop?->parsing_selectors;
            if (! is_array($selectors) || empty($selectors)) {
                $selectors = config('crawler.default_selectors', []);
            }

            // Нормализуем формат: гарантируем массивы селекторов по ключам
            $selectors = $this->normalizeSelectors($selectors);

            $crawlIntervalMinutes = $shop?->crawl_interval_minutes;
            if (! is_int($crawlIntervalMinutes) || $crawlIntervalMinutes <= 0) {
                $crawlIntervalMinutes = (int) config('crawler.interval_minutes', 30);
            }

            return [
                'shop_domain' => $shopDomain,
                'known_shop' => $shop !== null,
                'selectors' => $selectors,
                'crawl_interval_minutes' => $crawlIntervalMinutes,
                'config_updated_at' => $shop?->updated_at?->toIso8601String(),
            ];
        });

        $etag = '"'.sha1(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)).'"';
        if ($request->headers->get('If-None-Match') === $etag) {
            return response()->json()->setNotModified();
        }

        return response()
            ->json($payload)
            ->header('ETag', $etag)
            ->header('Cache-Control', 'private, max-age=300');
    }

    private function isValidDomain(string $domain): bool
    {
        // Минимальная валидация: домен без схемы/пути, только hostname chars
        if ($domain === '' || strlen($domain) > 253) {
            return false;
        }

        return (bool) preg_match('/^[a-z0-9.-]+$/i', $domain);
    }

    /**
     * @return array{price: array<int, string>, currency: array<int, string>, name: array<int, string>}
     */
    private function normalizeSelectors(mixed $selectors): array
    {
        $result = [
            'price' => [],
            'currency' => [],
            'name' => [],
        ];

        if (! is_array($selectors)) {
            return $result;
        }

        foreach (['price', 'currency', 'name'] as $key) {
            $value = $selectors[$key] ?? [];
            if (is_string($value) && $value !== '') {
                $value = [$value];
            }
            if (! is_array($value)) {
                $value = [];
            }
            $value = array_values(array_filter(array_map(
                static fn ($v) => is_string($v) ? trim($v) : '',
                $value
            ), static fn (string $v) => $v !== ''));

            $result[$key] = $value;
        }

        return $result;
    }
}
