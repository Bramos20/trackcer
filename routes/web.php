<?php

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SpotifyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AppleMusicController;
use App\Http\Controllers\AppleController;
use App\Services\AppleMusicService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ProducerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\DataUpdateController;
use App\Http\Controllers\AppleMusicMobileAuthController;
use App\Http\Controllers\SearchController;
use Inertia\Inertia;


Route::get('/artist/{artistName}/producer/{producer}/tracks', [App\Http\Controllers\ArtistController::class, 'showProducerTracks'])
     ->name('artist.producer.tracks');
Route::get('/artist/{artistName}/collaborations', [ArtistController::class, 'showCollaborations'])
    ->middleware(['auth'])
    ->name('artist.collaborations');
Route::get('/artists', [ArtistController::class, 'showAllArtists'])->name('artists');
Route::get('/artists/{artist_name}', [ArtistController::class, 'show'])->name('artist.show');

Route::middleware('auth')->group(function () {
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/reset-image', [ProfileController::class, 'resetImageToMusicService'])->name('profile.reset.image');
});


// Basic Routes
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('LandingPage');
});

// Authentication Routes (only declare once)
Auth::routes();
Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

// Spotify Authentication Routes
Route::prefix('auth')->group(function () {
    Route::get('/spotify', [SpotifyController::class, 'redirectToSpotify'])->name('auth.spotify');
});

// Apple Sign In initialization route
Route::get('login/apple', [AppleController::class, 'redirectToApple'])->name('login.apple');
Route::post('login/apple/revoke', [AppleController::class, 'revokeAppleAuthorization'])->name('apple.revoke');

Route::middleware(['auth'])->group(function () {
    Route::get('/apple-music/auth', function () {
        return Inertia::render('AppleMusicAuth');
    })->name('apple-music.auth');

    Route::post('/apple-music/connect', function (Illuminate\Http\Request $request) {
        $request->validate([
            'music_user_token' => 'required|string',

        ]);

        // Get the current authenticated user
        $user = Auth::user();

        // Save the Apple Music token
        $user->apple_music_token = $request->music_user_token;


       $user->save();
        // Redirect to dashboard with success message
        return redirect()->route('dashboard')
            ->with('success', 'Apple Music successfully connected!');
    })->name('apple-music.connect');
});

// All callback routes grouped under 'callback' prefix
Route::prefix('callback')->group(function () {
    // Spotify callback
    Route::get('/spotify', [SpotifyController::class, 'handleSpotifyCallback'])->name('callback.spotify');

    // Apple Sign In callback routes - support both GET and POST
    Route::match(['get', 'post'], '/apple', [AppleController::class, 'handleAppleCallback'])
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
        ->name('apple.callback');


    // Server-to-server notification endpoint
    Route::post('/apple/notifications', [AppleController::class, 'handleServerNotification'])
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
        ->name('apple.notifications');


    // Apple Music callback
    Route::post('/apple-music', [AppleMusicController::class, 'handleAppleMusicCallback'])
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
        ->name('apple-music.callback');;
});

// Protected Routes
Route::middleware(['auth'])->group(function () {
    // Dashboard Routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/stats', [SpotifyController::class, 'getStats']);
    Route::get('/tracks', [SpotifyController::class, 'allTracks'])->name('tracks.index');
    Route::get('/playlists', [DashboardController::class, 'showPlaylists'])->name('playlists.index');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::delete('/notifications/delete-all', [NotificationController::class, 'deleteAll'])->name('notifications.deleteAll');
    Route::post('/notifications/bulk-delete', [NotificationController::class, 'bulkDelete'])->name('notifications.bulkDelete');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    
    // Search route
    Route::get('/search', [SearchController::class, 'search'])->name('search');
    
    // Legal page
    Route::get('/legal', function () {
        return Inertia::render('LegalIntegrated');
    })->name('legal');

    // Producer Routes
    
    Route::get('/producers', [DashboardController::class, 'showAllProducers'])->name('producers');
    Route::get('/graph/data', [DashboardController::class, 'getProducerGraphData'])->name('producer.graph.data');
    Route::get('/{id}/tracks', [DashboardController::class, 'viewProducerTracks'])->name('producer.tracks');
    Route::post('/producer/{id}/create-playlist', [DashboardController::class, 'createProducerPlaylist'])->name('producers.createPlaylist');


    // Data Fetch Routes
    Route::get('/fetch-producers', function () {
        set_time_limit(600);
        return app(SpotifyController::class)->fetchProducersForTracks();
    });

    Route::get('/fetch-listening-history', function () {
        set_time_limit(600); // Set a longer timeout for API calls
        return app(SpotifyController::class)->fetchListeningHistory();
    })->name('fetch-listening-history');

    // Test Apple Music
    Route::get('/test-apple-music', function() {
        $user = auth()->user();
        $developerToken = env('APPLE_CLIENT_SECRET');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$developerToken}",
            'Music-User-Token' => $user->apple_music_token
        ])->get('https://api.music.apple.com/v1/me/recent/played/tracks');

        return response()->json([
            'status' => $response->status(),
            'body' => $response->json()
        ]);
    })->middleware('auth');

    // API Routes
    Route::prefix('api')->group(function () {
        Route::get('/fetch-producers', function () {
            set_time_limit(600);
            return app(SpotifyController::class)->fetchProducersForTracks();
        })->name('api.fetchProducers');

        Route::get('/fetch-listening-history', function () {
            set_time_limit(600);
            return app(SpotifyController::class)->fetchListeningHistory();
        })->name('api.fetchListeningHistory');

        Route::get('/check-recommendations/{id}', function ($id) {
           $user = auth()->user();
           $cacheKey = "user_{$user->id}_producer_{$id}_recommended_tracks";

           if (Cache::has($cacheKey)) {
               $recommendedTracks = Cache::get($cacheKey)->take(9);
               return response()->json([
                   'recommendations_ready' => true,
                   'html' => view('partials.recommended_tracks', compact('recommendedTracks'))->render()
               ]);
           }
           return response()->json(['recommendations_ready' => false]);
        })->name('api.checkRecommendations');

        Route::post('/check-data-updates', [DataUpdateController::class, 'checkUpdates'])
            ->name('api.checkDataUpdates');
    });

});

