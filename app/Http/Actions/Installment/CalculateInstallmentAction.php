<?php

declare(strict_types=1);

namespace App\Http\Actions\Installment;

use App\Domains\Installment\DTOs\CalculateInstallmentRequestDTO;
use App\Domains\Installment\Services\InstallmentService;
use App\Http\Requests\Api\V1\Installment\CalculateInstallmentRequest;
use App\Http\Resources\Api\V1\Installment\InstallmentResource;
use Illuminate\Http\JsonResponse;

final readonly class CalculateInstallmentAction
{
    public function __construct(
        private InstallmentService $installmentService,
    ) {
    }

    public function execute(CalculateInstallmentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dto = new CalculateInstallmentRequestDTO(
            userId: $request->user()->id,
            amount: (float) $validated['amount'],
            requestedMonths: (int) $validated['months'],
            currency: $validated['currency'] ?? 'EUR'
        );

        $response = $this->installmentService->calculateInstallment($dto);

        return (new InstallmentResource($response))->response();
    }
}


