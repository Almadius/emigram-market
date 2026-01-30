<?php

declare(strict_types=1);

namespace App\Http\Actions\Delivery;

use App\Domains\Delivery\Enums\DeliveryProviderEnum;
use App\Domains\Delivery\Services\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

final readonly class TrackDeliveryAction
{
    public function __construct(
        private DeliveryService $deliveryService,
    ) {}

    public function execute(Request $request, string $provider, string $trackingNumber): JsonResponse
    {
        try {
            $providerEnum = DeliveryProviderEnum::from($provider);
            $tracking = $this->deliveryService->track($providerEnum, $trackingNumber);

            return Response::json($tracking);
        } catch (\ValueError $e) {
            return Response::json([
                'error' => 'BAD_REQUEST',
                'message' => "Invalid provider: {$provider}",
            ], 400);
        }
    }
}
