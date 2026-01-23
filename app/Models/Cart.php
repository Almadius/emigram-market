<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Cart extends Model
{
    protected $fillable = [
        'user_id',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}




