<?php

namespace App\Models;

use App\Services\AutomaticCodeGenerator;
use MongoDB\Laravel\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'code',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            if ($product->code === null || $product->code === '') {
                $product->code = app(AutomaticCodeGenerator::class)->productCode();
            }
        });
    }
}
