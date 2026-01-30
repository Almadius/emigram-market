<?php

declare(strict_types=1);

namespace App\Filament\Resources\DiscountRuleResource\Pages;

use App\Filament\Resources\DiscountRuleResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateDiscountRule extends CreateRecord
{
    protected static string $resource = DiscountRuleResource::class;
}
