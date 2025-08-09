<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceWwwMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only redirect in production or when APP_ENV is not local
        if (!app()->environment('local')) {
            $host = $request->getHost();

            // If the host is trackcer.com (without www), redirect to www
            if ($host === 'trackcer.app') {
                // Force HTTPS in production
                $url = 'https://www.trackcer.app' . $request->getRequestUri();

                return redirect($url, 301);
            }
        }

        return $next($request);
    }
}
