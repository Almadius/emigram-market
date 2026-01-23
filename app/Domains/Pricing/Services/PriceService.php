<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Services;

use App\Domains\Pricing\Contracts\DiscountRepositoryInterface;
use App\Domains\Pricing\Contracts\PriceCalculatorInterface;
use App\Domains\Pricing\DTOs\PriceResolveRequestDTO;
use App\Domains\Pricing\DTOs\PriceResolveResponseDTO;
use App\Domains\Pricing\Events\PriceCalculated;
use App\Services\MetricsService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Cache;

final class PriceService
{
    public function __construct(
        private readonly PriceCalculatorInterface $calculator,
        private readonly DiscountService $discountService,
        private readonly DiscountRepositoryInterface $discountRepository,
        private readonly Dispatcher $eventDispatcher,
        private readonly MetricsService $metrics,
    ) {
    }

    public function resolvePrice(PriceResolveRequestDTO $request): PriceResolveResponseDTO
    {
        // Кэшируем расчет цены на 5 минут (ключ включает все параметры)
        $cacheKey = sprintf(
            'price:resolve:%d:%s:%s:%.2f:%s',
            $request->getUserId(),
            $request->getShopDomain(),
            md5($request->getProductUrl()),
            $request->getStorePrice(),
            $request->getCurrency()
        );

        return Cache::remember($cacheKey, 300, function () use ($request) {
            // Получаем скидку для пользователя
            $discount = $this->discountService->getDiscountForUser(
                $request->getUserId(),
                $request->getShopDomain()
            );

            // Рассчитываем цену
            $price = $this->calculator->calculate(
                $request->getStorePrice(),
                $discount,
                $request->getCurrency()
            );

            // Получаем правила скидок для отображения (кэшируем отдельно)
            $rulesCacheKey = "discount_rules:user:{$request->getUserId()}";
            $rules = Cache::remember($rulesCacheKey, 600, function () use ($request) {
                return $this->discountRepository->getRulesForUser($request->getUserId());
            });

            // Записываем метрику
            $this->metrics->increment('price.resolves', 1, [
                'shop_domain' => $request->getShopDomain(),
            ]);

            // Отправляем событие
            $this->eventDispatcher->dispatch(
                new PriceCalculated(
                    $request->getUserId(),
                    $request->getProductUrl(),
                    $price
                )
            );

            return new PriceResolveResponseDTO($price, $rules);
        });
    }
}





