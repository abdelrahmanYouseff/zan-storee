<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VisitorController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search', '');
        $countryFilter = $request->get('country', '');

        $query = Visitor::query()->orderBy('created_at', 'desc');

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('page_visited', 'like', "%{$search}%");
            });
        }

        // Country filter
        if ($countryFilter) {
            $query->where('country', $countryFilter);
        }

        $visitors = $query->paginate($perPage);

        // Get statistics
        $stats = [
            'total_visitors' => Visitor::distinct('ip_address')->count(),
            'total_visits' => Visitor::count(),
            'today_visitors' => Visitor::whereDate('created_at', today())->distinct('ip_address')->count(),
            'today_visits' => Visitor::whereDate('created_at', today())->count(),
        ];

        // Get top countries
        $topCountries = Visitor::select('country', 'country_code')
            ->selectRaw('COUNT(*) as visits')
            ->groupBy('country', 'country_code')
            ->orderBy('visits', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Visitors', [
            'visitors' => $visitors,
            'stats' => $stats,
            'topCountries' => $topCountries,
            'filters' => [
                'search' => $search,
                'country' => $countryFilter,
                'per_page' => $perPage,
            ],
        ]);
    }
}
