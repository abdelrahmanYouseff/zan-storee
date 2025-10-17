<?php

namespace App\Http\Middleware;

use App\Models\Visitor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Jenssegers\Agent\Agent;

class TrackVisitor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip tracking for admin routes and API calls
        if ($request->is('dashboard*') || 
            $request->is('orders*') || 
            $request->is('chat*') || 
            $request->is('api/*') ||
            $request->is('login*') ||
            $request->is('register*')) {
            return $next($request);
        }

        try {
            $agent = new Agent();
            
            // Detect device type
            $deviceType = 'desktop';
            if ($agent->isMobile()) {
                $deviceType = 'mobile';
            } elseif ($agent->isTablet()) {
                $deviceType = 'tablet';
            }

            // Get browser name
            $browser = $agent->browser();

            // Track the visitor
            Visitor::create([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'country' => null, // Can be enhanced with GeoIP
                'city' => null,
                'page_url' => $request->fullUrl(),
                'referrer' => $request->header('referer'),
                'device_type' => $deviceType,
                'browser' => $browser,
            ]);
        } catch (\Exception $e) {
            // Don't break the app if tracking fails
            \Log::error('Visitor tracking failed: ' . $e->getMessage());
        }

        return $next($request);
    }
}
