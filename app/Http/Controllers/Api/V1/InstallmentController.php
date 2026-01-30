<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Actions\Installment\CalculateInstallmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Installment\CalculateInstallmentRequest;
use Illuminate\Http\JsonResponse;

final class InstallmentController extends Controller
{
    public function __construct(
        private readonly CalculateInstallmentAction $calculateInstallmentAction,
    ) {}

    public function calculate(CalculateInstallmentRequest $request): JsonResponse
    {
        return $this->calculateInstallmentAction->execute($request);
    }
}
