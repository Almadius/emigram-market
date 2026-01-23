<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Installment;

use App\Domains\Installment\DTOs\CalculateInstallmentResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InstallmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CalculateInstallmentResponseDTO $response */
        $response = $this->resource;

        $plan = $response->getPlan();

        return [
            'approved' => $response->isApproved(),
            'plan' => $plan ? [
                'total_amount' => $plan->getTotalAmount(),
                'months' => $plan->getMonths(),
                'monthly_payment' => $plan->getMonthlyPayment(),
                'interest_rate' => $plan->getInterestRate(),
                'currency' => $plan->getCurrency(),
                'total_with_interest' => $plan->getTotalWithInterest(),
            ] : null,
            'limit' => [
                'max_amount' => $response->getLimit()->getMaxAmount(),
                'max_months' => $response->getLimit()->getMaxMonths(),
                'min_monthly_payment' => $response->getLimit()->getMinMonthlyPayment(),
                'currency' => $response->getLimit()->getCurrency(),
            ],
            'message' => $response->getMessage(),
        ];
    }
}
