<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\FetchListeningHistoryJob;
use App\Jobs\FetchProducersJob;
use App\Jobs\CacheArtistImagesJob;
use App\Models\ListeningHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JobController extends Controller
{
    /**
     * Dispatch job to fetch listening history
     */
    public function fetchListeningHistory(Request $request)
    {
        $user = $request->user();
        
        if (!$user->apple_music_token) {
            return response()->json([
                'error' => 'Apple Music token not found. Please update your Apple Music token first.',
            ], 400);
        }
        
        try {
            FetchListeningHistoryJob::dispatch($user);
            
            return response()->json([
                'message' => 'Listening history fetch job dispatched successfully',
                'status' => 'processing',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch listening history job: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to dispatch job',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Dispatch jobs to fetch producers for tracks
     */
    public function fetchProducers(Request $request)
    {
        $user = $request->user();
        
        // Get tracks without producers
        $tracksWithoutProducers = ListeningHistory::where('user_id', $user->id)
            ->whereDoesntHave('producers')
            ->where('source', 'Apple Music')
            ->limit(100) // Process in batches
            ->get();
        
        if ($tracksWithoutProducers->isEmpty()) {
            return response()->json([
                'message' => 'No tracks found without producers',
                'tracks_processed' => 0,
            ]);
        }
        
        $jobsDispatched = 0;
        
        foreach ($tracksWithoutProducers as $track) {
            try {
                FetchProducersJob::dispatch($user);
                $jobsDispatched++;
            } catch (\Exception $e) {
                Log::error('Failed to dispatch producer job for track ' . $track->id . ': ' . $e->getMessage());
            }
        }
        
        return response()->json([
            'message' => 'Producer fetch jobs dispatched successfully',
            'jobs_dispatched' => $jobsDispatched,
            'status' => 'processing',
        ]);
    }
    
    /**
     * Dispatch job to cache artist images
     */
    public function cacheArtistImages(Request $request)
    {
        try {
            $user = $request->user();
            
            // Get unique artist names from listening history that don't have images
            $artistNames = ListeningHistory::where('user_id', $user->id)
                ->select('artist_name')
                ->distinct()
                ->whereNotNull('artist_name')
                ->whereNotExists(function ($query) {
                    $query->select('id')
                        ->from('artist_images')
                        ->whereColumn('artist_images.artist_name', 'listening_history.artist_name');
                })
                ->limit(100) // Process 100 at a time
                ->pluck('artist_name')
                ->toArray();
            
            if (empty($artistNames)) {
                return response()->json([
                    'message' => 'All artists already have images cached',
                    'status' => 'complete',
                    'artists_processed' => 0,
                ]);
            }
            
            CacheArtistImagesJob::dispatch($artistNames);
            
            return response()->json([
                'message' => 'Artist image caching job dispatched successfully',
                'status' => 'processing',
                'artists_to_process' => count($artistNames),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch artist image caching job: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to dispatch job',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get job status (simplified version)
     */
    public function status(Request $request)
    {
        $user = $request->user();
        
        // Get counts for quick status
        $totalTracks = ListeningHistory::where('user_id', $user->id)->count();
        $tracksWithProducers = ListeningHistory::where('user_id', $user->id)
            ->whereHas('producers')
            ->count();
        $tracksWithoutProducers = $totalTracks - $tracksWithProducers;
        
        return response()->json([
            'total_tracks' => $totalTracks,
            'tracks_with_producers' => $tracksWithProducers,
            'tracks_without_producers' => $tracksWithoutProducers,
            'processing_percentage' => $totalTracks > 0 ? round(($tracksWithProducers / $totalTracks) * 100) : 0,
        ]);
    }
}