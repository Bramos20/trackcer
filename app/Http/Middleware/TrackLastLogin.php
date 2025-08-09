<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackLastLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Only update last_login_at if it's been more than 10 minutes
            if (!$user->last_login_at || now()->diffInMinutes($user->last_login_at) > 10) {
                $user->last_login_at = now();
                $user->save();
            }
        }

        return $next($request);
    }
}
