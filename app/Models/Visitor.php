<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    protected $fillable = [
        'ip_address',
        'user_agent',
        'country',
        'city',
        'page_url',
        'referrer',
        'device_type',
        'browser',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get total visitors count
     */
    public static function getTotalVisitors()
    {
        return self::distinct('ip_address')->count('ip_address');
    }

    /**
     * Get total visits count
     */
    public static function getTotalVisits()
    {
        return self::count();
    }

    /**
     * Get today's visitors
     */
    public static function getTodayVisitors()
    {
        return self::whereDate('created_at', today())
            ->distinct('ip_address')
            ->count('ip_address');
    }

    /**
     * Get today's visits
     */
    public static function getTodayVisits()
    {
        return self::whereDate('created_at', today())->count();
    }

    /**
     * Get visitors by device type
     */
    public static function getByDeviceType()
    {
        return self::select('device_type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('device_type')
            ->get();
    }

    /**
     * Get recent visitors
     */
    public static function getRecent($limit = 10)
    {
        return self::latest()->limit($limit)->get();
    }
}
