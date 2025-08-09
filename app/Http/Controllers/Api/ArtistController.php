<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListeningHistory;
use App\Models\ArtistImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ArtistController extends Controller
{
    /**
     * Get all artists for the authenticated user with pagination
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $search = $request->input('search', '');
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 15);
            
            // Get all tracks for the user
            $allTracks = ListeningHistory::where('user_id', $user->id)
                ->with(['genres'])
                ->get();

            // Process tracks and split artists
            $artistsData = collect();
            
            foreach ($allTracks as $track) {
                $individualArtists = $this->splitArtistNames($track->artist_name);
                
                foreach ($individualArtists as $artistName) {
                    $artistName = trim($artistName);
                    if (empty($artistName)) continue;
                    
                    // Apply search filter on individual artist names
                    if ($search && stripos($artistName, $search) === false) {
                        continue;
                    }
                    
                    if (!$artistsData->has($artistName)) {
                        $artistsData->put($artistName, [
                            'artist_name' => $artistName,
                            'tracks' => collect(),
                            'track_count' => 0,
                            'total_minutes' => 0,
                            'popularity_sum' => 0,
                            'popularity_count' => 0,
                            'latest_track' => null,
                            'genres' => collect(),
                        ]);
                    }

                    $artistData = $artistsData->get($artistName);
                    $artistData['tracks']->push($track);
                    $artistData['track_count']++;

                    // Process track data
                    try {
                        $trackData = is_array($track->track_data) ? $track->track_data : json_decode($track->track_data, true);
                        $duration = 0;

                        if ($track->source === 'spotify') {
                            $duration = $trackData['duration_ms'] ?? 0;
                            if (isset($trackData['popularity']) && is_numeric($trackData['popularity'])) {
                                $artistData['popularity_sum'] += $trackData['popularity'];
                                $artistData['popularity_count']++;
                            }
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

                            if ($track->popularity_data) {
                                try {
                                    $popularityData = is_array($track->popularity_data)
                                        ? $track->popularity_data
                                        : json_decode($track->popularity_data, true);

                                    if (is_array($popularityData) && isset($popularityData['popularity']) && is_numeric($popularityData['popularity'])) {
                                        $artistData['popularity_sum'] += $popularityData['popularity'];
                                        $artistData['popularity_count']++;
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Error processing popularity data', [
                                        'track_id' => $track->id,
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            }
                        }

                        $artistData['total_minutes'] += $duration / 60000;

                        if (!$artistData['latest_track'] || $track->played_at > $artistData['latest_track']->played_at) {
                            $artistData['latest_track'] = $track;
                        }

                        // Collect genres
                        $artistData['genres'] = $artistData['genres']->merge($track->genres->pluck('name'))->unique();

                    } catch (\Exception $e) {
                        Log::error('Error processing track', [
                            'track_id' => $track->id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $artistsData->put($artistName, $artistData);
                }
            }

            // Sort by track count
            $sortedArtists = $artistsData->sortByDesc('track_count')->values();
            
            // Calculate pagination
            $total = $sortedArtists->count();
            $offset = ($page - 1) * $perPage;
            $paginatedItems = $sortedArtists->slice($offset, $perPage);
            
            // Preload artist images
            $artistNames = $paginatedItems->pluck('artist_name');
            $cachedImages = ArtistImage::whereIn('artist_name', $artistNames)
                ->pluck('image_url', 'artist_name');

            // Format final data
            $finalArtistsData = $paginatedItems->map(function ($item) use ($cachedImages) {
                $imageUrl = $cachedImages->get($item['artist_name']);

                return [
                    'artist_name' => $item['artist_name'],
                    'track_count' => $item['track_count'],
                    'total_minutes' => round($item['total_minutes'], 2),
                    'average_popularity' => $item['popularity_count'] > 0 
                        ? round($item['popularity_sum'] / $item['popularity_count'], 2) 
                        : 0,
                    'latest_track' => $item['latest_track'] ? [
                        'id' => $item['latest_track']->id,
                        'track_name' => $item['latest_track']->track_name,
                        'album_name' => $item['latest_track']->album_name,
                        'played_at' => $item['latest_track']->played_at,
                    ] : null,
                    'genres' => $item['genres']->take(5)->values(),
                    'image_url' => $imageUrl,
                ];
            });

            return response()->json([
                'data' => $finalArtistsData->values(),
                'current_page' => (int) $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
            ]);

        } catch (\Exception $e) {
            Log::error('Artist index error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id ?? null
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch artists',
                'message' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    /**
     * Get artist details
     */
    public function show(Request $request, $artistName)
    {
        try {
            $user = $request->user();
            $artistName = urldecode($artistName);

            // Get all tracks where this artist appears (including collaborations)
            $allTracks = ListeningHistory::where('user_id', $user->id)
                ->with(['genres', 'producers'])
                ->get();

            // Filter tracks to include only those where the artist appears
            $tracks = $allTracks->filter(function ($track) use ($artistName) {
                $individualArtists = $this->splitArtistNames($track->artist_name);
                return in_array($artistName, array_map('trim', $individualArtists));
            });

            if ($tracks->isEmpty()) {
                return response()->json([
                    'error' => 'Artist not found',
                    'message' => 'Artist not found in your listening history'
                ], 404);
            }

            // Use cached image
            $cachedImage = ArtistImage::where('artist_name', $artistName)->first();
            $artistImage = $cachedImage ? $cachedImage->image_url : null;

            // Calculate stats
            $totalMinutes = $tracks->sum(function ($track) {
                $trackData = is_array($track->track_data) 
                    ? $track->track_data 
                    : json_decode($track->track_data, true);
                    
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
                
                return $duration;
            }) / 60000;

            // Get genres
            $genres = $tracks->flatMap(fn($track) => $track->genres)->unique('id');
            
            // Genre breakdown
            $genreBreakdown = $genres->mapWithKeys(function ($genre) use ($tracks) {
                $genreTracks = $tracks->filter(fn($track) => 
                    $track->genres->contains('id', $genre->id)
                );
                
                $totalMinutes = $genreTracks->sum(function ($track) {
                    $trackData = is_array($track->track_data) 
                        ? $track->track_data 
                        : json_decode($track->track_data, true);
                        
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
                    return $duration;
                }) / 60000;
                
                return [$genre->name => [
                    'count' => $genreTracks->count(),
                    'minutes' => round($totalMinutes, 2)
                ]];
            });

            // Get top producers for this artist
            $producerStats = collect();
            foreach ($tracks as $track) {
                foreach ($track->producers as $producer) {
                    if (!$producerStats->has($producer->id)) {
                        $producerStats->put($producer->id, [
                            'id' => $producer->id,
                            'name' => $producer->name,
                            'image_url' => $producer->image_url,
                            'track_count' => 0
                        ]);
                    }
                    $stats = $producerStats->get($producer->id);
                    $stats['track_count']++;
                    $producerStats->put($producer->id, $stats);
                }
            }
            
            $topProducers = $producerStats->sortByDesc('track_count')
                ->take(10)
                ->values();

            // Format tracks for response
            $formattedTracks = $tracks->map(function ($track) {
                return [
                    'id' => $track->id,
                    'track_name' => $track->track_name,
                    'artist_name' => $track->artist_name,
                    'album_name' => $track->album_name,
                    'album_image_url' => $track->album_image_url,
                    'played_at' => $track->played_at,
                    'duration_ms' => $track->duration_ms,
                    'source' => $track->source,
                    'genres' => $track->genres->pluck('name'),
                    'producers' => $track->producers->map(function ($producer) {
                        return [
                            'id' => $producer->id,
                            'name' => $producer->name,
                        ];
                    }),
                ];
            })->sortByDesc('played_at')->values();

            return response()->json([
                'artist' => [
                    'name' => $artistName,
                    'image_url' => $artistImage,
                ],
                'stats' => [
                    'total_tracks' => $tracks->count(),
                    'total_minutes' => round($totalMinutes, 2),
                    'genre_breakdown' => $genreBreakdown,
                ],
                'tracks' => $formattedTracks->take(50), // Limit to recent 50 tracks
                'top_producers' => $topProducers,
            ]);

        } catch (\Exception $e) {
            Log::error('Artist show error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'artist_name' => $artistName,
                'user_id' => $request->user()->id ?? null
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch artist details',
                'message' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    /**
     * Get top artists for a specific time range
     */
    public function topArtists(Request $request)
    {
        try {
            $user = $request->user();
            $range = $request->input('range', 'all'); // all, week, month, custom
            $start = $request->input('start');
            $end = $request->input('end');
            $limit = $request->input('limit', 10);

            $query = ListeningHistory::where('user_id', $user->id);

            // Apply date filters
            if ($range === 'week') {
                $query->where('played_at', '>=', Carbon::now()->subWeek());
            } elseif ($range === 'month') {
                $query->where('played_at', '>=', Carbon::now()->subMonth());
            } elseif ($range === 'custom' && $start && $end) {
                $query->whereBetween('played_at', [
                    Carbon::parse($start)->startOfDay(),
                    Carbon::parse($end)->endOfDay()
                ]);
            }

            $tracks = $query->get();
            
            // Process artist counts
            $artistCounts = collect();
            foreach ($tracks as $track) {
                $individualArtists = $this->splitArtistNames($track->artist_name);
                foreach ($individualArtists as $artistName) {
                    $artistName = trim($artistName);
                    if (empty($artistName)) continue;
                    
                    $artistCounts->put($artistName, ($artistCounts->get($artistName, 0) + 1));
                }
            }

            // Get top artists
            $topArtists = $artistCounts->sortByDesc(function ($count) {
                return $count;
            })->take($limit);

            // Get artist images
            $artistNames = $topArtists->keys();
            $artistImages = ArtistImage::whereIn('artist_name', $artistNames)
                ->pluck('image_url', 'artist_name');

            // Format response
            $formattedArtists = $topArtists->map(function ($count, $artistName) use ($artistImages) {
                return [
                    'artist_name' => $artistName,
                    'track_count' => $count,
                    'image_url' => $artistImages->get($artistName),
                ];
            })->values();

            return response()->json([
                'data' => $formattedArtists,
                'range' => $range,
                'total' => $topArtists->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Top artists error', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch top artists',
                'message' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }
    
    /**
     * Split artist names by common separators
     */
    private function splitArtistNames($artistName)
    {
        // List of artist names that should not be split
        $doNotSplitArtists = [
            'Tyler, The Creator',
            'Portugal, The Man',
            'Dexys Midnight Runners',
            'Earth, Wind & Fire',
            'Crosby, Stills & Nash',
            'Crosby, Stills, Nash & Young',
            'Emerson, Lake & Palmer',
            'Blood, Sweat & Tears',
            'Peter, Paul & Mary',
            'Simon & Garfunkel',
            'Hall & Oates',
            'Ike & Tina Turner',
            'Sonny & Cher',
            'Brooks & Dunn',
            'Tegan & Sara',
            'Angus & Julia Stone',
            'She & Him',
            'Hootie & The Blowfish',
            'Huey Lewis & The News',
            'Me First & the Gimme Gimmes',
            'Toots & The Maytals',
            'Martha & The Vandellas',
            'Iron & Wine',
            'Nick Cave & The Bad Seeds',
            'Bob Marley & The Wailers',
            'The Mamas & The Papas',
            'Tom Petty & The Heartbreakers',
            'Derek & The Dominos',
            'Captain & Tennille',
            'Ashford & Simpson',
            'Sam & Dave',
            'Peaches & Herb',
            'Richard & Linda Thompson',
            'Bob Seger & The Silver Bullet Band',
            'Brownie McGhee & Sonny Terry',
            'Gladys Knight & The Pips',
            'Little Anthony & The Imperials',
            'Gary Puckett & The Union Gap',
            'Smokey Robinson & The Miracles',
            'Sly & The Family Stone',
            'Dr. Hook & The Medicine Show',
            'Emerson, Lake & Powell'
        ];
        
        // Check if this is a "do not split" artist
        foreach ($doNotSplitArtists as $doNotSplit) {
            if (stripos($artistName, $doNotSplit) !== false) {
                return [$artistName];
            }
        }
        
        // Split by common separators using regex like the webapp
        $artists = preg_split('/[,&]|\s+(?:feat\.|featuring|ft\.|with)\s+/i', $artistName);
        
        return array_map(function($artist) {
            return trim($artist);
        }, $artists);
    }
}