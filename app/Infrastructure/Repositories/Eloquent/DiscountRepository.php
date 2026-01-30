<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Eloquent;

use App\Domains\Pricing\Contracts\DiscountRepositoryInterface;
use App\Models\DiscountRule;
use App\Models\Shop;

final class DiscountRepository implements DiscountRepositoryInterface
{
    public function getBaseDiscount(string $shopDomain): float
    {
        $shop = Shop::where('domain', $shopDomain)->first();

        $discount = $shop?->base_discount ?? config('pricing.discount.base', 5.0);

        return (float) $discount;
    }

    public function getPersonalDiscount(int $userLevel): float
    {
        $rule = DiscountRule::where('user_level', $userLevel)->first();

        if ($rule !== null) {
            return (float) $rule->discount;
        }

        // Дефолтные значения если нет в БД
        return match ($userLevel) {
            1 => 0.0,  // Bronze
            2 => 2.0,  // Silver
            3 => 5.0,  // Gold
            4 => 8.0,  // Platinum
            5 => 10.0, // Diamond
            default => 0.0,
        };
    }

    public function getDiscountLimits(): array
    {
        return [
            'min' => config('pricing.discount.min', 0.0),
            'max' => config('pricing.discount.max', 50.0),
        ];
    }

    public function getRulesForUser(int $userId): array
    {
        $user = \App\Models\User::find($userId);

        if ($user === null) {
            return [];
        }

        $userLevel = $user->level ?? 1;
        $personalRule = DiscountRule::where('user_level', $userLevel)->first();

        $rules = [
            'user_level' => $userLevel,
            'personal_discount' => $personalRule ? (float) $personalRule->discount : 0.0,
            'base_discount' => config('pricing.discount.base', 5.0),
            'limits' => $this->getDiscountLimits(),
        ];

        return $rules;
    }
}
