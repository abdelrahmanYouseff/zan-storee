<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimerSetting extends Model
{
    protected $fillable = [
        'is_active',
        'end_time',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'end_time' => 'datetime',
    ];

    /**
     * Get the current timer setting
     */
    public static function current()
    {
        return self::first() ?? self::create([
            'is_active' => true,
            'end_time' => now()->addHours(48),
        ]);
    }
}
