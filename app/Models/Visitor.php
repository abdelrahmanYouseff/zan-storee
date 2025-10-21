<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    protected $fillable = [
        'ip_address',
        'country',
        'country_code',
        'city',
        'region',
        'user_agent',
        'page_visited',
        'referrer',
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
     * Get visitors by country
     */
    public static function getByCountry($limit = 10)
    {
        return self::select('country', 'country_code')
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull('country')
            ->groupBy('country', 'country_code')
            ->orderBy('count', 'desc')
            ->limit($limit)
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
