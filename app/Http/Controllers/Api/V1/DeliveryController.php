<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Actions\Delivery\CalculateDeliveryAction;
use App\Http\Actions\Delivery\CompareDeliveryAction;
use App\Http\Actions\Delivery\TrackDeliveryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Delivery\CalculateDeliveryRequest;
use App\Http\Requests\Api\V1\Delivery\CompareDeliveryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeliveryController extends Controller
{
    public function __construct(
        private readonly CalculateDeliveryAction $calculateDeliveryAction,
        private readonly CompareDeliveryAction $compareDeliveryAction,
        private readonly TrackDeliveryAction $trackDeliveryAction,
    ) {
    }

    public function calculate(CalculateDeliveryRequest $request): JsonResponse
    {
        return $this->calculateDeliveryAction->execute($request);
    }

    public function compare(CompareDeliveryRequest $request): JsonResponse
    {
        return $this->compareDeliveryAction->execute($request);
    }

    public function track(Request $request, string $provider, string $trackingNumber): JsonResponse
    {
        return $this->trackDeliveryAction->execute($request, $provider, $trackingNumber);
    }
}
