<?php

namespace App\Http\Middleware;

use App\Models\Eventner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveEventnerSubdomain
{
    /**
     * Extract subdomain from host, resolve eventner, bind to container.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $rootDomain = parse_url(config('app.url'), PHP_URL_HOST);

        // Same as root domain — skip
        if ($host === $rootDomain) {
            return $next($request);
        }

        // Extract subdomain: "kejurcabcianjur.berbaris.local" → "kejurcabcianjur"
        $subdomain = str_replace('.' . $rootDomain, '', $host);

        // If no subdomain, skip
        if ($subdomain === $host || empty($subdomain)) {
            return $next($request);
        }

        $eventner = Eventner::where('subdomain', $subdomain)->first();

        if (!$eventner) {
            abort(404, 'Event tidak ditemukan.');
        }

        // Bind to container for components to pick up
        app()->instance('current_eventner', $eventner);

        // Share with views
        view()->share('subdomainEventner', $eventner);

        return $next($request);
    }
}
