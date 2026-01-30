<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

final class ProductObserver
{
    public function saved(Product $product): void
    {
        $this->clearProductCache($product);
    }

    public function deleted(Product $product): void
    {
        $this->clearProductCache($product);
    }

    private function clearProductCache(Product $product): void
    {
        // Очистить кэш конкретного продукта
        Cache::forget("product:{$product->id}");

        // Очистить все кэши поиска продуктов
        Cache::flush();
    }
}
