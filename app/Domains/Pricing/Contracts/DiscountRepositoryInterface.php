<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Contracts;

interface DiscountRepositoryInterface
{
    public function getBaseDiscount(string $shopDomain): float;

    public function getPersonalDiscount(int $userLevel): float;

    /**
     * @return array{min: float, max: float}
     */
    public function getDiscountLimits(): array;

    /**
     * @return array<string, mixed>
     */
    public function getRulesForUser(int $userId): array;
}





