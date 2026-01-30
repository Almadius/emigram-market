<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'product_name',
        'quantity',
        'price',
        'currency',
        'shop_id',
        'shop_domain',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'shop_id' => 'integer',
    ];
}
