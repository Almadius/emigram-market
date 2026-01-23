<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Events;

use App\Domains\Pricing\ValueObjects\Price;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PriceCalculated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $productUrl,
        public readonly Price $price,
    ) {
    }
}





