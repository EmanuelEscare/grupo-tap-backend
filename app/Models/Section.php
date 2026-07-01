<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Section extends Model
{
    protected $fillable = [
        'code',
        'name',
        'key',
    ];
}
