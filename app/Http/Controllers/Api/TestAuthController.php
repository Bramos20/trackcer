<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TestAuthController extends Controller
{
    /**
     * Simple test authentication endpoint
     */
    public function simpleLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            // Try to find user by email
            $user = User::where('email', $request->email)->first();

            // If user doesn't exist, create one for testing
            if (!$user) {
                $user = User::create([
                    'name' => 'Test User',
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);
            }

            // Verify password
            if (!Hash::check($request->password, $user->password)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            // Create token
            $token = $user->createToken('test-app')->plainTextToken;

            Log::info('Token created for user: ' . $user->id);

            return response()->json([
                'user' => $user,
                'token' => $token,
                'message' => 'Login successful',
            ]);

        } catch (\Exception $e) {
            Log::error('Test auth error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Authentication failed',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Test token verification
     */
    public function testToken(Request $request)
    {
        return response()->json([
            'message' => 'Token is valid',
            'user' => $request->user(),
        ]);
    }
}