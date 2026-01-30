<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'price',
        'currency',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'quantity' => 'integer',
        'price' => 'decimal:2',
    ];
}
