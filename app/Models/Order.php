<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'shop_id',
        'shop_domain',
        'status',
        'total',
        'currency',
        'shop_order_id',
        'metadata',
    ];

    protected $casts = [
        'shop_id' => 'integer',
        'total' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
