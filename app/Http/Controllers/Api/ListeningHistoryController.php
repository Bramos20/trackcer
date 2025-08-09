<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListeningHistory;
use App\Models\Producer;
use App\Models\Genre;
use App\Jobs\FetchProducersJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ListeningHistoryController extends Controller
{
    /**
     * Get paginated listening history
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 50);
        // Allow larger page sizes for fetching all data
        $perPage = min($perPage, 1000); // Max 1000 per page
        
        $history = ListeningHistory::where('user_id', $request->user()->id)
            ->with(['producers', 'genres', 'artistImage'])
            ->orderBy('played_at', 'desc')
            ->paginate($perPage);

        return response()->json($history);
    }

    /**
     * Sync listening history from iOS app
     */
    public function sync(Request $request)
    {
        $request->validate([
            'tracks' => 'required|array',
            'tracks.*.track_id' => 'required|string',
            'tracks.*.track_name' => 'required|string',
            'tracks.*.artist_name' => 'required|string',
            'tracks.*.album_name' => 'required|string',
            'tracks.*.played_at' => 'required|date',
            'tracks.*.duration_ms' => 'nullable|integer',
            'tracks.*.apple_music_id' => 'nullable|string',
            'tracks.*.isrc' => 'nullable|string',
            'tracks.*.album_artwork_url' => 'nullable|string',
            'tracks.*.preview_url' => 'nullable|string',
        ]);

        $user = $request->user();
        $syncedCount = 0;
        $skippedCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($request->tracks as $trackData) {
                // Check if this track already exists for this user at this time
                $existingTrack = ListeningHistory::where([
                    'user_id' => $user->id,
                    'track_id' => $trackData['track_id'],
                    'played_at' => $trackData['played_at'],
                ])->first();

                if ($existingTrack) {
                    $skippedCount++;
                    continue;
                }

                // Extract duration from track_data if not provided directly
                $durationMs = $trackData['duration_ms'] ?? null;
                
                // If duration_ms is not provided, try to extract from track_data JSON
                if (!$durationMs && isset($trackData['track_data'])) {
                    $trackDataJson = is_string($trackData['track_data']) 
                        ? json_decode($trackData['track_data'], true) 
                        : $trackData['track_data'];
                    
                    if (is_array($trackDataJson)) {
                        // Check for Apple Music format
                        if (isset($trackDataJson['attributes']['durationInMillis'])) {
                            $durationMs = $trackDataJson['attributes']['durationInMillis'];
                        }
                        // Check for Spotify format
                        elseif (isset($trackDataJson['duration_ms'])) {
                            $durationMs = $trackDataJson['duration_ms'];
                        }
                    }
                }
                
                // Build track_data JSON if not provided
                $trackDataJson = $trackData['track_data'] ?? null;
                
                // If track_data wasn't provided, build it from the individual fields
                if (!$trackDataJson && isset($trackData['apple_music_id'])) {
                    $trackDataJson = [
                        'id' => $trackData['apple_music_id'],
                        'attributes' => [
                            'name' => $trackData['track_name'],
                            'artistName' => $trackData['artist_name'],
                            'albumName' => $trackData['album_name'],
                            'durationInMillis' => $durationMs,
                            'isrc' => $trackData['isrc'] ?? null,
                            'artwork' => [
                                'url' => $trackData['album_artwork_url'] ?? null
                            ],
                            'previews' => !empty($trackData['preview_url']) ? [
                                ['url' => $trackData['preview_url']]
                            ] : []
                        ]
                    ];
                }
                
                // Create the listening history entry
                $history = ListeningHistory::create([
                    'user_id' => $user->id,
                    'track_id' => $trackData['track_id'],
                    'track_name' => $trackData['track_name'],
                    'artist_name' => $trackData['artist_name'],
                    'album_name' => $trackData['album_name'],
                    'played_at' => $trackData['played_at'],
                    'source' => 'Apple Music',
                    'track_data' => $trackDataJson,
                ]);

                // Dispatch job to fetch producers if we have Apple Music ID
                if (!empty($trackData['apple_music_id'])) {
                    FetchProducersJob::dispatch($history->id, $user->id);
                }

                $syncedCount++;
            }

            DB::commit();

            return response()->json([
                'message' => 'Listening history synced successfully',
                'synced_count' => $syncedCount,
                'skipped_count' => $skippedCount,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error syncing listening history: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to sync listening history',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get track details
     */
    public function show($id)
    {
        $track = ListeningHistory::where('user_id', auth()->id())
            ->where('id', $id)
            ->with(['producers', 'genres', 'artistImage'])
            ->firstOrFail();

        return response()->json($track);
    }
}