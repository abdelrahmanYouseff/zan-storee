<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Calculate statistics
        $totalOrders = Order::count();
        $totalRevenue = Order::sum('total_amount');
        $recentOrders = Order::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Visitor statistics
        $totalVisitors = Visitor::getTotalVisitors();
        $totalVisits = Visitor::getTotalVisits();
        $todayVisitors = Visitor::getTodayVisitors();
        $todayVisits = Visitor::getTodayVisits();
        $countryStats = Visitor::getByCountry(5);
        $recentVisitors = Visitor::getRecent(10);

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalOrders' => $totalOrders,
                'totalRevenue' => $totalRevenue,
                'totalVisitors' => $totalVisitors,
                'totalVisits' => $totalVisits,
                'todayVisitors' => $todayVisitors,
                'todayVisits' => $todayVisits,
            ],
            'recentOrders' => $recentOrders,
            'countryStats' => $countryStats,
            'recentVisitors' => $recentVisitors,
        ]);
    }
}
