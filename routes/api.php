<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ListeningHistoryController;
use App\Http\Controllers\Api\ProducerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\TestAuthController;
use App\Http\Controllers\Api\DiagnosticController;
use App\Http\Controllers\Api\ArtistImageController;
use App\Http\Controllers\Api\ArtistController;
use App\Http\Controllers\Api\AppleMusicTokenController;
use App\Http\Controllers\Api\DebugAuthController;
use App\Http\Controllers\Api\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Debug route (temporary - remove after fixing auth)
Route::get('/debug/auth', [DebugAuthController::class, 'debugHeaders']);
Route::middleware('auth:sanctum')->get('/debug/auth-protected', [DebugAuthController::class, 'debugHeaders']);

// Public routes
Route::post('/auth/apple', [AuthController::class, 'loginWithApple']);

// Test authentication routes
Route::post('/test-auth', [TestAuthController::class, 'simpleLogin']);

// Diagnostic routes (for debugging)
Route::get('/diagnostic/sanctum', [DiagnosticController::class, 'checkSanctum']);
Route::post('/diagnostic/test-token', [DiagnosticController::class, 'createTestToken']);

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/profile-image', [AuthController::class, 'updateProfileImage']);
    Route::post('/profile-image', [AuthController::class, 'updateProfileImage']); // Alternate route for compatibility
    Route::put('/user/apple-music-token', [AuthController::class, 'updateAppleMusicToken']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Test token verification
    Route::get('/test-token', [TestAuthController::class, 'testToken']);
    
    // Listening history routes
    Route::prefix('listening-history')->group(function () {
        Route::get('/', [ListeningHistoryController::class, 'index']);
        Route::post('/sync', [ListeningHistoryController::class, 'sync']);
        Route::get('/{id}', [ListeningHistoryController::class, 'show']);
    });
    
    // Producer routes
    Route::prefix('producers')->group(function () {
        Route::get('/', [ProducerController::class, 'index']);
        Route::get('/{id}', [ProducerController::class, 'show']);
        Route::post('/{id}/follow', [ProducerController::class, 'follow']);
        Route::post('/{id}/unfollow', [ProducerController::class, 'unfollow']);
        Route::post('/{id}/favorite', [ProducerController::class, 'favorite']);
        Route::post('/{id}/unfavorite', [ProducerController::class, 'unfavorite']);
        Route::get('/{id}/shared-tracks', [ProducerController::class, 'sharedTracks']);
        Route::get('/{id}/artist-tracks', [ProducerController::class, 'artistTracks']);
    });
    
    // Dashboard routes
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'stats']);
        Route::get('/top-producer-today', [DashboardController::class, 'topProducerToday']);
    });
    
    // Job routes
    Route::prefix('jobs')->group(function () {
        Route::post('/fetch-listening-history', [JobController::class, 'fetchListeningHistory']);
        Route::post('/fetch-producers', [JobController::class, 'fetchProducers']);
        Route::post('/cache-artist-images', [JobController::class, 'cacheArtistImages']);
        Route::get('/status', [JobController::class, 'status']);
    });
    
    // Artist routes
    Route::prefix('artists')->group(function () {
        Route::get('/', [ArtistController::class, 'index']);
        Route::get('/top', [ArtistController::class, 'topArtists']);
        Route::get('/{artistName}', [ArtistController::class, 'show']);
    });
    
    // Artist image routes
    Route::prefix('artist-images')->group(function () {
        Route::get('/', [ArtistImageController::class, 'getArtistImage']);
        Route::post('/fetch', [ArtistImageController::class, 'fetchImages']);
        Route::get('/stats', [ArtistImageController::class, 'stats']);
        Route::post('/batch', [ArtistImageController::class, 'batch']);
    });
    
    // Apple Music routes
    Route::prefix('apple-music')->group(function () {
        Route::get('/developer-token', [AppleMusicTokenController::class, 'generateDeveloperToken']);
    });
    
    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
    });
});

