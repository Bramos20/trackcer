<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugAuthController extends Controller
{
    public function debugHeaders(Request $request)
    {
        $headers = $request->headers->all();
        $bearerToken = $request->bearerToken();
        $user = $request->user();
        
        Log::info('API Request Debug', [
            'headers' => $headers,
            'bearer_token' => $bearerToken ? 'Present' : 'Missing',
            'user' => $user ? $user->id : 'Not authenticated',
            'auth_guard' => auth()->guard()->name ?? 'unknown',
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);
        
        return response()->json([
            'auth_status' => $user ? 'authenticated' : 'unauthenticated',
            'user_id' => $user ? $user->id : null,
            'token_present' => $bearerToken ? true : false,
            'headers_received' => [
                'authorization' => $request->header('Authorization') ? 'Present' : 'Missing',
                'accept' => $request->header('Accept'),
                'x-requested-with' => $request->header('X-Requested-With'),
            ],
            'guard' => auth()->guard()->name ?? 'unknown',
        ]);
    }
}