<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Shop;
use Illuminate\Support\Facades\Cache;

final class ShopObserver
{
    public function saved(Shop $shop): void
    {
        // При изменении магазина очищаем все кэши продуктов
        Cache::flush();
    }

    public function deleted(Shop $shop): void
    {
        // При удалении магазина очищаем все кэши продуктов
        Cache::flush();
    }
}