// Apple Music API Routes
Route::get('/api/apple-music/token', [AppleMusicController::class, 'generateToken'])
    ->name('apple.music.token');

// Apple Music Mobile Auth Route
Route::get('/apple-music/mobile-auth', [AppleMusicMobileAuthController::class, 'showMobileAuth'])
    ->name('apple-music.mobile-auth');



Route::prefix('fetch-apple-music-data')->group(function () {
    Route::get('/', function () {
        try {
            $token = AppleMusicService::generateToken();
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
            ])->get("https://api.music.apple.com/v1/catalog/us/songs/203709340");

            return $response->successful()
                ? response()->json(['message' => 'Success', 'data' => $response->json()])
                : response()->json(['error' => 'Failed to fetch data'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    Route::get('/{type}/{id}', function ($type, $id) {
        try {
            $token = AppleMusicService::generateToken();
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
            ])->get("https://api.music.apple.com/v1/catalog/us/{$type}s/{$id}");

            if ($response->successful()) {
                $data = $response->json()['data'][0]['attributes'] ?? [];

                $discogsResponse = Http::get('https://api.discogs.com/database/search', [
                    'q' => $data['name'] ?? '',
                    'artist' => explode(',', $data['artistName'] ?? '')[0],
                    'type' => 'artist',
                    'key' => config('services.discogs.key'),
                    'secret' => config('services.discogs.secret'),
                ]);

                return response()->json([
                    'track_name' => $data['name'] ?? null,
                    'artist_name' => $data['artistName'] ?? null,
                    'album_name' => $data['albumName'] ?? null,
                    'producers' => collect($discogsResponse->json()['results'] ?? [])->pluck('title')->unique()->values(),
                ]);
            }

            return response()->json(['error' => 'Failed to fetch data'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});


//Auth::routes();

Route::get('/login', function () {
    return Inertia::render('Login', [
        'error' => session('error'),
    ]);
})->name('login');

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/producer/{producer}/collaborators', [ProducerController::class, 'collaborators'])->name('producer.collaborators');
Route::get('/producer/{id}', [ProducerController::class, 'show'])->name('producer.show');
// Route::get('/producers/{producer}/shared-tracks/{collaborator}', [ProducerController::class, 'sharedTracks'])->name('producer.sharedTracks');
Route::get('/producers/{producerId}/shared-tracks/{collaboratorId}', [ProducerController::class, 'sharedTracks'])
    ->name('producer.sharedTracks');
Route::get('/producer/{producer}/shared-tracks/artist/{artist}', [ProducerController::class, 'sharedTracksWithArtist'])->name('producer.artistSharedTracks');
Route::middleware('auth')->group(function () {
    Route::post('/producers/{producer}/follow', [ProducerController::class, 'toggleFollow'])->name('producers.follow');
    Route::post('/producers/{producer}/favourite', [ProducerController::class, 'toggleFavourite'])->name('producers.favourite');
    Route::get('/favourites', [ProducerController::class, 'favourites'])->name('producers.favourites');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/update-profile', [SettingsController::class, 'updateProfile'])->name('settings.updateProfile');
    Route::post('/settings/update-profile-image', [SettingsController::class, 'updateProfileImage'])->name('settings.updateProfileImage');
});