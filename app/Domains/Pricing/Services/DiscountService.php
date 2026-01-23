<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Services;

use App\Domains\Pricing\Contracts\DiscountRepositoryInterface;
use App\Domains\Pricing\ValueObjects\Discount;
use App\Domains\User\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Cache;

final class DiscountService
{
    public function __construct(
        private readonly DiscountRepositoryInterface $discountRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function getDiscountForUser(int $userId, string $shopDomain): Discount
    {
        // Кэшируем расчет скидки на 10 минут
        $cacheKey = "discount:user:{$userId}:shop:{$shopDomain}";
        
        return Cache::remember($cacheKey, 600, function () use ($userId, $shopDomain) {
            $user = $this->userRepository->findById($userId);
            
            if ($user === null) {
                throw new \RuntimeException("User with ID {$userId} not found");
            }

            // Кэшируем базовую скидку магазина на 1 час
            $baseDiscountCacheKey = "discount:base:shop:{$shopDomain}";
            $baseDiscount = Cache::remember($baseDiscountCacheKey, 3600, function () use ($shopDomain) {
                return $this->discountRepository->getBaseDiscount($shopDomain);
            });

            // Кэшируем персональную скидку уровня на 1 час
            $level = $user->getLevel()->value;
            $personalDiscountCacheKey = "discount:personal:level:{$level}";
            $personalDiscount = Cache::remember($personalDiscountCacheKey, 3600, function () use ($level) {
                return $this->discountRepository->getPersonalDiscount($level);
            });

            // Кэшируем лимиты на 24 часа (редко меняются)
            $limitsCacheKey = 'discount:limits';
            $limits = Cache::remember($limitsCacheKey, 86400, function () {
                return $this->discountRepository->getDiscountLimits();
            });

            return new Discount(
                baseDiscount: $baseDiscount,
                personalDiscount: $personalDiscount,
                minDiscount: $limits['min'],
                maxDiscount: $limits['max']
            );
        });
    }
}

