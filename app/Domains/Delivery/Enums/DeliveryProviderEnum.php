<?php

declare(strict_types=1);

namespace App\Domains\Delivery\Enums;

enum DeliveryProviderEnum: string
{
    case DHL = 'dhl';
    case UPS = 'ups';
}


