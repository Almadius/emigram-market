<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class DiscountRule extends Model
{
    protected $fillable = [
        'user_level',
        'discount',
    ];

    protected $casts = [
        'user_level' => 'integer',
        'discount' => 'decimal:2',
    ];
}





