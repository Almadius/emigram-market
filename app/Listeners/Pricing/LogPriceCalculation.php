<?php

declare(strict_types=1);

namespace App\Listeners\Pricing;

use App\Domains\Pricing\Events\PriceCalculated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class LogPriceCalculation implements ShouldQueue
{
    public function handle(PriceCalculated $event): void
    {
        Log::info('Price calculated', [
            'user_id' => $event->userId,
            'product_url' => $event->productUrl,
            'store_price' => $event->price->getStorePrice(),
            'emigram_price' => $event->price->getEmigramPrice(),
            'savings' => $event->price->getSavingsAbsolute(),
        ]);
    }
}
