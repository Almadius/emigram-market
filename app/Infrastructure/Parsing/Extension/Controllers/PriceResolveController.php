<?php

declare(strict_types=1);

namespace App\Infrastructure\Parsing\Extension\Controllers;

use App\Domains\Parsing\Contracts\PriceSourceRepositoryInterface;
use App\Domains\Parsing\DTOs\ParsedPriceDTO;
use App\Domains\Parsing\Enums\PriceSourceEnum;
use App\Domains\Pricing\DTOs\PriceResolveRequestDTO;
use App\Domains\Pricing\Services\PriceService;
use App\Http\Controllers\Controller;
use App\Http\Requests\PriceResolveRequest;
use Illuminate\Http\JsonResponse;

final class PriceResolveController extends Controller
{
    public function __construct(
        private readonly PriceService $priceService,
        private readonly PriceSourceRepositoryInterface $priceSourceRepository,
    ) {
    }

    public function __invoke(PriceResolveRequest $request): JsonResponse
    {
        $storePrice = (float) $request->validated('price_store');
        $shopDomain = $request->validated('shop_domain');
        $productUrl = $request->validated('product_url');
        $currency = $request->validated('currency', 'EUR');

        // Определяем источник: WebView (mobile app) или Extension (browser)
        $userAgent = $request->header('User-Agent', '');
        $source = $this->detectSource($userAgent, $request->validated('source'));

        // Сохраняем сырую цену
        $parsedPrice = new ParsedPriceDTO(
            shopDomain: $shopDomain,
            productUrl: $productUrl,
            price: $storePrice,
            currency: $currency,
            source: $source,
            parsedAt: new \DateTimeImmutable()
        );
        $this->priceSourceRepository->save($parsedPrice);

        // Рассчитываем персональную цену
        $dto = new PriceResolveRequestDTO(
            userId: $request->user()->id,
            shopDomain: $shopDomain,
            productUrl: $productUrl,
            storePrice: $storePrice,
            currency: $currency,
            context: $request->validated('context', [])
        );

        $response = $this->priceService->resolvePrice($dto);

        return response()->json($response->toArray());
    }

    private function detectSource(string $userAgent, ?string $requestedSource): PriceSourceEnum
    {
        // Если источник явно указан в запросе
        if ($requestedSource !== null) {
            return match ($requestedSource) {
                'webview' => PriceSourceEnum::WEBVIEW,
                'extension' => PriceSourceEnum::EXTENSION,
                default => PriceSourceEnum::EXTENSION,
            };
        }

        // Определяем по User-Agent
        $userAgentLower = strtolower($userAgent);
        
        // Mobile WebView индикаторы
        if (str_contains($userAgentLower, 'wv') || 
            str_contains($userAgentLower, 'webview') ||
            str_contains($userAgentLower, 'mobile') && 
            (str_contains($userAgentLower, 'android') || str_contains($userAgentLower, 'iphone'))) {
            return PriceSourceEnum::WEBVIEW;
        }

        // По умолчанию Extension (browser extension)
        return PriceSourceEnum::EXTENSION;
    }
}
