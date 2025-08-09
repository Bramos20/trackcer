<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producer;
use App\Models\ListeningHistory;
use App\Models\ArtistImage;
use App\Http\Resources\ProducerResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ProducerController extends Controller
{
    /**
     * Get paginated list of producers
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 200);
            // Allow larger page sizes for fetching all data
            $perPage = min($perPage, 1000); // Max 1000 per page
            $search = $request->input('search');
            $user = $request->user();
            $page = $request->input('page', 1);
            $fields = $request->input('fields', 'full'); // minimal, basic, full

            // Get all unique producer IDs from the listening history for this user
            $producerIdsSubquery = DB::table('listening_history as lh')
                ->select('pt.producer_id')
                ->join('producer_track as pt', 'lh.id', '=', 'pt.listening_history_id')
                ->where('lh.user_id', $user->id)
                ->distinct();

            // Get producer details
            $query = DB::table('producers as p')
                ->joinSub($producerIdsSubquery, 'user_producers', function ($join) {
                    $join->on('p.id', '=', 'user_producers.producer_id');
                });
                
            if ($search) {
                $query->where('p.name', 'like', "%{$search}%");
            }
            
            // Get total count
            $total = $query->count();
            
            \Log::info('Producer index query', [
                'user_id' => $user->id,
                'total_producers' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'search' => $search
            ]);
            
            // Now get the paginated producers with their stats
            $offset = ($page - 1) * $perPage;
            
            // Get paginated producer IDs
            $paginatedQuery = clone $query;
            $paginatedProducerIds = $paginatedQuery
                ->select('p.id')
                ->orderBy('p.name')
                ->offset($offset)
                ->limit($perPage)
                ->pluck('p.id');
            
            if ($paginatedProducerIds->isEmpty()) {
                return response()->json([
                    'data' => [],
                    'current_page' => (int) $page,
                    'per_page' => $perPage,
                    'total' => 0,
                    'last_page' => 1,
                ]);
            }
            
            // Get producer details with stats for the paginated set
            $producers = DB::table('producers')
                ->whereIn('producers.id', $paginatedProducerIds)
                ->select('producers.id', 'producers.name', 'producers.image_url')
                ->get();
                
            // Calculate stats for each producer
            $producers = $producers->map(function($producer) use ($user) {
                // Get track count and calculate total minutes from listening history
                $tracks = ListeningHistory::where('user_id', $user->id)
                    ->whereHas('producers', function($q) use ($producer) {
                        $q->where('producer_id', $producer->id);
                    })
                    ->get();
                    
                $totalMinutes = 0;
                foreach ($tracks as $track) {
                    $trackData = is_array($track->track_data) ? $track->track_data : json_decode($track->track_data, true);
                    $duration = 0;
                    
                    if ($track->source === 'spotify') {
                        $duration = $trackData['duration_ms'] ?? 0;
                    } elseif ($track->source === 'Apple Music') {
                        if (isset($trackData['attributes']['durationInMillis'])) {
                            $duration = $trackData['attributes']['durationInMillis'];
                        } elseif (isset($trackData['data'][0]['attributes']['durationInMillis'])) {
                            $duration = $trackData['data'][0]['attributes']['durationInMillis'];
                        } else {
                            $jsonString = json_encode($trackData);
                            if (preg_match('/"durationInMillis"\s*:\s*(\d+)/', $jsonString, $matches)) {
                                $duration = intval($matches[1]);
                            }
                        }
                    }
                    
                    $totalMinutes += $duration / 60000;
                }
                
                $producer->track_count = $tracks->count();
                $producer->total_minutes = $totalMinutes;
                
                return $producer;
            });

            // Transform to match expected format with field selection
            $producersCollection = collect($producers)->map(function ($producer) use ($user, $fields) {
                try {
                    // For minimal fields, skip expensive operations
                    if ($fields === 'minimal') {
                        return [
                            'id' => $producer->id,
                            'name' => $producer->name,
                            'image_url' => $producer->image_url,
                            'total_tracks' => (int) $producer->track_count,
                        ];
                    }
                    
                    // Check follow/favorite status for basic and full
                    $producerModel = Producer::find($producer->id);
                    
                    $data = [
                        'id' => $producer->id,
                        'name' => $producer->name,
                        'image_url' => $producer->image_url,
                        'total_tracks' => (int) $producer->track_count,
                        'total_minutes' => round($producer->total_minutes),
                        'is_following' => $producerModel ? $producerModel->followers()->where('user_id', $user->id)->exists() : false,
                        'is_favorite' => $producerModel ? $producerModel->favouritedBy()->where('user_id', $user->id)->exists() : false,
                    ];
                    
                    // Add full fields
                    if ($fields === 'full') {
                        $data['followers_count'] = $producerModel ? $producerModel->followers()->count() : 0;
                    }
                    
                    return $data;
                } catch (\Exception $e) {
                    \Log::error('Error transforming producer', [
                        'producer_id' => $producer->id,
                        'error' => $e->getMessage()
                    ]);
                    
                    return [
                        'id' => $producer->id,
                        'name' => $producer->name,
                        'image_url' => $producer->image_url,
                        'total_tracks' => (int) $producer->track_count,
                        'total_minutes' => round($producer->total_minutes),
                        'is_following' => false,
                        'is_favorite' => false,
                        'followers_count' => 0,
                    ];
                }
            });

            // Create paginated response
            $paginatedResponse = [
                'data' => $producersCollection,
                'current_page' => (int) $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
            ];

            return response()->json($paginatedResponse);
            
        } catch (\Exception $e) {
            \Log::error('Producer index error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id ?? null
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch producers',
                'message' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    /**
     * Get producer details
     */
    public function show($id)
    {
        try {
            $user = auth()->user();
            
            \Log::info('Producer show request', ['producer_id' => $id, 'user_id' => $user->id]);
            
            $producer = Producer::findOrFail($id);
            
            // Add basic info
            try {
                // Add follow/favorite status
                $producer->is_following = $producer->followers()->where('user_id', $user->id)->exists();
                $producer->is_favorite = $producer->favouritedBy()->where('user_id', $user->id)->exists();
                $producer->followers_count = $producer->followers()->count();
            } catch (\Exception $e) {
                \Log::error('Error getting follow/favorite status', ['error' => $e->getMessage()]);
                $producer->is_following = false;
                $producer->is_favorite = false;
                $producer->followers_count = 0;
            }
            
            // Get detailed analytics
            try {
                $analytics = $this->getDetailedProducerAnalytics($producer->id, $user->id);
                $producer->analytics = $analytics;
            } catch (\Exception $e) {
                \Log::error('Error getting analytics', ['error' => $e->getMessage()]);
                $producer->analytics = [
                    'total_tracks' => 0,
                    'total_minutes' => 0,
                    'first_listened' => null,
                    'last_listened' => null,
                    'monthly_breakdown' => []
                ];
            }
            
            // Get genre breakdown
            try {
                $genreBreakdown = $this->getProducerGenreBreakdown($producer->id, $user->id);
                $genreDict = [];
                foreach ($genreBreakdown as $genre) {
                    $genreDict[$genre->name] = $genre->count;
                }
                $producer->genre_breakdown = !empty($genreDict) ? $genreDict : new \stdClass();
                \Log::info('Producer genre breakdown', [
                    'producer_id' => $producer->id,
                    'genre_count' => count($genreDict),
                    'genres' => $genreDict
                ]);
            } catch (\Exception $e) {
                \Log::error('Error getting genre breakdown', ['error' => $e->getMessage()]);
                $producer->genre_breakdown = [];
            }
            
            // Get recent tracks
            try {
                $recentTracks = ListeningHistory::where('user_id', $user->id)
                    ->whereHas('producers', function ($query) use ($id) {
                        $query->where('producer_id', $id);
                    })
                    ->with(['producers', 'genres'])
                    ->orderBy('played_at', 'desc')
                    ->limit(10)
                    ->get();
                
                $producer->recent_tracks = $recentTracks;
            } catch (\Exception $e) {
                \Log::error('Error getting recent tracks', ['error' => $e->getMessage()]);
                $producer->recent_tracks = [];
            }
            
            // Get collaborators
            try {
                $collaborators = $this->getProducerCollaborators($producer->id, $user->id);
                $producer->collaborators = $collaborators;
                \Log::info('Producer collaborators', [
                    'producer_id' => $producer->id,
                    'collaborator_count' => count($collaborators),
                    'collaborators' => $collaborators->toArray()
                ]);
            } catch (\Exception $e) {
                \Log::error('Error getting collaborators', ['error' => $e->getMessage()]);
                $producer->collaborators = [];
            }
            
            // Get artist collaborators
            try {
                $artistCollaborators = $this->getArtistCollaborators($producer->id, $user->id);
                $producer->artist_collaborators = $artistCollaborators;
            } catch (\Exception $e) {
                \Log::error('Error getting artist collaborators', ['error' => $e->getMessage()]);
                $producer->artist_collaborators = [];
            }

            return response()->json($producer);
            
        } catch (\Exception $e) {
            \Log::error('Producer show error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'producer_id' => $id,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch producer details',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Follow a producer
     */
    public function follow(Request $request, $id)
    {
        $producer = Producer::findOrFail($id);
        $user = $request->user();

        if (!$producer->followers()->where('user_id', $user->id)->exists()) {
            $producer->followers()->attach($user->id);
        }

        return response()->json([
            'message' => 'Producer followed successfully',
            'is_following' => true,
        ]);
    }

    /**
     * Unfollow a producer
     */
    public function unfollow(Request $request, $id)
    {
        $producer = Producer::findOrFail($id);
        $user = $request->user();

        $producer->followers()->detach($user->id);

        return response()->json([
            'message' => 'Producer unfollowed successfully',
            'is_following' => false,
        ]);
    }

    /**
     * Add producer to favorites
     */
    public function favorite(Request $request, $id)
    {
        $producer = Producer::findOrFail($id);
        $user = $request->user();

        if (!$producer->favouritedBy()->where('user_id', $user->id)->exists()) {
            $producer->favouritedBy()->attach($user->id);
        }

        return response()->json([
            'message' => 'Producer added to favorites',
            'is_favorite' => true,
        ]);
    }

    /**
     * Remove producer from favorites
     */
    public function unfavorite(Request $request, $id)
    {
        $producer = Producer::findOrFail($id);
        $user = $request->user();

        $producer->favouritedBy()->detach($user->id);

        return response()->json([
            'message' => 'Producer removed from favorites',
            'is_favorite' => false,
        ]);
    }

    /**
     * Get shared tracks between two producers
     */
    public function sharedTracks(Request $request, $producerId)
    {
        $collaboratorId = $request->input('collaborator_id');
        $user = $request->user();

        if (!$collaboratorId) {
            return response()->json(['error' => 'Collaborator ID is required'], 400);
        }

        // Get tracks where both producers worked together
        $sharedTracks = ListeningHistory::where('user_id', $user->id)
            ->whereHas('producers', function ($query) use ($producerId) {
                $query->where('producer_id', $producerId);
            })
            ->whereHas('producers', function ($query) use ($collaboratorId) {
                $query->where('producer_id', $collaboratorId);
            })
            ->with(['producers', 'genres'])
            ->orderBy('played_at', 'desc')
            ->get()
            ->map(function ($track) {
                return [
                    'id' => $track->id,
                    'track_name' => $track->track_name,
                    'artist_name' => $track->artist_name,
                    'album_name' => $track->album_name,
                    'album_image_url' => $track->album_image_url,
                    'played_at' => $track->played_at,
                    'duration_ms' => $track->duration_ms,
                    'genres' => $track->genres->pluck('name'),
                    'producers' => $track->producers->map(function ($producer) {
                        return [
                            'id' => $producer->id,
                            'name' => $producer->name,
                        ];
                    }),
                ];
            });

        return response()->json([
            'data' => $sharedTracks,
            'total' => $sharedTracks->count(),
        ]);
    }

    /**
     * Get tracks between a producer and an artist
     */
    public function artistTracks(Request $request, $producerId)
    {
        // iOS app sends 'artist' parameter, not 'artist_name'
        $artistName = $request->input('artist', $request->input('artist_name'));
        $user = $request->user();

        if (!$artistName) {
            return response()->json(['error' => 'Artist name is required'], 400);
        }

        // Get tracks where the producer worked with the artist
        $artistTracks = ListeningHistory::where('user_id', $user->id)
            ->whereHas('producers', function ($query) use ($producerId) {
                $query->where('producer_id', $producerId);
            })
            ->where(function ($query) use ($artistName) {
                $query->where('artist_name', 'like', "%{$artistName}%")
                      ->orWhere('artist_name', 'like', "%{$artistName},%")
                      ->orWhere('artist_name', 'like', "%,{$artistName}%");
            })
            ->with(['producers', 'genres'])
            ->orderBy('played_at', 'desc')
            ->get()
            ->map(function ($track) {
                return [
                    'id' => $track->id,
                    'track_name' => $track->track_name,
                    'artist_name' => $track->artist_name,
                    'album_name' => $track->album_name,
                    'album_image_url' => $track->album_image_url,
                    'played_at' => $track->played_at,
                    'duration_ms' => $track->duration_ms,
                    'genres' => $track->genres->pluck('name'),
                    'producers' => $track->producers->map(function ($producer) {
                        return [
                            'id' => $producer->id,
                            'name' => $producer->name,
                        ];
                    }),
                ];
            });

        return response()->json([
            'data' => $artistTracks,
            'total' => $artistTracks->count(),
        ]);
    }

    /**
     * Get producer analytics for a user
     */
    private function getProducerAnalytics($producerId, $userId)
    {
        $cacheKey = "producer_analytics_{$producerId}_{$userId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($producerId, $userId) {
            // Get all tracks for this producer
            $tracks = ListeningHistory::where('user_id', $userId)
                ->whereHas('producers', function ($query) use ($producerId) {
                    $query->where('producer_id', $producerId);
                })
                ->get();
            
            if ($tracks->isEmpty()) {
                return [
                    'total_tracks' => 0,
                    'total_minutes' => 0,
                    'first_listened' => null,
                    'last_listened' => null,
                ];
            }
            
            // Calculate total minutes from track data
            $totalMinutes = 0;
            foreach ($tracks as $track) {
                $trackData = is_array($track->track_data) ? $track->track_data : json_decode($track->track_data, true);
                $duration = 0;
                
                if ($track->source === 'spotify') {
                    $duration = $trackData['duration_ms'] ?? 0;
                } elseif ($track->source === 'Apple Music') {
                    if (isset($trackData['attributes']['durationInMillis'])) {
                        $duration = $trackData['attributes']['durationInMillis'];
                    } elseif (isset($trackData['data'][0]['attributes']['durationInMillis'])) {
                        $duration = $trackData['data'][0]['attributes']['durationInMillis'];
                    } else {
                        $jsonString = json_encode($trackData);
                        if (preg_match('/"durationInMillis"\s*:\s*(\d+)/', $jsonString, $matches)) {
                            $duration = intval($matches[1]);
                        }
                    }
                }
                
                $totalMinutes += $duration / 60000;
            }

            return [
                'total_tracks' => $tracks->count(),
                'total_minutes' => round($totalMinutes),
                'first_listened' => $tracks->min('played_at'),
                'last_listened' => $tracks->max('played_at'),
            ];
        });
    }

    /**
     * Get detailed producer analytics
     */
    private function getDetailedProducerAnalytics($producerId, $userId)
    {
        $analytics = $this->getProducerAnalytics($producerId, $userId);
        
        // Get tracks from last 12 months
        $tracks = ListeningHistory::where('user_id', $userId)
            ->whereHas('producers', function ($query) use ($producerId) {
                $query->where('producer_id', $producerId);
            })
            ->where('played_at', '>=', now()->subMonths(12))
            ->get();
        
        // Group by month
        $monthlyBreakdown = $tracks->groupBy(function ($track) {
            return Carbon::parse($track->played_at)->format('Y-m');
        })->map(function ($monthTracks, $month) {
            $totalMinutes = 0;
            foreach ($monthTracks as $track) {
                $trackData = is_array($track->track_data) ? $track->track_data : json_decode($track->track_data, true);
                $duration = 0;
                
                if ($track->source === 'spotify') {
                    $duration = $trackData['duration_ms'] ?? 0;
                } elseif ($track->source === 'Apple Music') {
                    if (isset($trackData['attributes']['durationInMillis'])) {
                        $duration = $trackData['attributes']['durationInMillis'];
                    } elseif (isset($trackData['data'][0]['attributes']['durationInMillis'])) {
                        $duration = $trackData['data'][0]['attributes']['durationInMillis'];
                    } else {
                        $jsonString = json_encode($trackData);
                        if (preg_match('/"durationInMillis"\s*:\s*(\d+)/', $jsonString, $matches)) {
                            $duration = intval($matches[1]);
                        }
                    }
                }
                
                $totalMinutes += $duration / 60000;
            }
            
            return (object)[
                'month' => $month,
                'track_count' => $monthTracks->count(),
                'minutes' => round($totalMinutes)
            ];
        })->values()->sortBy('month')->values();

        $analytics['monthly_breakdown'] = $monthlyBreakdown;
        
        return $analytics;
    }

    /**
     * Get producer genre breakdown
     */
    private function getProducerGenreBreakdown($producerId, $userId)
    {
        return DB::table('genres')
            ->join('genre_track', 'genres.id', '=', 'genre_track.genre_id')
            ->join('listening_history', 'genre_track.listening_history_id', '=', 'listening_history.id')
            ->join('producer_track', 'listening_history.id', '=', 'producer_track.listening_history_id')
            ->where('listening_history.user_id', $userId)
            ->where('producer_track.producer_id', $producerId)
            ->select('genres.name', DB::raw('COUNT(*) as count'))
            ->groupBy('genres.id', 'genres.name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
    }
    
    /**
     * Get producer collaborators
     */
    private function getProducerCollaborators($producerId, $userId)
    {
        // Get all track IDs the producer has worked on for this user
        $trackIds = DB::table('listening_history')
            ->join('producer_track', 'listening_history.id', '=', 'producer_track.listening_history_id')
            ->where('listening_history.user_id', $userId)
            ->where('producer_track.producer_id', $producerId)
            ->pluck('listening_history.id');
        
        // Find other producers who worked on the same tracks
        $collaborators = DB::table('producers')
            ->join('producer_track', 'producers.id', '=', 'producer_track.producer_id')
            ->whereIn('producer_track.listening_history_id', $trackIds)
            ->where('producers.id', '!=', $producerId)
            ->select(
                'producers.id',
                'producers.name',
                'producers.image_url',
                DB::raw('COUNT(DISTINCT producer_track.listening_history_id) as collaboration_count')
            )
            ->groupBy('producers.id', 'producers.name', 'producers.image_url')
            ->orderBy('collaboration_count', 'desc')
            ->limit(10)
            ->get();
            
        // Transform to match Producer model structure
        return $collaborators->map(function ($collab) {
            return [
                'id' => $collab->id,
                'name' => $collab->name,
                'image_url' => $collab->image_url,
                'total_tracks' => $collab->collaboration_count,
                'track_count' => $collab->collaboration_count,
                'is_following' => false,
                'is_favorite' => false,
                'followers_count' => 0
            ];
        })->values();
    }
    
    /**
     * Get artist collaborators
     */
    private function getArtistCollaborators($producerId, $userId)
    {
        // Get all tracks for this producer
        $tracks = DB::table('listening_history')
            ->join('producer_track', 'listening_history.id', '=', 'producer_track.listening_history_id')
            ->where('listening_history.user_id', $userId)
            ->where('producer_track.producer_id', $producerId)
            ->select('listening_history.artist_name')
            ->get();
        
        // Process artist names (handle multiple artists per track)
        $artistCounts = collect();
        foreach ($tracks as $track) {
            $artists = explode(',', $track->artist_name);
            foreach ($artists as $artist) {
                $artist = trim($artist);
                if (!empty($artist)) {
                    $artistCounts[$artist] = ($artistCounts[$artist] ?? 0) + 1;
                }
            }
        }
        
        // Get top artists and their images
        $topArtists = $artistCounts->sortDesc()->take(10);
        $artistNames = $topArtists->keys();
        
        // Fetch artist images
        $artistImages = ArtistImage::whereIn('artist_name', $artistNames)
            ->pluck('image_url', 'artist_name');
        
        // Format the response
        return $topArtists->map(function ($count, $name) use ($artistImages) {
            return [
                'artist_name' => $name,
                'image_url' => $artistImages->get($name),
                'track_count' => $count,
                'total_minutes' => 0, // Would need to calculate this from track durations
                'first_collaboration' => null,
                'last_collaboration' => null
            ];
        })->values();
    }
}