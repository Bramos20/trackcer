<?php

namespace App\Http\Controllers;

use App\Services\ProducerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\ListeningHistory;
Use App\Models\User;
Use App\Models\Producer;
Use App\Models\Genre;
use App\Models\Playlist;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $developerToken;
    protected $openAiApiKey;

    public function __construct()
    {
        $this->developerToken = config('services.apple.client_secret');
        $this->openAiApiKey = config('services.openai.api_key');
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        // Fetch the selected time frame (default to 'all')
        $timeframe = $request->get('timeframe', 'all');

        // Determine the start date based on the time frame
        $startDate = null;
        if ($timeframe === 'today') {
            $startDate = now()->startOfDay();
        } elseif ($timeframe === 'last_2_days') {
            $startDate = now()->subDays(2);
        } elseif ($timeframe === 'last_week') {
            $startDate = now()->subWeek();
        } elseif ($timeframe === 'last_month') {
            $startDate = now()->subMonth();
        } elseif ($timeframe === 'last_year') {
            $startDate = now()->subYear();
        }

        // Fetch recent tracks
        $history = ListeningHistory::with(['producers', 'genres'])
            ->where('user_id', $user->id)
            ->orderBy('played_at', 'desc')
            ->limit(5);
        if ($startDate) {
            $history->where('played_at', '>=', $startDate);
        }
        $history = $history->get();

        // Total unique tracks (count distinct track_id)
        $totalTracks = ListeningHistory::where('user_id', $user->id);
        if ($startDate) {
            $totalTracks->where('played_at', '>=', $startDate);
        }
        $totalTracks = $totalTracks->distinct('track_id')->count('track_id');

        // Total minutes played (sum all plays, including duplicates)
        $totalMinutes = ListeningHistory::where('user_id', $user->id);
        if ($startDate) {
            $totalMinutes->where('played_at', '>=', $startDate);
        }
        $totalMinutes = $totalMinutes->get()->sum(function ($track) {
            $trackData = is_array($track->track_data) ? $track->track_data : json_decode($track->track_data, true);

            // Handle different data structures for Spotify and Apple Music
            $duration = 0;

            if ($track->source === 'spotify') {
                // Spotify-specific duration extraction
                $duration = $trackData['duration_ms'] ?? 0;
            } elseif ($track->source === 'Apple Music') {
                // Apple Music-specific duration extraction with improved structure handling

                // For direct track objects from Apple Music API
                if (isset($trackData['attributes']['durationInMillis'])) {
                    $duration = $trackData['attributes']['durationInMillis'];
                }
                // For data wrapped in a 'data' array (like in the recent tracks response)
                elseif (isset($trackData['data'][0]['attributes']['durationInMillis'])) {
                    $duration = $trackData['data'][0]['attributes']['durationInMillis'];
                }
                // As a fallback, try to find durationInMillis anywhere in the structure
                else {
                    $jsonString = json_encode($trackData);
                    if (preg_match('/"durationInMillis"\s*:\s*(\d+)/', $jsonString, $matches)) {
                        $duration = intval($matches[1]);
                    }
                }

                // Detailed logging for troubleshooting
                if ($duration === 0) {
                    Log::warning('Zero duration for Apple Music track', [
                        'track_id' => $track->id,
                        'track_name' => $track->track_name ?? 'Unknown',
                        'data_keys' => array_keys($trackData),
                        'has_attributes' => isset($trackData['attributes']),
                        'has_data_array' => isset($trackData['data']),
                        'sample_data' => json_encode(array_slice($trackData, 0, 3))
                    ]);
                }
            }

            // Log detailed track duration information
            Log::info('Track Duration Calculation', [
                'track_id' => $track->id,
                'source' => $track->source,
                'original_duration' => $duration,
                'minutes' => $duration / 60000
            ]);

            // Convert milliseconds to minutes
            return $duration / 60000;
        });

        $totalMinutes = round($totalMinutes, 2);

        // Total producers
        $totalProducers = Producer::whereHas('tracks', function ($query) use ($user, $startDate) {
            $query->where('user_id', $user->id);
            if ($startDate) {
                $query->where('played_at', '>=', $startDate);
            }
        })->count();

        // Calculate today's changes
        $todayStart = now()->startOfDay();
        $yesterdayStart = now()->subDay()->startOfDay();
        $yesterdayEnd = now()->subDay()->endOfDay();

        // Today's new unique tracks
        $todayTracks = ListeningHistory::where('user_id', $user->id)
            ->where('played_at', '>=', $todayStart)
            ->distinct('track_id')
            ->count('track_id');

        // Today's new producers
        $todayProducers = Producer::whereHas('tracks', function ($query) use ($user, $todayStart) {
            $query->where('user_id', $user->id)
                  ->where('played_at', '>=', $todayStart);
        })->count();

        // Get producers with their tracks, then sort by unique track count
        $topProducers = Producer::whereHas('tracks', function ($query) use ($user, $startDate) {
            $query->where('user_id', $user->id);
            if ($startDate) {
                $query->where('played_at', '>=', $startDate);
            }
        })
            ->with(['tracks' => function ($query) use ($user, $startDate) {
                $query->where('user_id', $user->id);
                if ($startDate) {
                    $query->where('played_at', '>=', $startDate);
                }
            }])
            ->get()
            ->map(function ($producer) {
                // Count unique tracks for sorting
                $uniqueTrackCount = $producer->tracks->unique('track_id')->count();
                $producer->unique_tracks_count = $uniqueTrackCount;
                return $producer;
            })
            ->sortByDesc('unique_tracks_count')
            ->take(6)
            ->values()
            ->map(function ($producer) use ($user, $startDate) {
                $totalMinutes = 0;
                $popularitySum = 0;
                $popularityCount = 0;
                $latestTrack = null;

                // Load all tracks for this producer played by this user
                $allTracks = $producer->tracks()
                    ->where('user_id', $user->id)
                    ->when($startDate, function ($query) use ($startDate) {
                        $query->where('played_at', '>=', $startDate);
                    })
                    ->get();
                
                // Group by track_id to get unique tracks
                $uniqueTracks = $allTracks->groupBy('track_id');
                $tracks = $allTracks; // Keep all tracks for duration calculation

                foreach ($tracks as $track) {
                    try {
                        $trackData = is_array($track->track_data) ? $track->track_data : json_decode($track->track_data, true);

                        // Handle different data structures for Spotify and Apple Music
                        $duration = 0;

                        if ($track->source === 'spotify') {
                            // Spotify-specific data extraction
                            $duration = $trackData['duration_ms'] ?? 0;

                            // Extract Spotify popularity directly from track_data
                            if (isset($trackData['popularity']) && is_numeric($trackData['popularity'])) {
                                $popularitySum += $trackData['popularity'];
                                $popularityCount++;
                            }
                        } elseif ($track->source === 'Apple Music') {
                            // Apple Music-specific duration extraction with improved structure handling
                            if (isset($trackData['attributes']['durationInMillis'])) {
                                $duration = $trackData['attributes']['durationInMillis'];
                            }
                            // For data wrapped in a 'data' array
                            elseif (isset($trackData['data'][0]['attributes']['durationInMillis'])) {
                                $duration = $trackData['data'][0]['attributes']['durationInMillis'];
                            }
                            // Fallback regex search
                            else {
                                $jsonString = json_encode($trackData);
                                if (preg_match('/"durationInMillis"\s*:\s*(\d+)/', $jsonString, $matches)) {
                                    $duration = intval($matches[1]);
                                }
                            }

                            // Extract popularity from popularity_data for Apple Music tracks
                            if ($track->popularity_data) {
                                try {
                                    $popularityData = is_array($track->popularity_data)
                                        ? $track->popularity_data
                                        : json_decode($track->popularity_data, true);

                                    // Check if popularity_data was successfully parsed and contains the popularity field
                                    if (is_array($popularityData) && isset($popularityData['popularity']) && is_numeric($popularityData['popularity'])) {
                                        $popularitySum += $popularityData['popularity'];
                                        $popularityCount++;
                                    } else {
                                        Log::debug('Missing or invalid popularity data structure', [
                                            'track_id' => $track->id,
                                            'popularity_data' => $track->popularity_data
                                        ]);
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Error processing popularity data', [
                                        'track_id' => $track->id,
                                        'error' => $e->getMessage(),
                                        'popularity_data' => $track->popularity_data
                                    ]);
                                }
                            }
                        }

                        $totalMinutes += $duration / 60000;

                        // Update latest track logic
                        if (!$latestTrack || $track->played_at > $latestTrack->played_at) {
                            $latestTrack = $track;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error processing track', [
                            'track_id' => $track->id,
                            'source' => $track->source,
                            'error' => $e->getMessage(),
                            'track_data' => isset($trackData) ? json_encode(array_slice((array)$trackData, 0, 3)) : 'No data'
                        ]);
                    }
                }

                $genres = $tracks->flatMap(fn($track) => $track->genres->pluck('name'))->unique()->values()->toArray();

                return [
                    'producer' => $producer,
                    'track_count' => $uniqueTracks->count(), // Count unique tracks
                    'total_minutes' => round($totalMinutes, 2),
                    'average_popularity' => $popularityCount > 0
                        ? round($popularitySum / $popularityCount, 2)
                        : 0,
                    'latest_track' => $latestTrack,
                    'genres' => $genres,
                ];
            });

        // Chart data
        $producerNames = $topProducers->pluck('producer.name');
        $producerTrackCounts = $topProducers->pluck('track_count');

        // Top genres
        $excludedGenres = ['Música', 'Music'];

        $genres = Genre::withCount(['tracks' => function ($query) use ($user, $startDate) {
            $query->where('user_id', $user->id);
            if ($startDate) {
                $query->where('played_at', '>=', $startDate);
            }
        }])
            ->whereNotIn('name', $excludedGenres)
            ->having('tracks_count', '>', 0)
            ->orderBy('tracks_count', 'desc')
            ->take(5)
            ->get();

        $genreNames = $genres->pluck('name');
        $genreCounts = $genres->pluck('tracks_count');

        return Inertia::render('Dashboard', [
            'totalTracks' => $totalTracks,
            'totalMinutes' => $totalMinutes,
            'totalProducers' => $totalProducers,
            'topProducers' => $topProducers,
            'history' => $history,
            'producerNames' => $producerNames,
            'producerTrackCounts' => $producerTrackCounts,
            'genreNames' => $genreNames,
            'genreCounts' => $genreCounts,
            'timeframe' => $timeframe,
            'todayTracks' => $todayTracks,
            'todayProducers' => $todayProducers,
        ]);
    }

    public function getProducerGraphData()
    {
        try {
            $user = Auth::user(); // Get the currently logged-in user
            $nodes = [];
            $links = [];

            // Fetch producers related to the logged-in user
            $producers = Producer::with(['tracks' => function ($query) use ($user) {
                $query->where('user_id', $user->id)->with('genres'); // Filter tracks by user ID and load genres
            }])->whereHas('tracks', function ($query) use ($user) {
                $query->where('user_id', $user->id); // Ensure producer has tracks associated with the user
            })->get();

            if ($producers->isEmpty()) {
                return response()->json(['error' => 'No producers found for this user.'], 404);
            }

            foreach ($producers as $producer) {
                // Add producer node
                $nodes[] = [
                    'id' => 'producer_' . $producer->id,
                    'label' => $producer->name,
                    'group' => 'producer',
                ];

                foreach ($producer->tracks as $track) {
                    // Add track node
                    $nodes[] = [
                        'id' => 'track_' . $track->id,
                        'label' => $track->track_name, // Assuming `track_name` is the field for the track name
                        'group' => 'track',
                    ];

                    // Link producer to track
                    $links[] = [
                        'source' => 'producer_' . $producer->id,
                        'target' => 'track_' . $track->id,
                        'value' => 1,
                    ];

                    foreach ($track->genres as $genre) {
                        // Skip "Música" and "Music" genres
                        if (in_array($genre->name, ['Música', 'Music'])) {
                            continue;
                        }

                        // Add genre node
                        $nodes[] = [
                            'id' => 'genre_' . $genre->id,
                            'label' => $genre->name,
                            'group' => 'genre',
                        ];

                        // Link track to genre
                        $links[] = [
                            'source' => 'track_' . $track->id,
                            'target' => 'genre_' . $genre->id,
                            'value' => 1,
                        ];
                    }
                }
            }

            // Deduplicate nodes
            $uniqueNodes = array_values(array_reduce($nodes, function ($carry, $node) {
                $carry[$node['id']] = $node;
                return $carry;
            }, []));

            return response()->json([
                'nodes' => $uniqueNodes,
                'links' => $links,
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating producer graph data: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to generate graph data.'], 500);
        }
    }


    public function createProducerPlaylist($producerId)
    {
        $user = Auth::user();
        $producer = Producer::findOrFail($producerId);

        // Fetch tracks for this producer that the user has listened to
        $tracks = $producer->tracks()->where('user_id', $user->id)->get();

        if ($tracks->isEmpty()) {
        return redirect()->back()->with('custom_alert', [
            'variant' => 'destructive',
            'icon' => 'error',
            'title' => 'Error!',
            'description' => 'No tracks found for this producer in your listening history.'
        ]);
        }

        // Create playlist name and description
        $playlistName = "Tracks produced by " . $producer->name;
        $playlistDescription = "A playlist of tracks worked on by " . $producer->name;

        // If user has Spotify token then handle Spotify playlist
        if ($user->spotify_token) {
            // Check if playlist already exists for this producer and user on Spotify
            $existingPlaylist = Playlist::where('user_id', $user->id)
                ->where('service', 'spotify')
                ->where('name', $playlistName)
                ->first();

            // Extract Spotify URIs
            $trackUris = [];
            $spotifyTracks = $tracks->where('source', 'spotify');

            foreach ($spotifyTracks as $track) {
                $trackData = is_array($track->track_data) ? $track->track_data : json_decode($track->track_data, true);
                if (isset($trackData['uri'])) {
                    $trackUris[] = $trackData['uri'];
                } else {
                    Log::warning('Spotify track URI missing', [
                        'track_id' => $track->id,
                        'track_name' => $track->track_name,
                        'track_data' => $trackData,
                    ]);
                }
            }

            if (empty($trackUris)) {
                return redirect()->back()->with('custom_alert', [
                    'variant' => 'destructive',
                    'icon' => 'error',
                    'title' => 'Error!',
                    'description' => 'No valid Spotify tracks available for this producer.'
                ]);
            }

            if ($existingPlaylist) {
                return $this->updateSpotifyPlaylist($user, $producer, $existingPlaylist, $trackUris);
            } else {
                return $this->createSpotifyPlaylist($user, $producer, $playlistName, $playlistDescription, $trackUris);
            }
        }
        // If user has Apple Music token then handle Apple Music playlist
        elseif ($user->apple_music_token) {
            // Check if playlist already exists for this producer and user on Apple Music
            $existingPlaylist = Playlist::where('user_id', $user->id)
                ->where('service', 'apple_music')
                ->where('name', $playlistName)
                ->first();

            // Extract Apple Music track IDs
            $appleMusicTrackIds = [];
            $appleMusicTracks = $tracks->where('source', 'Apple Music');

            foreach ($appleMusicTracks as $track) {
                $trackData = is_array($track->track_data) ? $track->track_data : json_decode($track->track_data, true);

                // Extract catalog ID from Apple Music data
                $catalogId = null;

                // Handle different data structures for Apple Music
                if (isset($trackData['id'])) {
                    $catalogId = $trackData['id'];
                } elseif (isset($trackData['data'][0]['id'])) {
                    $catalogId = $trackData['data'][0]['id'];
                } else {
                    // Try to find ID in the JSON structure
                    $jsonString = json_encode($trackData);
                    if (preg_match('/"id"\s*:\s*"([^"]+)"/', $jsonString, $matches)) {
                        $catalogId = $matches[1];
                    }
                }

                if ($catalogId) {
                    $appleMusicTrackIds[] = [
                        'id' => $catalogId,
                        'type' => 'songs'
                    ];
                } else {
                    Log::warning('Apple Music track ID missing', [
                        'track_id' => $track->id,
                        'track_name' => $track->track_name,
                        'track_data' => $trackData,
                    ]);
                }
            }

            if (empty($appleMusicTrackIds)) {
                return redirect()->back()->with('custom_alert', [
                    'variant' => 'destructive',
                    'icon' => 'error',
                    'title' => 'Error!',
                    'description' => 'No valid Apple Music tracks available for this producer.'
                ]);
            }

            if ($existingPlaylist) {
                return $this->updateAppleMusicPlaylist($user, $producer, $existingPlaylist, $appleMusicTrackIds);
            } else {
                return $this->createAppleMusicPlaylist($user, $producer, $playlistName, $playlistDescription, $appleMusicTrackIds);
            }
        }
        // No music service connected
        else {
            return redirect()->back()->with('custom_alert', [
                'variant' => 'destructive',
                'icon' => 'error',
                'title' => 'Error!',
                'description' => 'No music service connected. Please connect to Spotify or Apple Music.'
            ]);
        }
    }

    // Update existing Spotify playlist
    private function updateSpotifyPlaylist($user, $producer, $existingPlaylist, $newTrackUris)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $accessToken = $user->spotify_token;
            $playlistId = $existingPlaylist->spotify_id;

            // Get current tracks in the playlist
            $response = $client->get("https://api.spotify.com/v1/playlists/{$playlistId}/tracks", [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            ]);

            // Handle token expiration
            if ($response->getStatusCode() === 401) {
                Log::warning('Spotify token expired. Attempting to refresh token.');
                $this->refreshSpotifyToken($user);
                $accessToken = $user->spotify_token;

                $response = $client->get("https://api.spotify.com/v1/playlists/{$playlistId}/tracks", [
                    'headers' => ['Authorization' => 'Bearer ' . $accessToken],
                ]);
            }

            $currentTracksData = json_decode($response->getBody(), true);
            $currentTrackUris = array_map(function($item) {
                return $item['track']['uri'];
            }, $currentTracksData['items']);

            // Find tracks that need to be added (not already in playlist)
            $tracksToAdd = array_diff($newTrackUris, $currentTrackUris);

            if (empty($tracksToAdd)) {
                return redirect()->back()->with('custom_alert', [
                    'variant' => 'info',
                    'icon' => 'info',
                    'title' => 'Info',
                    'description' => 'All tracks are already in the existing playlist for ' . $producer->name . '!'
                ]);
            }

            // Add new tracks to the existing playlist
            $addTracksResponse = $client->post("https://api.spotify.com/v1/playlists/{$playlistId}/tracks", [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
                'json' => ['uris' => array_values($tracksToAdd)],
            ]);

            if ($addTracksResponse->getStatusCode() !== 201) {
                Log::error('Failed to add tracks to existing Spotify playlist', [
                    'playlist_id' => $playlistId,
                    'track_uris' => $tracksToAdd,
                    'response' => $addTracksResponse->getBody()->getContents(),
                ]);
                return redirect()->back()->with('custom_alert', [
                    'variant' => 'destructive',
                    'icon' => 'error',
                    'title' => 'Error!',
                    'description' => 'Failed to add new tracks to the existing playlist.'
                ]);
            }

            return redirect()->back()->with('custom_alert', [
                'variant' => 'success',
                'icon' => 'check',
                'title' => 'Success!',
                'description' => 'Added ' . count($tracksToAdd) . ' new tracks to existing Spotify playlist for ' . $producer->name . '!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating Spotify playlist', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('custom_alert', [
                'variant' => 'destructive',
                'icon' => 'error',
                'title' => 'Error!',
                'description' => 'An error occurred while updating the Spotify playlist: ' . $e->getMessage()
            ]);
        }
    }

    // Update existing Apple Music playlist
    private function updateAppleMusicPlaylist($user, $producer, $existingPlaylist, $newTrackIds)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $playlistId = $existingPlaylist->apple_music_id;

            // Get current tracks in the playlist
            $response = $client->get("https://api.music.apple.com/v1/me/library/playlists/{$playlistId}/tracks", [
                'headers' => [
                    'Authorization' => "Bearer {$this->developerToken}",
                    'Music-User-Token' => $user->apple_music_token,
                    'Content-Type' => 'application/json'
                ]
            ]);

            $currentTracksData = json_decode($response->getBody(), true);
            $currentTrackIds = [];

            if (isset($currentTracksData['data'])) {
                $currentTrackIds = array_map(function($item) {
                    // FIXED: Use catalogId instead of library id
                    return $item['attributes']['playParams']['catalogId'];
                }, $currentTracksData['data']);
            }

            // Find tracks that need to be added (not already in playlist)
            $newTrackIdsFlat = array_map(function($track) {
                return $track['id'];
            }, $newTrackIds);

            // Only add tracks that are NOT already in the playlist
            $tracksToAdd = [];
            foreach ($newTrackIdsFlat as $trackId) {
                if (!in_array($trackId, $currentTrackIds)) {
                    $tracksToAdd[] = $trackId;
                }
            }

            // Updated logging
            Log::info('Apple Music playlist update', [
                'total_new_tracks' => count($newTrackIdsFlat),
                'current_playlist_tracks' => count($currentTrackIds),
                'tracks_to_add' => count($tracksToAdd),
                'current_catalog_ids' => $currentTrackIds,
                'new_track_ids' => $newTrackIdsFlat,
                'tracks_to_add_ids' => $tracksToAdd
            ]);

            if (empty($tracksToAdd)) {
                return redirect()->back()->with('custom_alert', [
                    'variant' => 'info',
                    'icon' => 'info',
                    'title' => 'Info',
                    'description' => 'All tracks are already in the existing playlist for ' . $producer->name . '!'
                ]);
            }

            // Convert back to the format needed for Apple Music API
            $tracksToAddFormatted = array_map(function($trackId) {
                return [
                    'id' => $trackId,
                    'type' => 'songs'
                ];
            }, $tracksToAdd);

            // Add new tracks to the existing playlist
            $addTracksResponse = $client->post("https://api.music.apple.com/v1/me/library/playlists/{$playlistId}/tracks", [
                'headers' => [
                    'Authorization' => "Bearer {$this->developerToken}",
                    'Music-User-Token' => $user->apple_music_token,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'data' => $tracksToAddFormatted
                ]
            ]);

            return redirect()->back()->with('custom_alert', [
                'variant' => 'success',
                'icon' => 'check',
                'title' => 'Success!',
                'description' => 'Added ' . count($tracksToAdd) . ' new tracks to existing Apple Music playlist for ' . $producer->name . '!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating Apple Music playlist', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('custom_alert', [
                'variant' => 'destructive',
                'icon' => 'error',
                'title' => 'Error!',
                'description' => 'An error occurred while updating the Apple Music playlist: ' . $e->getMessage()
            ]);
        }
    }

    // Playlist for spotify
    private function createSpotifyPlaylist($user, $producer, $playlistName, $playlistDescription, $trackUris)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $accessToken = $user->spotify_token;

            // Create the playlist
            $response = $client->post('https://api.spotify.com/v1/users/' . $user->spotify_id . '/playlists', [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
                'json' => [
                    'name' => $playlistName,
                    'description' => $playlistDescription,
                    'public' => true,
                ],
            ]);

            // If the token has expired, refresh it and retry
            if ($response->getStatusCode() === 401) {
                Log::warning('Spotify token expired. Attempting to refresh token.');
                $this->refreshSpotifyToken($user);

                // Retry with the new token
                $accessToken = $user->spotify_token;
                $response = $client->post('https://api.spotify.com/v1/users/' . $user->spotify_id . '/playlists', [
                    'headers' => ['Authorization' => 'Bearer ' . $accessToken],
                    'json' => [
                        'name' => $playlistName,
                        'description' => $playlistDescription,
                        'public' => true,
                    ],
                ]);
            }

            $playlistData = json_decode($response->getBody(), true);
            $playlistId = $playlistData['id'];

            // Add tracks to the playlist
            $addTracksResponse = $client->post("https://api.spotify.com/v1/playlists/{$playlistId}/tracks", [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
                'json' => ['uris' => $trackUris],
            ]);

            if ($addTracksResponse->getStatusCode() !== 201) {
                Log::error('Failed to add tracks to Spotify playlist', [
                    'playlist_id' => $playlistId,
                    'track_uris' => $trackUris,
                    'response' => $addTracksResponse->getBody()->getContents(),
                ]);
                return redirect()->back()->with('custom_alert', [
                    'variant' => 'destructive',
                    'icon' => 'error',
                    'title' => 'Error!',
                    'description' => 'Failed to add tracks to the playlist.'
                ]);
            }

            // Save the playlist to the database
            Playlist::create([
                'user_id' => $user->id,
                'spotify_id' => $playlistId,
                'name' => $playlistName,
                'description' => $playlistData['description'] ?? null,
                'service' => 'spotify',
            ]);

            return redirect()->back()->with('custom_alert', [
                'variant' => 'success',
                'icon' => 'check',
                'title' => 'Success!',
                'description' => 'Spotify playlist created successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating Spotify playlist', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('custom_alert', [
                'variant' => 'destructive',
                'icon' => 'error',
                'title' => 'Error!',
                'description' => 'An error occurred while creating the Spotify playlist: ' . $e->getMessage()
            ]);
        }
    }

    // Playlist for apple
    private function createAppleMusicPlaylist($user, $producer, $playlistName, $playlistDescription, $trackIds)
    {
        try {
            $client = new \GuzzleHttp\Client();

            // Step 1: Create the playlist in Apple Music with isPublic set to true
            $response = $client->post('https://api.music.apple.com/v1/me/library/playlists', [
                'headers' => [
                    'Authorization' => "Bearer {$this->developerToken}",
                    'Music-User-Token' => $user->apple_music_token,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'attributes' => [
                        'name' => $playlistName,
                        'description' => $playlistDescription,
                        'isPublic' => true // Set the playlist to public to get a globalId
                    ]
                ]
            ]);

            $playlistData = json_decode($response->getBody(), true);
            Log::info('Apple Music playlist creation response', ['response' => $playlistData]);

            // Extract the new playlist ID
            if (isset($playlistData['data']) && is_array($playlistData['data']) && !empty($playlistData['data'])) {
                $playlistId = $playlistData['data'][0]['id'];

                // Step 2: Add tracks to the playlist
                $addTracksResponse = $client->post("https://api.music.apple.com/v1/me/library/playlists/{$playlistId}/tracks", [
                    'headers' => [
                        'Authorization' => "Bearer {$this->developerToken}",
                        'Music-User-Token' => $user->apple_music_token,
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        'data' => $trackIds
                    ]
                ]);

                // Step 3: Fetch the playlist details to get the global ID
                $playlistDetailsResponse = $client->get("https://api.music.apple.com/v1/me/library/playlists/{$playlistId}", [
                    'headers' => [
                        'Authorization' => "Bearer {$this->developerToken}",
                        'Music-User-Token' => $user->apple_music_token,
                        'Content-Type' => 'application/json'
                    ]
                ]);

                $playlistDetails = json_decode($playlistDetailsResponse->getBody(), true);

                // Log the response for debugging
                Log::info('Apple Music playlist details response', [
                    'response' => json_encode($playlistDetails)
                ]);

                // Extract the global ID - should now exist since we set isPublic to true
                $globalId = null;
                if (isset($playlistDetails['data'][0]['attributes']['playParams']['globalId'])) {
                    $globalId = $playlistDetails['data'][0]['attributes']['playParams']['globalId'];
                    Log::info('Found Apple Music global ID', ['globalId' => $globalId]);
                } else {
                    Log::warning('Global ID still not found in response', [
                        'isPublic' => $playlistDetails['data'][0]['attributes']['isPublic'] ?? 'unknown'
                    ]);
                }

                // Save the playlist to the database
                $playlist = Playlist::create([
                    'user_id' => $user->id,
                    'spotify_id' => null,
                    'apple_music_id' => $playlistId,
                    'apple_music_global_id' => $globalId,
                    'name' => $playlistName,
                    'description' => $playlistDescription,
                    'service' => 'apple_music'
                ]);

                return redirect()->back()->with('custom_alert', [
                    'variant' => 'success',
                    'icon' => 'check',
                    'title' => 'Success!',
                    'description' => 'Apple Music playlist created successfully!'
                ]);
            }

            return redirect()->back()->with('custom_alert', [
                'variant' => 'destructive',
                'icon' => 'error',
                'title' => 'Error!',
                'description' => 'Failed to create the Apple Music playlist.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating Apple Music playlist', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('custom_alert', [
                'variant' => 'destructive',
                'icon' => 'error',
                'title' => 'Error!',
                'description' => 'An error occurred while creating the Apple Music playlist: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show playlists created by the user through this website
     */
    public function showPlaylists()
    {
        $user = Auth::user();

        // Get playlists from the database that belong to this user
        $playlists = Playlist::where('user_id', $user->id)->get();

        // If no playlists found
        if ($playlists->isEmpty()) {
            return view('playlists.index', ['playlists' => collect()]);
        }

        // For each playlist, fetch its details from the corresponding service
        foreach ($playlists as $playlist) {
            // Set default values
            $playlist->image_url = 'https://via.placeholder.com/150';
            $playlist->track_count = 0;
            $playlist->external_url = '#';

            try {
                // For Spotify playlists
                if ($playlist->service === 'spotify' && $user->spotify_token && $playlist->spotify_id) {
                    $response = Http::withToken($user->spotify_token)
                        ->get("https://api.spotify.com/v1/playlists/{$playlist->spotify_id}");

                    // Handle token expiration
                    if ($response->status() === 401) {
                        $tokenResponse = Http::asForm()->post('https://accounts.spotify.com/api/token', [
                            'grant_type' => 'refresh_token',
                            'refresh_token' => $user->spotify_refresh_token,
                            'client_id' => env('SPOTIFY_CLIENT_ID'),
                            'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
                        ]);

                        $tokenData = $tokenResponse->json();
                        if (isset($tokenData['access_token'])) {
                            DB::table('users')->where('id', $user->id)->update([
                                'spotify_token' => $tokenData['access_token'],
                                'updated_at' => now(),
                            ]);

                            // Retry with new token
                            $response = Http::withToken($tokenData['access_token'])
                                ->get("https://api.spotify.com/v1/playlists/{$playlist->spotify_id}");
                        }
                    }

                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['images'][0]['url'])) {
                            $playlist->image_url = $data['images'][0]['url'];
                        }
                        if (isset($data['tracks']['total'])) {
                            $playlist->track_count = $data['tracks']['total'];
                        }
                        if (isset($data['external_urls']['spotify'])) {
                            $playlist->external_url = $data['external_urls']['spotify'];
                        }
                    }
                }

                // For Apple Music playlists
                elseif ($playlist->service === 'apple_music' && $user->apple_music_token && $playlist->apple_music_id) {
                                    $response = Http::withHeaders([
                                        'Authorization' => "Bearer {$this->developerToken}",
                                        'Music-User-Token' => $user->apple_music_token
                                    ])->get("https://api.music.apple.com/v1/me/library/playlists/{$playlist->apple_music_id}");

                                    if ($response->successful()) {
                                        $data = $response->json();
                                        if (isset($data['data'][0]['attributes']['artwork']['url'])) {
                                            $artworkUrl = $data['data'][0]['attributes']['artwork']['url'];
                                            // Replace width and height placeholders
                                            $playlist->image_url = str_replace('{w}x{h}', '300x300', $artworkUrl);
                                        }
                                        if (isset($data['data'][0]['relationships']['tracks']['meta']['total'])) {
                                            $playlist->track_count = $data['data'][0]['relationships']['tracks']['meta']['total'];
                                        }
                                        if (isset($data['data'][0]['attributes']['playParams']['globalId'])) {
                                            $globalId = $data['data'][0]['attributes']['playParams']['globalId'];
                                            $playlist->external_url = "https://music.apple.com/playlist/{$globalId}";

                                        }
                                    }
                }
            } catch (\Exception $e) {
                Log::error('Error fetching playlist details: ' . $e->getMessage(), [
                    'playlist_id' => $playlist->id,
                    'service' => $playlist->service
                ]);
            }
        }

        return view('playlists.index', ['playlists' => $playlists]);
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

    public function showAllProducers(Request $request): Response
    {
        set_time_limit(120); // Quick fix: increase time limit to 2 minutes
        $user = Auth::user();

        // Get processed producers with filters
        $producersData = $this->processProducers($user, $request);

          // Get user's favourited producers
        $favouritesRaw = $user->favouriteProducers()->get();
        $favouriteProducers = $this->processProducers($user, $request, $favouritesRaw);

        // For charts
        $start = $request->query('start');
        $end = $request->query('end');
        $range = $request->query('range', 'all');

        $topProducersByRange = match ($range) {
            'today' => $this->getTopProducersByRange(Carbon::now()->startOfDay(), Carbon::now()->endOfDay()),
            'week' => $this->getTopWeeklyProducers(),
            'last7' => $this->getTopProducersByRange(Carbon::now()->subDays(7), Carbon::now()),
            'custom' => ($start && $end)
                ? $this->getTopProducersByRange(Carbon::parse($start), Carbon::parse($end))
                : collect(),
            default => $this->getTopProducers(),
        };

        // Get all genres for filters
        $allGenres = Genre::select('genres.name')
            ->join('genre_track', 'genres.id', '=', 'genre_track.genre_id')
            ->join('listening_history', 'genre_track.listening_history_id', '=', 'listening_history.id')
            ->where('listening_history.user_id', $user->id)
            ->distinct()
            ->orderBy('genres.name')
            ->pluck('name');

        return Inertia::render('Producers', [
            'producersData' => $producersData,
            'favouriteProducers' => $favouriteProducers,
            'selectedGenre' => $request->get('genre'),
            'genres' => $allGenres,
            'searchQuery' => $request->get('search', ''),
            'filters' => [
                'sort_by' => $request->get('sort_by', 'track_count'),
                'sort_order' => $request->get('sort_order', 'desc'),
                'min_tracks' => $request->get('min_tracks'),
                'min_popularity' => $request->get('min_popularity'),
            ],
            'topProducers' => $this->getTopProducers(),
            'weeklyTopProducers' => $this->getTopWeeklyProducers(),
            'topProducersByRange' => $topProducersByRange,
            'range' => $range,
        ]);
    }

    private function processProducers(User $user, Request $request, $preFilteredProducers = null)
    {
        $searchQuery = $request->get('search', '');
        $genreFilter = $request->get('genre');
        $sortBy = $request->get('sort_by', 'track_count');
        $sortOrder = $request->get('sort_order', 'desc');
        $minTracks = $request->get('min_tracks');
        $minPopularity = $request->get('min_popularity');

        // If we're processing a predefined set (like favourites)
        if ($preFilteredProducers) {
            $producersQuery = Producer::whereIn('id', $preFilteredProducers->pluck('id'));
        } else {
            // Normal full query
            $producersQuery = Producer::select('producers.*')
                ->join('producer_track', 'producers.id', '=', 'producer_track.producer_id')
                ->join('listening_history', 'producer_track.listening_history_id', '=', 'listening_history.id')
                ->when($genreFilter, function ($query, $genre) {
                    $query->join('genre_track', 'listening_history.id', '=', 'genre_track.listening_history_id')
                        ->join('genres', 'genre_track.genre_id', '=', 'genres.id')
                        ->where('genres.name', $genre);
                })
                ->where('listening_history.user_id', $user->id)
                ->when($searchQuery, function ($query) use ($searchQuery) {
                    $query->where('producers.name', 'like', "%{$searchQuery}%");
                })
                ->groupBy(
                    'producers.id',
                    'producers.name',
                    'producers.spotify_id',
                    'producers.discogs_id',
                    'producers.image_url',
                    'producers.created_at',
                    'producers.updated_at'
                );
        }

        $producersData = $producersQuery
            ->withCount(['tracks' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->having('tracks_count', '>', 0)
            ->when($minTracks, function ($query) use ($minTracks) {
                $query->having('tracks_count', '>=', $minTracks);
            })
            ->limit(500) // Reasonable limit to prevent memory issues while still showing all relevant producers
            ->get()
            ->map(function ($producer) use ($user) {
                $allTracks = $producer->tracks()->where('user_id', $user->id)->get();
                // Group by track_id to get unique tracks for counting
                $uniqueTracks = $allTracks->groupBy('track_id');
                $tracks = $allTracks; // Keep all tracks for duration calculation

                $totalMinutes = 0;
                $popularitySum = 0;
                $popularityCount = 0;
                $latestTrack = null;

                foreach ($tracks as $track) {
                    try {
                        $trackData = is_array($track->track_data) ? $track->track_data : json_decode($track->track_data, true);
                        $duration = 0;

                        if ($track->source === 'spotify') {
                            $duration = $trackData['duration_ms'] ?? 0;
                            if (isset($trackData['popularity']) && is_numeric($trackData['popularity'])) {
                                $popularitySum += $trackData['popularity'];
                                $popularityCount++;
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
                                        $popularitySum += $popularityData['popularity'];
                                        $popularityCount++;
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Error processing popularity data', [
                                        'track_id' => $track->id,
                                        'error' => $e->getMessage(),
                                        'popularity_data' => $track->popularity_data
                                    ]);
                                }
                            }
                        }

                        $totalMinutes += $duration / 60000;

                        if (!$latestTrack || $track->played_at > $latestTrack->played_at) {
                            $latestTrack = $track;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error processing track', [
                            'track_id' => $track->id,
                            'source' => $track->source,
                            'error' => $e->getMessage(),
                            'track_data' => isset($trackData) ? json_encode(array_slice((array)$trackData, 0, 3)) : 'No data'
                        ]);
                    }
                }

                $genres = $tracks->flatMap(fn($track) => $track->genres->pluck('name'))->unique()->take(10);
                $averagePopularity = $popularityCount > 0 ? round($popularitySum / $popularityCount, 2) : 0;

                return [
                    'producer' => $producer,
                    'track_count' => $uniqueTracks->count(), // Count unique tracks
                    'total_minutes' => round($totalMinutes, 2),
                    'average_popularity' => $averagePopularity,
                    'latest_track' => $latestTrack,
                    'genres' => $genres->values(),
                    'latest_played_at' => $latestTrack ? $latestTrack->played_at : null,
                ];
            })
            ->when($minPopularity, function ($collection) use ($minPopularity) {
                return $collection->filter(function ($item) use ($minPopularity) {
                    return $item['average_popularity'] >= $minPopularity;
                });
            })
            ->sortBy(function ($item) use ($sortBy, $sortOrder) {
                $value = match($sortBy) {
                    'name' => strtolower($item['producer']->name),
                    'popularity' => $item['average_popularity'],
                    'recent' => $item['latest_played_at'] ? strtotime($item['latest_played_at']) : 0,
                    'track_count' => $item['track_count'],
                    default => $item['track_count']
                };

                return $sortOrder === 'desc' ? -$value : $value;
            })
            ->values();

        // Manual Pagination
        $page = (int)$request->get('page', 1);
        $perPage = 15;
        $total = $producersData->count();
        $items = $producersData->slice(($page - 1) * $perPage, $perPage)->values();

        $lastPage = (int)ceil($total / $perPage);
        $paginationMeta = [
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
            'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : null,
            'to' => $total > 0 ? min($page * $perPage, $total) : null,
            'prev_page_url' => $page > 1 ? route('producers', array_merge($request->all(), ['page' => $page - 1])) : null,
            'next_page_url' => $page < $lastPage ? route('producers', array_merge($request->all(), ['page' => $page + 1])) : null,
            'links' => $this->generatePaginationLinks($page, $lastPage, $request->all()),
        ];

        return [
            'data' => $items,
            'meta' => $paginationMeta,
        ];
    }

    private function getTopProducers()
    {
        return Producer::select(
                'producers.id',
                'producers.name',
                'producers.image_url',
                DB::raw('COUNT(DISTINCT listening_history.track_id) as unique_track_count'),
                DB::raw('COUNT(listening_history.id) as total_play_count')
            )
            ->join('producer_track', 'producers.id', '=', 'producer_track.producer_id')
            ->join('listening_history', 'producer_track.listening_history_id', '=', 'listening_history.id')
            ->groupBy(
                'producers.id',
                'producers.name',
                'producers.image_url'
            )
            ->orderByDesc('unique_track_count')
            ->orderByDesc('total_play_count')
            ->limit(10)
            ->get();
    }

    private function getTopWeeklyProducers()
    {
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        return DB::table('producer_track')
            ->join('listening_history', 'producer_track.listening_history_id', '=', 'listening_history.id')
            ->join('producers', 'producer_track.producer_id', '=', 'producers.id')
            ->whereBetween('listening_history.played_at', [$startOfWeek, $endOfWeek])
            ->select(
                'producers.id',
                'producers.name',
                'producers.image_url', 
                DB::raw('COUNT(DISTINCT listening_history.track_id) as unique_track_count'),
                DB::raw('COUNT(*) as total_play_count')
            )
            ->groupBy('producers.id', 'producers.name', 'producers.image_url')
            ->orderByDesc('unique_track_count')
            ->orderByDesc('total_play_count')
            ->limit(10)
            ->get();
    }

    private function getTopProducersByRange(?string $start, ?string $end)
    {
        if (!$start || !$end) {
            return collect();
        }

        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->endOfDay(); // ensure full day is covered

        return DB::table('producer_track')
            ->join('listening_history', 'producer_track.listening_history_id', '=', 'listening_history.id')
            ->join('producers', 'producer_track.producer_id', '=', 'producers.id')
            ->whereBetween('listening_history.played_at', [$startDate, $endDate])
            ->select(
                'producers.id',
                'producers.name',
                'producers.image_url',
                DB::raw('COUNT(DISTINCT listening_history.track_id) as unique_track_count'),
                DB::raw('COUNT(*) as total_play_count')
            )
            ->groupBy('producers.id', 'producers.name', 'producers.image_url')
            ->orderByDesc('unique_track_count')
            ->orderByDesc('total_play_count')
            ->limit(10)
            ->get();
    }

    private function generatePaginationLinks($currentPage, $lastPage, $params)
    {
        $links = [];
        $maxLinks = 5; // Show 5 page links at most

        // Previous link
        $links[] = [
            'url' => $currentPage > 1 ? route('producers', array_merge($params, ['page' => $currentPage - 1])) : null,
            'label' => '&laquo; Previous',
            'active' => false,
        ];

        // Page number links
        $start = max(1, $currentPage - 2);
        $end = min($lastPage, $currentPage + 2);

        for ($i = $start; $i <= $end; $i++) {
            $links[] = [
                'url' => route('producers', array_merge($params, ['page' => $i])),
                'label' => (string)$i,
                'active' => $i === $currentPage,
            ];
        }

        // Next link
        $links[] = [
            'url' => $currentPage < $lastPage ? route('producers', array_merge($params, ['page' => $currentPage + 1])) : null,
            'label' => 'Next &raquo;',
            'active' => false,
        ];

        return $links;
    }
}
