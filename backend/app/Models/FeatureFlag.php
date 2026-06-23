<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    protected $fillable = [
        'name',
        'key',
        'is_enabled',
        'description',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    protected static function booted()
    {
        static::saved(function ($flag) {
            if ($flag->key === 'maintenance_mode') {
                \Illuminate\Support\Facades\Cache::forget('maintenance_mode_enabled');
            }
        });
    }
}
