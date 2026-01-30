<?php

declare(strict_types=1);

namespace App\Infrastructure\External\Stripe;

use App\Domains\Installment\Contracts\StripeServiceInterface;
use App\Domains\Installment\ValueObjects\InstallmentPlan;
use Stripe\StripeClient;

final class StripeService implements StripeServiceInterface
{
    public function __construct(
        private readonly StripeClient $stripe,
    ) {}

    public function createPaymentIntent(float $amount, string $currency, array $metadata): string
    {
        $intent = $this->stripe->paymentIntents->create([
            'amount' => (int) ($amount * 100), // Stripe uses cents
            'currency' => strtolower($currency),
            'metadata' => $metadata,
        ]);

        return $intent->id;
    }

    public function createInstallmentPlan(InstallmentPlan $plan, int $userId): string
    {
        // Create a subscription or payment plan in Stripe
        $product = $this->stripe->products->create([
            'name' => "Installment Plan for User {$userId}",
        ]);

        $price = $this->stripe->prices->create([
            'product' => $product->id,
            'unit_amount' => (int) ($plan->getMonthlyPayment() * 100),
            'currency' => strtolower($plan->getCurrency()),
            'recurring' => [
                'interval' => 'month',
                'interval_count' => 1,
            ],
        ]);

        return $price->id;
    }

    public function confirmPayment(string $paymentIntentId): bool
    {
        try {
            $intent = $this->stripe->paymentIntents->retrieve($paymentIntentId);

            return $intent->status === 'succeeded';
        } catch (\Exception $e) {
            return false;
        }
    }
}
