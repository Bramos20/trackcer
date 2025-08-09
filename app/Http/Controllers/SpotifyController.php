<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\ListeningHistory;
use App\Models\User;
use App\Models\Producer;
use App\Models\Genre;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Jobs\FetchListeningHistoryJob;
use App\Jobs\FetchProducersJob;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SpotifyController extends Controller
{
    public function redirectToSpotify(Request $request)
    {
        $clientId = env('SPOTIFY_CLIENT_ID');
        $redirectUri = urlencode(env('SPOTIFY_REDIRECT_URI'));
        $scopes = urlencode('user-read-email user-read-private user-read-recently-played playlist-modify-public playlist-modify-private');

        $state = Str::random(40);
        session(['spotify_auth_state' => $state]);

        $url = "https://accounts.spotify.com/authorize?response_type=code&client_id=$clientId&redirect_uri=$redirectUri&scope=$scopes&state=$state";

        return redirect($url);
    }


    public function handleSpotifyCallback(Request $request)
    {
        Log::info('Spotify callback started', ['request_params' => $request->all()]);

        try {
            $code = $request->get('code');
            if (!$code) {
                Log::error('No authorization code provided');
                return redirect('/')->with('error', 'No authorization code provided');
            }

            $clientId = env('SPOTIFY_CLIENT_ID');
            $clientSecret = env('SPOTIFY_CLIENT_SECRET');
            $redirectUri = env('SPOTIFY_REDIRECT_URI');

            $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);

            if ($response->failed()) {
                Log::error('Token request failed', ['response' => $response->body()]);
                return redirect('/')->with('error', 'Failed to get access token');
            }

            $data = $response->json();
            $accessToken = $data['access_token'];
            $refreshToken = $data['refresh_token'];

            $userResponse = Http::withToken($accessToken)->get('https://api.spotify.com/v1/me');

            if ($userResponse->failed()) {
                Log::error('User profile request failed', ['response' => $userResponse->body()]);
                return redirect('/')->with('error', 'Failed to get user profile');
            }

            $userData = $userResponse->json();
            $spotifyId = $userData['id'];

            // Extract profile image
            $profileImage = $userData['images'][0]['url'] ?? null;

            // Check if user already exists
            $existingUser = User::where('spotify_id', $spotifyId)->first();

            if ($existingUser) {
                $existingUser->name = $userData['display_name'] ?? 'Spotify User';
                $existingUser->email = $userData['email'] ?? $existingUser->email;
                $existingUser->spotify_token = $accessToken;
                $existingUser->spotify_refresh_token = $refreshToken;

                // Only update profile image if user has not uploaded their own
                if (!$existingUser->custom_profile_image && $profileImage) {
                    $existingUser->profile_image = $profileImage;
                }

                $existingUser->save();
                $authUser = $existingUser;
            } else {
                // First time login — no need to check for custom image
                $authUser = User::create([
                    'spotify_id' => $spotifyId,
                    'name' => $userData['display_name'] ?? 'Spotify User',
                    'email' => $userData['email'] ?? null,
                    'spotify_token' => $accessToken,
                    'spotify_refresh_token' => $refreshToken,
                    'profile_image' => $profileImage,
                    'custom_profile_image' => false, // default false
                ]);
            }

            Auth::login($authUser);

            Log::info('Authentication successful, redirecting to dashboard');
            return redirect()->route('dashboard');

        } catch (\Exception $e) {
            Log::error('Exception in Spotify callback', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            if (Auth::check()) {
                Log::info('Exception occurred but user is authenticated, redirecting to dashboard');
                return redirect()->route('dashboard');
            }

            return redirect('/')->with('error', 'An unexpected error occurred during Spotify authentication');
        }
        
    }

    public function fetchListeningHistory()
    {
        try {
            $user = Auth::user();

            // Dispatch the job with the user
            FetchListeningHistoryJob::dispatch($user);

            return response()->json([
                'message' => 'Listening history fetch started',
                'services' => [
                    'spotify' => !empty($user->spotify_token),
                    'apple_music' => !empty($user->apple_music_token)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error starting listening history fetch:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }

    public function allTracks(Request $request)
    {
        $user = Auth::user();
        $searchQuery = $request->get('search', '');

        $tracks = ListeningHistory::with(['producers', 'genres'])
            ->where('user_id', $user->id)
            ->when($searchQuery, function ($query) use ($searchQuery) {
                $query->where(function ($q) use ($searchQuery) {
                    $q->where('track_name', 'like', "%{$searchQuery}%")
                    ->orWhere('artist_name', 'like', "%{$searchQuery}%")
                    ->orWhere('album_name', 'like', "%{$searchQuery}%");
                });
            })
            ->orderBy('played_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Aggregated graph data — number of tracks per day (last 30 days)
        $listensPerDay = ListeningHistory::selectRaw('DATE(played_at) as date, COUNT(*) as count')
            ->where('user_id', $user->id)
            ->groupBy(DB::raw('DATE(played_at)'))
            ->orderBy('date')
            ->get();

        return Inertia::render('Tracks', [
            'tracks' => [
                'data' => $tracks->items(),
                'meta' => [
                    'current_page' => $tracks->currentPage(),
                    'last_page' => $tracks->lastPage(),
                    'per_page' => $tracks->perPage(),
                    'total' => $tracks->total(),
                    'links' => $tracks->linkCollection()->toArray(),
                ],
            ],
            'searchQuery' => $searchQuery,
            'listensPerDay' => $listensPerDay,
        ]);
    }



    public function fetchProducersForTracks()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'User is not authenticated'], 401);
        }

        FetchProducersJob::dispatch($user);
        //return redirect()->route('dashboard')->with('success', 'Producers are being fetched in the background.');
        return response()->json(['success' => 'Producers are being fetched in the background']);
    }



    private function refreshSpotifyToken($user)
    {
        $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $user->spotify_refresh_token,
            'client_id' => env('SPOTIFY_CLIENT_ID'),
            'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
        ]);

        $data = $response->json();

        if (isset($data['access_token'])) {
            $user->update(['spotify_token' => $data['access_token']]);
        } else {
            Log::error('Failed to refresh Spotify token:', $data);
        }
    }

}