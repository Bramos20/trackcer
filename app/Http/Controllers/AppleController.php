<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

class AppleController extends Controller
{
    /**
     * Redirect the user to the Apple authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToApple(Request $request)
    {
        $state = Str::random(40);
        session(['apple_auth_state' => $state]);

        $params = [
            'client_id' => 'com.trackcer.apple',
            'redirect_uri' => route('apple.callback'),
            'response_type' => 'code id_token',
            'state' => $state,
            'scope' => 'name email',
            'response_mode' => 'form_post'
        ];

        return redirect('https://appleid.apple.com/auth/authorize?' . http_build_query($params));
    }

    /**
     * Handle the callback from Apple.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function handleAppleCallback(Request $request)
    {
        try {
            $request->setLaravelSession($request->session());
            $request->session()->forget('_token');

            if ($request->has('error')) {
                Log::error('Apple sign in error: ' . $request->error);
                return redirect()->route('login')
                    ->with('error', 'Apple sign in failed: ' . $request->error);
            }

            $code = $request->input('code');
            $idToken = $request->input('id_token');
            $state = $request->input('state');
            $user_data = $request->input('user', '{}');
            $user_info = json_decode($user_data, true);

            Log::info('Apple callback received', [
                'code_exists' => !empty($code),
                'id_token_exists' => !empty($idToken),
                'state' => $state,
                'session_state' => session('apple_auth_state'),
                'user_data' => $user_data
            ]);

            $tokenParts = explode('.', $idToken);
            if (count($tokenParts) !== 3) {
                Log::error('Invalid token format from Apple');
                return redirect()->route('login')
                    ->with('error', 'Invalid authentication response');
            }

            $payload = json_decode(base64_decode(str_replace(
                ['-', '_'],
                ['+', '/'],
                $tokenParts[1]
            )), true);

            if (!$payload) {
                Log::error('Failed to decode Apple token payload');
                return redirect()->route('login')
                    ->with('error', 'Invalid authentication response');
            }

            $appleId = $payload['sub'] ?? null;
            $email = $payload['email'] ?? null;

            if (!$appleId) {
                Log::error('Missing user ID in Apple response');
                return redirect()->route('login')
                    ->with('error', 'Invalid user data received');
            }

            $firstName = isset($user_info['name']) ? ($user_info['name']['firstName'] ?? '') : '';
            $lastName = isset($user_info['name']) ? ($user_info['name']['lastName'] ?? '') : '';

            $existingUser = User::where('apple_id', $appleId)->first();

            if ($existingUser) {
                $user = $existingUser;
                $user->apple_token = $idToken;
                $user->save();
            } else {
                $user = User::where('email', $email)->first();

                if (!$user) {
                    $user = new User();
                    $user->email = $email;
                    $user->name = trim($firstName . ' ' . $lastName);
                    $user->password = bcrypt(Str::random(16));
                }

                $user->apple_id = $appleId;
                $user->apple_token = $idToken;
                $user->save();

                Log::info('New Apple user created', ['id' => $user->id, 'email' => $email]);
            }

            Auth::login($user);

            Log::info('User authenticated with Apple', [
                'user_id' => $user->id,
                'has_music_token' => !empty($user->apple_music_token)
            ]);

            if (empty($user->apple_music_token)) {
                Log::info('Redirecting to Apple Music auth page');
                return redirect()->route('apple-music.auth');
            }

            Log::info('Redirecting to dashboard');
            return redirect()->route('dashboard');

        } catch (\Exception $e) {
            Log::error('Apple sign in error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return redirect()->route('login')
                ->with('error', 'Failed to authenticate with Apple. Please try again or contact support. Error: ' . $e->getMessage());
        }
    }

    /**
     * Handle server-to-server Apple notifications
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleServerNotification(Request $request)
    {
        try {
            $payload = $request->all();
            Log::info('Apple notification received', $payload);

            if (isset($payload['type']) && $payload['type'] === 'revoked') {
                $events = $payload['events'] ?? [];

                foreach ($events as $event) {
                    if (isset($event['sub'])) {
                        $appleId = $event['sub'];
                        $user = User::where('apple_id', $appleId)->first();

                        if ($user) {
                            // Clear Apple credentials
                            $user->apple_id = null;
                            $user->apple_token = null;
                            $user->save();

                            Log::info("User {$user->id} Apple credentials revoked");
                        }
                    }
                }
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Apple notification error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Revoke Apple authorization
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function revokeAppleAuthorization(Request $request)
    {
        try {
            $user = Auth::user();

            if ($user && $user->apple_id) {

                $user->apple_id = null;
                $user->apple_token = null;
                $user->save();

                return redirect()->back()
                    ->with('success', 'Apple authorization has been revoked.');
            }

            return redirect()->back()
                ->with('error', 'No Apple authorization to revoke.');
        } catch (\Exception $e) {
            Log::error('Apple revocation error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to revoke Apple authorization.');
        }
    }
}
