<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain',
        'name',
        'base_discount',
        'is_active',
        'parsing_selectors',
        'crawl_interval_minutes',
    ];

    protected $casts = [
        'base_discount' => 'float',
        'is_active' => 'boolean',
        'parsing_selectors' => 'array',
        'crawl_interval_minutes' => 'integer',
    ];
}
