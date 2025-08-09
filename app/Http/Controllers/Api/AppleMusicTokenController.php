<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AppleMusicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AppleMusicTokenController extends Controller
{
    /**
     * Generate an Apple Music developer token.
     *
     * This endpoint creates a JWT token that can be used to authenticate
     * with the Apple Music API. The token uses the ES256 algorithm and
     * includes the configured key ID, team ID as issuer, and an expiration
     * time of 180 days (approximately 6 months).
     *
     * @return JsonResponse
     */
    public function generateDeveloperToken(): JsonResponse
    {
        try {
            // Generate the token using the AppleMusicService
            $token = AppleMusicService::generateToken();
            
            // Calculate expiration time for the response
            $expiresIn = 180 * 24 * 60 * 60; // 180 days in seconds
            $expiresAt = now()->addSeconds($expiresIn);
            
            Log::info('Apple Music developer token generated successfully for API request');
            
            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'expires_in' => $expiresIn,
                    'expires_at' => $expiresAt->toIso8601String(),
                    'token_type' => 'Bearer'
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Failed to generate Apple Music developer token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Apple Music developer token',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while generating the token'
            ], 500);
        }
    }
}