<?php

declare(strict_types=1);

namespace App\Domains\Installment\Contracts;

use App\Domains\Installment\ValueObjects\InstallmentPlan;

interface StripeServiceInterface
{
    public function createPaymentIntent(float $amount, string $currency, array $metadata): string;

    public function createInstallmentPlan(InstallmentPlan $plan, int $userId): string;

    public function confirmPayment(string $paymentIntentId): bool;
}
