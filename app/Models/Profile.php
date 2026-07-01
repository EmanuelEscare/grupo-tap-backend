<?php

namespace App\Models;

use App\Services\AutomaticCodeGenerator;
use MongoDB\Laravel\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'code',
        'name',
        'section_ids',
    ];

    protected static function booted(): void
    {
        static::creating(function (Profile $profile): void {
            if ($profile->code === null || $profile->code === '') {
                $profile->code = app(AutomaticCodeGenerator::class)->profileCode();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section_ids' => 'array',
        ];
    }
}
