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
            $ip = $request->ip();

            // Get geolocation data
            $geoData = $this->getGeoLocation($ip);

            // Track the visitor
            Visitor::create([
                'ip_address' => $ip,
                'country' => $geoData['country'] ?? null,
                'country_code' => $geoData['country_code'] ?? null,
                'city' => $geoData['city'] ?? null,
                'region' => $geoData['region'] ?? null,
                'user_agent' => $request->userAgent(),
                'page_visited' => $request->fullUrl(),
                'referrer' => $request->header('referer'),
            ]);
        } catch (\Exception $e) {
            // Don't break the app if tracking fails
            \Log::error('Visitor tracking failed: ' . $e->getMessage());
        }

        return $next($request);
    }

    /**
     * Get geolocation data from IP address
     */
    private function getGeoLocation($ip)
    {
        // Skip for local IPs
        if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.')) {
            return [
                'country' => 'Local',
                'country_code' => 'LC',
                'city' => 'Localhost',
                'region' => 'Local',
            ];
        }

        try {
            // Use free ip-api.com service
            $response = file_get_contents("http://ip-api.com/json/{$ip}");
            $data = json_decode($response, true);

            if ($data && $data['status'] === 'success') {
                return [
                    'country' => $data['country'] ?? null,
                    'country_code' => $data['countryCode'] ?? null,
                    'city' => $data['city'] ?? null,
                    'region' => $data['regionName'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            \Log::error('GeoIP lookup failed: ' . $e->getMessage());
        }

        return [
            'country' => 'Unknown',
            'country_code' => null,
            'city' => null,
            'region' => null,
        ];
    }
}
