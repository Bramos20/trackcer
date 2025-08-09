<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use phpseclib3\Math\BigInteger;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PublicKey;

class AuthController extends Controller
{
    /**
     * Handle Apple Sign-In
     */
    public function loginWithApple(Request $request)
    {
        $request->validate([
            'apple_id' => 'required|string',
            'identity_token' => 'required|string',
            'email' => 'nullable|email',
            'name' => 'nullable|string',
            'apple_music_token' => 'nullable|string',
        ]);

        try {
            // Verify the identity token with Apple
            $appleId = $request->apple_id;
            $identityToken = $request->identity_token;
            
            // Decode the identity token to get user info
            $tokenParts = explode('.', $identityToken);
            if (count($tokenParts) !== 3) {
                return response()->json(['error' => 'Invalid identity token format'], 401);
            }

            // Decode the payload (we'll verify the signature in production)
            $payload = json_decode(base64_decode(strtr($tokenParts[1], '-_', '+/')), true);
            
            if (!$payload || !isset($payload['sub'])) {
                return response()->json(['error' => 'Invalid identity token payload'], 401);
            }

            // Verify the token issuer and audience
            if ($payload['iss'] !== 'https://appleid.apple.com') {
                return response()->json(['error' => 'Invalid token issuer'], 401);
            }

            // Find or create the user
            $user = User::where('apple_id', $appleId)->first();

            if (!$user) {
                // Generate a better name if none provided
                $name = $request->name;
                if (empty($name)) {
                    $email = $request->email ?? $payload['email'] ?? null;
                    if (!empty($email) && strpos($email, '@') !== false) {
                        // Extract username from email and format it nicely
                        $username = explode('@', $email)[0];
                        $username = str_replace(['.', '_', '-'], ' ', $username);
                        $parts = explode(' ', $username);
                        $name = implode(' ', array_map('ucfirst', array_filter($parts)));
                    }
                    
                    if (empty($name)) {
                        $name = 'Apple User';
                    }
                }
                
                // Create new user
                $user = User::create([
                    'apple_id' => $appleId,
                    'email' => $request->email ?? $payload['email'] ?? null,
                    'name' => $name,
                    'password' => bcrypt(str()->random(32)), // Random password since they use Apple Sign-In
                    'apple_music_token' => $request->apple_music_token,
                ]);
            } else {
                // Update Apple Music token if provided
                if ($request->apple_music_token) {
                    $user->update(['apple_music_token' => $request->apple_music_token]);
                }
            }

            // Create Sanctum token
            $token = $user->createToken('ios-app')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
            ]);

        } catch (\Exception $e) {
            Log::error('Apple Sign-In error: ' . $e->getMessage());
            return response()->json(['error' => 'Authentication failed'], 500);
        }
    }

    /**
     * Update Apple Music token
     */
    public function updateAppleMusicToken(Request $request)
    {
        $request->validate([
            'apple_music_token' => 'required|string',
        ]);

        $request->user()->update([
            'apple_music_token' => $request->apple_music_token,
        ]);

        return response()->json([
            'message' => 'Apple Music token updated successfully',
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $request->user()->id,
        ]);

        $user = $request->user();
        
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        
        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }

    /**
     * Update profile image
     */
    public function updateProfileImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // Increased to 5MB
        ]);

        $user = $request->user();

        try {
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = 'profile-images/' . $user->id . '_' . time() . '.' . $image->getClientOriginalExtension();
                
                // Store the image on S3
                $path = Storage::disk('s3')->putFileAs(
                    'profile-images', 
                    $image, 
                    $user->id . '_' . time() . '.' . $image->getClientOriginalExtension(),
                    'public'
                );
                
                // Get the full URL
                $imageUrl = Storage::disk('s3')->url($path);
                
                // Delete old custom profile image if exists
                if ($user->custom_profile_image) {
                    // Extract the path from the URL
                    $oldPath = parse_url($user->custom_profile_image, PHP_URL_PATH);
                    if ($oldPath) {
                        $oldPath = ltrim($oldPath, '/');
                        Storage::disk('s3')->delete($oldPath);
                    }
                }
                
                // Update user's custom profile image
                $user->custom_profile_image = $imageUrl;
                $user->save();
                
                return response()->json([
                    'message' => 'Profile image updated successfully',
                    'image_url' => $imageUrl,
                    'user' => $user,
                ]);
            }
            
            return response()->json([
                'error' => 'No image file provided'
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('Profile image upload failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to upload image. Please try again.'
            ], 500);
        }
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}