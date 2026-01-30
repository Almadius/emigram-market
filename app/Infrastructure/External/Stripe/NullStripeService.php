<?php

declare(strict_types=1);

namespace App\Infrastructure\External\Stripe;

use App\Domains\Installment\Contracts\StripeServiceInterface;
use App\Domains\Installment\ValueObjects\InstallmentPlan;

final class NullStripeService implements StripeServiceInterface
{
    public function createPaymentIntent(float $amount, string $currency, array $metadata): string
    {
        // Return a mock payment intent ID for development
        return 'pi_mock_'.uniqid();
    }

    public function createInstallmentPlan(InstallmentPlan $plan, int $userId): string
    {
        // Return a mock plan ID for development
        return 'plan_mock_'.uniqid();
    }

    public function confirmPayment(string $paymentIntentId): bool
    {
        // Mock confirmation for development
        return true;
    }
}
