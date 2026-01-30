<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PriceSnapshot extends Model
{
    protected $fillable = [
        'shop_domain',
        'product_url',
        'price',
        'currency',
        'source',
        'parsed_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'parsed_at' => 'datetime',
    ];
}
