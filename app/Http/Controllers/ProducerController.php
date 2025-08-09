<?php

namespace App\Http\Controllers;
use App\Models\Producer;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\ListeningHistory;
Use App\Models\User;
Use App\Models\Genre;
use App\Models\Playlist;
use App\Models\ArtistImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Inertia\Response;
use Illuminate\Support\Str;
use App\Traits\HandlesProducerInteractions;


use Illuminate\Http\Request;

class ProducerController extends Controller
{
    use HandlesProducerInteractions;

    protected $developerToken;
    protected $openAiApiKey;

    public function __construct()
    {
        $this->developerToken = config('services.apple.client_secret');
        $this->openAiApiKey = config('services.openai.api_key');
    }

    private function collaborators(Producer $producer)
    {
        $user = Auth::user();
        // Get all track IDs the producer has worked on
        $trackIds = $producer->tracks()->where('user_id', $user->id)->pluck('listening_history_id');

        // Find collaborators: producers who worked on the same tracks, excluding the current producer
        $collaborators = Producer::whereHas('tracks', function ($q) use ($trackIds) {
            $q->whereIn('listening_history_id', $trackIds);
        })
        ->where('id', '!=', $producer->id)
        ->withCount(['tracks as collaboration_count' => function ($q) use ($trackIds) {
            $q->whereIn('listening_history_id', $trackIds);
        }])
        ->distinct()
        ->get();

        // Enrich collaborator data with additional fields
        return $this->enrichProducerData($collaborators, $user);
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            
            // Enhanced logging for debugging
            Log::info('Producer show method called', [
                'producer_id' => $id,
                'user_id' => $user->id,
                'has_apple_music' => !empty($user->apple_music_token),
                'has_spotify' => !empty($user->spotify_token),
                'source' => !empty($user->apple_music_token) ? 'Apple Music' : 'Spotify'
            ]);

            $producer = Producer::whereHas('tracks', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->with(['tracks' => function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orderBy('played_at', 'desc');
            }, 'tracks.genres',
                'tracks.producers'])
            ->withCount('followers')
            ->findOrFail($id);
            
            Log::info('Producer loaded successfully', [
                'producer_id' => $producer->id,
                'producer_name' => $producer->name,
                'tracks_count' => $producer->tracks->count()
            ]);

        // Get all plays for tracks by this producer
        $allPlays = $producer->tracks;

        // Group by track_id to get unique tracks with play counts
        $uniqueTracks = $allPlays->groupBy('track_id')->map(function ($plays) {
            $firstPlay = $plays->first();
            return [
                'track' => $firstPlay,
                'play_count' => $plays->count(),
                'first_played_at' => $plays->min('played_at'),
                'last_played_at' => $plays->max('played_at'),
                'plays' => $plays // Keep all plays for detailed view if needed
            ];
        });

        // For backward compatibility, create a collection of tracks
        $tracks = $uniqueTracks->pluck('track');

        $userGenres = $tracks->flatMap(fn($track) => $track->genres->pluck('name'))->unique()->toArray();

        try {
            $recommendedTracks = $this->getInternalRecommendations($user, $producer);

            if ($recommendedTracks->isEmpty()) {
                $recommendedTracks = $this->getTracksFromCollaborators($user, $producer);
            }

            if ($recommendedTracks->isEmpty()) {
                $recommendedTracks = $this->getGenreBasedRecommendations($user, $producer);
            }

            if ($recommendedTracks->isEmpty()) {
                Log::info('Getting external recommendations', [
                    'producer' => $producer->name,
                    'source' => isset($user->apple_music_token) ? 'Apple Music' : 'Spotify'
                ]);
                
                $recommendedTracks = isset($user->apple_music_token)
                    ? $this->getAppleMusicRecommendations($producer->name, $userGenres, $user)
                    : $this->getSpotifyRecommendations($producer->name, $userGenres, $user);
            }
        } catch (\Exception $e) {
            Log::error('Error getting recommendations', [
                'producer_id' => $producer->id,
                'error' => $e->getMessage()
            ]);
            $recommendedTracks = collect();
        }
        
        $genres = $tracks->flatMap(fn($track) => $track->genres)->unique('id');

        $similarProducers = Producer::where('id', '!=', $id)
            ->whereHas('tracks', fn($query) => $query->where('user_id', $user->id))
            ->whereHas('tracks.genres', fn($query) => $query->whereIn('genres.id', $genres->pluck('id')))
            ->limit(6)
            ->get();
        
        // Enrich similar producers with additional data
        $similarProducers = $this->enrichProducerData($similarProducers, $user);

        // Calculate total minutes from ALL plays (not just unique tracks)
        $totalMinutes = $allPlays->sum(function ($track) {
            try {
                $trackData = is_array($track->track_data) ? $track->track_data : json_decode($track->track_data, true);
                
                if (!is_array($trackData)) {
                    return 0;
                }
                
                $durationMs = 0;
                
                if ($track->source === 'spotify') {
                    $durationMs = $trackData['duration_ms'] ?? 0;
                } elseif (strtolower($track->source) === 'apple music') {
                    // Check multiple possible locations for Apple Music duration
                    if (isset($trackData['attributes']['durationInMillis'])) {
                        $durationMs = $trackData['attributes']['durationInMillis'];
                    } elseif (isset($trackData['duration_ms'])) {
                        $durationMs = $trackData['duration_ms'];
                    } elseif (isset($trackData['duration'])) {
                        $durationMs = $trackData['duration'];
                    } else {
                        // Try direct access to duration_ms column if available
                        $durationMs = $track->duration_ms ?? 0;
                    }
                }
                
                return round($durationMs / 60000, 2);
            } catch (\Exception $e) {
                Log::error('Error calculating track duration', [
                    'track_id' => $track->id,
                    'source' => $track->source,
                    'error' => $e->getMessage()
                ]);
                return 0;
            }
        });

        $averagePopularity = round($tracks->avg(function ($track) {
            try {
                $trackData = is_array($track->track_data) ? $track->track_data : json_decode($track->track_data, true);
                
                if ($track->source === 'spotify') {
                    return $trackData['popularity'] ?? 0;
                } elseif (strtolower($track->source) === 'apple music' && $track->popularity_data) {
                    $popularityData = is_array($track->popularity_data) ? $track->popularity_data : json_decode($track->popularity_data, true);
                    return $popularityData['popularity'] ?? 0;
                }
                
                return 0;
            } catch (\Exception $e) {
                return 0;
            }
        }) ?: 0);

        $durationByGenre = $genres->mapWithKeys(function ($genre) use ($tracks) {
            $total = $tracks->filter(fn($track) =>
                $track->genres->contains('id', $genre->id)
            )->sum(function ($track) {
                try {
                    $data = is_array($track->track_data) ? $track->track_data : json_decode($track->track_data, true);
                    
                    if (!is_array($data)) {
                        return 0;
                    }
                    
                    if ($track->source === 'spotify') {
                        return $data['duration_ms'] ?? 0;
                    } elseif (strtolower($track->source) === 'apple music') {
                        // Check multiple possible locations
                        if (isset($data['attributes']['durationInMillis'])) {
                            return $data['attributes']['durationInMillis'];
                        } elseif (isset($data['duration_ms'])) {
                            return $data['duration_ms'];
                        } elseif (isset($data['duration'])) {
                            return $data['duration'];
                        }
                        // Fallback to duration_ms column
                        return $track->duration_ms ?? 0;
                    }
                    
                    return 0;
                } catch (\Exception $e) {
                    return 0;
                }
            });

            return [$genre->name => round($total / 60000, 2)];
        });

        $popularityData = $tracks->map(function ($track) {
            try {
                if ($track->source === 'spotify') {
                    $data = is_array($track->track_data) ? $track->track_data : json_decode($track->track_data, true);
                    return $data['popularity'] ?? null;
                } elseif (Str::lower($track->source) === 'apple music' && $track->popularity_data) {
                    $data = is_array($track->popularity_data) ? $track->popularity_data : json_decode($track->popularity_data, true);
                    return $data['popularity'] ?? null;
                }
            } catch (\Exception $e) {
                return null;
            }

            return null;
        })->filter()->values();

        $sevenDaysAgo = now()->subDays(6)->startOfDay();
        $today = now()->endOfDay();


        // Get all listening history entries for this user and producer within the past 7 days
        $listeningHistory = ListeningHistory::where('user_id', $user->id)
            ->whereBetween('played_at', [$sevenDaysAgo, $today])
            ->whereHas('producers', function ($query) use ($producer) {
                $query->where('producers.id', $producer->id);
            })
            ->get();

        // Group by day and calculate duration from ALL plays
        $weeklyListeningData = collect(range(0, 6))->map(function ($i) use ($listeningHistory) {
            $date = now()->subDays(6 - $i)->toDateString();

            $dayTracks = $listeningHistory->filter(function ($track) use ($date) {
                return \Carbon\Carbon::parse($track->played_at)->toDateString() === $date;
            });

            $totalMs = $dayTracks->sum(function ($track) {
                // Convert to array if stored as JSON string
                $data = is_string($track->track_data)
                    ? json_decode($track->track_data, true)
                    : $track->track_data;

                // Fallback if decoding fails or data is null
                if (!is_array($data)) {
                    return 0;
                }

                if ($track->source === 'spotify') {
                    return $data['duration_ms'] ?? 0;
                } elseif (strtolower($track->source) === 'apple music') {
                    // Check multiple possible locations for Apple Music duration
                    if (isset($data['attributes']['durationInMillis'])) {
                        return $data['attributes']['durationInMillis'];
                    } elseif (isset($data['duration_ms'])) {
                        return $data['duration_ms'];
                    } elseif (isset($data['duration'])) {
                        return $data['duration'];
                    }
                    // Fallback to duration_ms column
                    return $track->duration_ms ?? 0;
                }
                return 0;
            });

            return [
                'day' => \Carbon\Carbon::parse($date)->format('D'),
                'duration' => round($totalMs / 60000, 2), // convert ms to minutes
            ];
        });

        $collaborators = $this->collaborators($producer);
        $artistCollaborators = $this->artistCollaborators($producer);

        $soloTracks = $uniqueTracks->filter(function ($item) {
            return empty(trim($item['track']->artist_name));
        })->count();

        $artistCollabCount = count($artistCollaborators);
        $producerCollabCount = count($collaborators);

        $collaborationBreakdown = [
            'Solo' => $soloTracks,
            'Artist Collabs' => $artistCollabCount,
            'Producer Collabs' => $producerCollabCount,
        ];

        // Prepare producer data with unique tracks
        $producerData = $producer->toArray();
        $producerData['tracks'] = $uniqueTracks->map(function ($item) {
            return array_merge(
                $item['track']->toArray(),
                [
                    'play_count' => $item['play_count'],
                    'first_played_at' => $item['first_played_at'],
                    'last_played_at' => $item['last_played_at']
                ]
            );
        })->values()->toArray();
        
        return Inertia::render('Show', [
            'producer' => $producerData,
            'producerImage' => $producer->image_url,
            'stats' => [
                'total_minutes' => $totalMinutes,
                'average_popularity' => $averagePopularity,
                'durationByGenre' => $durationByGenre,
                'popularityDistribution' => $popularityData,
                'weeklyListeningData' => $weeklyListeningData,
                'collaborationBreakdown' => $collaborationBreakdown,
            ],
            'totalTrackCount' => $uniqueTracks->count(), // Count of unique tracks
            'totalPlayCount' => $allPlays->count(), // Total number of plays
            'tracks' => $uniqueTracks->map(function ($item) {
                return array_merge(
                    $item['track']->toArray(),
                    [
                        'play_count' => $item['play_count'],
                        'first_played_at' => $item['first_played_at'],
                        'last_played_at' => $item['last_played_at']
                    ]
                );
            })->values()->toArray(),
            'totalProducerCollabs' => count($collaborators),
            'totalArtistCollabs' => count($artistCollaborators),
            'recommendedTracks' => $recommendedTracks,
            'similarProducers' => $similarProducers,
            'collaborators' => $collaborators,
            'artistCollaborators' => $artistCollaborators,
            'isFollowing' => $this->isFollowing($user, $producer),
            'isFavourited' => $this->isFavourited($user, $producer),
        ]);
        } catch (\Exception $e) {
            Log::error('Error in ProducerController::show', [
                'producer_id' => $id,
                'user_id' => $user->id ?? null,
                'source' => isset($user) && !empty($user->apple_music_token) ? 'Apple Music' : 'Spotify',
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    public function toggleFollow(Producer $producer)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        $this->handleFollow($user, $producer);
        return back();
    }

    public function toggleFavourite(Producer $producer)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        $this->handleFavourite($user, $producer);
        return back();
    }

    private function getSpotifyRecommendations($producerName, $userGenres, $user)
    {

        $aiRecommendedTracks = $this->getOpenAIRecommendations($producerName, $userGenres);


        return $this->searchTracksOnSpotify($aiRecommendedTracks, $user);
    }


    private function getAppleMusicRecommendations($producerName, $userGenres, $user)
    {

        $aiRecommendedTracks = $this->getOpenAIRecommendationsAP($producerName, $userGenres);


        return $this->searchTracksOnAppleMusic($aiRecommendedTracks, $user);
    }



    private function getOpenAIRecommendations($producerName, $userGenres)
    {
        $prompt = "Give me a list of 9 recommended songs based on the music style of producer {$producerName}
                    and the following genres: " . implode(', ', $userGenres) . ".
                    Format the response as a JSON list of objects with 'track_name' and 'artist_name'.";

        try {
            Log::info("Sending OpenAI Request", ['prompt' => $prompt]);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->openAiApiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4-turbo',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.7,
            ]);

            Log::info("OpenAI Raw Response", ['body' => $response->body()]);

            if (!$response->ok()) {
                Log::error("OpenAI API request failed", ['status' => $response->status(), 'error' => $response->body()]);
                return [];
            }

            $result = json_decode($response->getBody(), true);
            $textResponse = $result['choices'][0]['message']['content'] ?? '';

            // **Clean JSON by removing Markdown code block**
            $cleanJson = preg_replace('/```json\s*(.*?)\s*```/s', '$1', $textResponse);

            Log::info("OpenAI Processed Response", ['clean_json' => $cleanJson]);

            return json_decode($cleanJson, true) ?? [];
        } catch (\Exception $e) {
            Log::error("OpenAI API Exception: " . $e->getMessage());
            return [];
        }
    }

    private function getOpenAIRecommendationsAP($producerName, $userGenres)
    {
        $prompt = "Give me a list of 9 recommended songs based on the music style of producer {$producerName}
                                    and the following genres: " . implode(', ', $userGenres) . ".
                                    Format the response as a JSON list of objects with 'track_name' and 'artist_name'.
                                    Replace spaces with plus signs in both the track_name and artist_name fields.
                                            For artist_name, only include the primary artist.";

        try {
            Log::info("Sending OpenAI Request", ['prompt' => $prompt]);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->openAiApiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4-turbo',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.7,
            ]);

            Log::info("OpenAI Raw Response", ['body' => $response->body()]);

            if (!$response->ok()) {
                Log::error("OpenAI API request failed", ['status' => $response->status(), 'error' => $response->body()]);
                return [];
            }

            $result = json_decode($response->getBody(), true);
            $textResponse = $result['choices'][0]['message']['content'] ?? '';

            // **Clean JSON by removing Markdown code block**
            $cleanJson = preg_replace('/```json\s*(.*?)\s*```/s', '$1', $textResponse);

            Log::info("OpenAI Processed Response", ['clean_json' => $cleanJson]);

            return json_decode($cleanJson, true) ?? [];
        } catch (\Exception $e) {
            Log::error("OpenAI API Exception: " . $e->getMessage());
            return [];
        }
    }


    private function searchTracksOnSpotify($aiRecommendedTracks, $user)
    {
        $spotifyTracks = collect();

        foreach ($aiRecommendedTracks as $track) {
            $query = $track['track_name'] . ' ' . $track['artist_name'];

            $response = Http::withToken($user->spotify_token)
                ->get('https://api.spotify.com/v1/search', [
                    'q' => $query,
                    'type' => 'track',
                    'limit' => 1,
                ]);

            if ($response->status() === 401) {
                $this->refreshSpotifyToken($user);
                $response = Http::withToken($user->spotify_token)
                    ->get('https://api.spotify.com/v1/search', [
                        'q' => $query,
                        'type' => 'track',
                        'limit' => 1,
                    ]);
            }

            if ($response->ok()) {
                $trackData = $response->json()['tracks']['items'][0] ?? null;
                if ($trackData) {
                    $spotifyTracks->push([
                        'track_name' => $trackData['name'],
                        'album_name' => $trackData['album']['name'],
                        'artist_name' => collect($trackData['artists'])->pluck('name')->join(', '),
                        'spotify_url' => $trackData['external_urls']['spotify'] ?? null,
                        'album_cover' => $trackData['album']['images'][0]['url'] ?? 'https://via.placeholder.com/300x300?text=No+Image',
                    ]);
                }
            }
        }

        return $spotifyTracks;
    }

    private function searchTracksOnAppleMusic($aiRecommendedTracks, $user)
    {
        $appleMusicTracks = collect();

        // Apple Music requires a JWT token for authentication


        foreach ($aiRecommendedTracks as $track) {

            $query = $track['track_name'] . ' ' . $track['artist_name'];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->developerToken}",
            ])->get("https://api.music.apple.com/v1/catalog/us/search", [
                'types' => 'songs',
                'term' => $query,
                'limit' => 1,
            ]);

            if ($response->ok()) {
                $results = $response->json()['results'] ?? [];
                $songsData = $results['songs']['data'] ?? [];
                $songData = $songsData[0] ?? null;

                if ($songData) {
                    $attributes = $songData['attributes'] ?? [];

                    // Get album artwork with appropriate size
                    $artworkUrl = null;
                    if (isset($attributes['artwork']['url'])) {
                        // Replace {w} and {h} with actual dimensions
                        $artworkUrl = str_replace(['{w}', '{h}'], ['300', '300'], $attributes['artwork']['url']);
                    }

                    $appleMusicTracks->push([
                        'track_name' => $attributes['name'] ?? '',
                        'album_name' => $attributes['albumName'] ?? '',
                        'artist_name' => $attributes['artistName'] ?? '',
                        'apple_music_url' => $attributes['url'] ?? null,
                        'album_cover' => $artworkUrl ?? 'https://via.placeholder.com/300x300?text=No+Image',
                        'preview_url' => $attributes['previews'][0]['url'] ?? null,
                    ]);
                }
            } else {
                Log::error("Apple Music API request failed", [
                    'status' => $response->status(),
                    'error' => $response->body(),
                    'query' => $query
                ]);
            }
        }

        return $appleMusicTracks;
    }
    
    private function getInternalRecommendations(User $user, Producer $producer)
    {
        $userTrackIds = ListeningHistory::where('user_id', $user->id)->pluck('track_id');

        $tracks = DB::table('producer_track')
            ->join('listening_history', 'producer_track.listening_history_id', '=', 'listening_history.id')
            ->where('producer_track.producer_id', $producer->id)
            ->where('listening_history.user_id', '!=', $user->id)
            ->whereNotIn('listening_history.track_id', $userTrackIds)
            ->select(
                'listening_history.track_name',
                'listening_history.artist_name'
            )
            ->distinct()
            ->limit(12)
            ->get();

        $finalTracks = collect();

        foreach ($tracks as $track) {
            if (isset($user->apple_music_token)) {
                // Search Apple Music
                $fallback = $this->searchAppleMusicTrackByName($track->track_name, $track->artist_name);
            } else {
                // Search Spotify
                $fallback = $this->searchSpotifyTrackByName($track->track_name, $track->artist_name, $user);
            }

            if ($fallback) {
                $finalTracks->push($fallback);
            }
        }

        return $finalTracks;
    }

    private function getGenreBasedRecommendations(User $user, Producer $producer)
    {
        $topGenres = $this->getTopGenresForProducer($producer);

        if ($topGenres->isEmpty()) {
            return collect();
        }

        $genreIds = $topGenres->pluck('id');

        $userTrackIds = ListeningHistory::where('user_id', $user->id)->pluck('track_id');

        $tracks = DB::table('genre_track')
            ->join('listening_history', 'genre_track.listening_history_id', '=', 'listening_history.id')
            ->whereIn('genre_track.genre_id', $genreIds)
            ->whereNotIn('listening_history.track_id', $userTrackIds)
            ->select(
                'listening_history.track_name',
                'listening_history.artist_name'
            )
            ->distinct()
            ->limit(12)
            ->get();

        $finalTracks = collect();

        foreach ($tracks as $track) {
            if (isset($user->apple_music_token)) {
                $fallback = $this->searchAppleMusicTrackByName($track->track_name, $track->artist_name);
            } else {
                $fallback = $this->searchSpotifyTrackByName($track->track_name, $track->artist_name, $user);
            }

            if ($fallback) {
                $finalTracks->push($fallback);
            }
        }

        return $finalTracks;
    }

    private function getTracksFromCollaborators(User $user, Producer $producer)
    {
        $producerTrackIds = DB::table('producer_track')
            ->where('producer_id', $producer->id)
            ->pluck('listening_history_id');

        $collaboratorIds = DB::table('producer_track')
            ->whereIn('listening_history_id', $producerTrackIds)
            ->where('producer_id', '!=', $producer->id)
            ->pluck('producer_id')
            ->unique();

        if ($collaboratorIds->isEmpty()) {
            return collect();
        }

        $collaboratorTrackIds = DB::table('producer_track')
            ->whereIn('producer_id', $collaboratorIds)
            ->pluck('listening_history_id');

        $userTrackIds = ListeningHistory::where('user_id', $user->id)
            ->pluck('track_id');

        $tracks = ListeningHistory::whereIn('id', $collaboratorTrackIds)
            ->whereNotIn('track_id', $userTrackIds)
            ->orderByDesc('played_at')
            ->limit(12)
            ->get();

        $finalTracks = collect();

        foreach ($tracks as $track) {
            if (isset($user->apple_music_token)) {
                $fallback = $this->searchAppleMusicTrackByName($track->track_name, $track->artist_name);
            } else {
                $fallback = $this->searchSpotifyTrackByName($track->track_name, $track->artist_name, $user);
            }

            if ($fallback) {
                $finalTracks->push($fallback);
            }
        }

        return $finalTracks;
    }

    private function searchSpotifyTrackByName($trackName, $artistName, $user)
    {
        $query = "{$trackName} {$artistName}";

        $response = Http::withToken($user->spotify_token)
            ->get('https://api.spotify.com/v1/search', [
                'q' => $query,
                'type' => 'track',
                'limit' => 1,
            ]);

        if ($response->status() === 401) {
            $this->refreshSpotifyToken($user);
            $response = Http::withToken($user->spotify_token)
                ->get('https://api.spotify.com/v1/search', [
                    'q' => $query,
                    'type' => 'track',
                    'limit' => 1,
                ]);
        }

        if ($response->ok()) {
            $trackData = $response->json()['tracks']['items'][0] ?? null;

            if ($trackData) {
                return [
                    'track_name' => $trackData['name'],
                    'artist_name' => collect($trackData['artists'])->pluck('name')->join(', '),
                    'album_name' => $trackData['album']['name'],
                    'played_at' => now(), // we don't need the actual played_at here
                    'spotify_url' => $trackData['external_urls']['spotify'] ?? null,
                    'album_cover' => $trackData['album']['images'][0]['url'] ?? 'https://via.placeholder.com/300x300?text=No+Image',
                    'preview_url' => $trackData['preview_url'] ?? null,
                ];
            }
        }

        return null;
    }

    private function searchAppleMusicTrackByName($trackName, $artistName)
    {
        $query = "{$trackName} {$artistName}";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->developerToken}",
        ])->get("https://api.music.apple.com/v1/catalog/us/search", [
            'types' => 'songs',
            'term' => $query,
            'limit' => 1,
        ]);

        if ($response->ok()) {
            $songs = $response->json()['results']['songs']['data'] ?? [];
            $song = $songs[0] ?? null;

            if ($song) {
                $attr = $song['attributes'] ?? [];
                $artworkUrl = isset($attr['artwork']['url'])
                    ? str_replace(['{w}', '{h}'], ['300', '300'], $attr['artwork']['url'])
                    : 'https://via.placeholder.com/300x300?text=No+Image';

                return [
                    'track_name' => $attr['name'] ?? '',
                    'artist_name' => $attr['artistName'] ?? '',
                    'album_name' => $attr['albumName'] ?? '',
                    'apple_music_url' => $attr['url'] ?? null,
                    'album_cover' => $artworkUrl,
                    'preview_url' => $attr['previews'][0]['url'] ?? null,
                    'played_at' => now(),
                ];
            }
        }

        Log::warning("Apple fallback failed", ['query' => $query, 'status' => $response->status()]);
        return null;
    }

    private function getTopGenresForProducer(Producer $producer, $limit = 1)
    {
        return DB::table('producer_track')
            ->join('listening_history', 'producer_track.listening_history_id', '=', 'listening_history.id')
            ->join('genre_track', 'listening_history.id', '=', 'genre_track.listening_history_id')
            ->join('genres', 'genre_track.genre_id', '=', 'genres.id')
            ->where('producer_track.producer_id', $producer->id)
            ->select('genres.id', 'genres.name', DB::raw('COUNT(*) as genre_count'))
            ->groupBy('genres.id', 'genres.name')
            ->orderByDesc('genre_count')
            ->limit($limit)
            ->get();
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

    public function sharedTracks($producerId, $collaboratorId, Request $request)
    {
        $user = Auth::user();

        $collaborator = Producer::findOrFail($collaboratorId);

        if ($producerId === 'artist') {
            // Coming from artist context — expect artist name to be passed in query param
            $artistName = $request->query('artist');

            if (!$artistName) {
                abort(400, 'Artist name is required for artist context.');
            }

            // Get user's listening history for that artist
            $trackIds = ListeningHistory::where('user_id', $user->id)
                ->where('artist_name', $artistName)
                ->pluck('id');

            // Get all plays for tracks that are in the artist's tracks AND produced by this collaborator
            $allSharedPlays = $collaborator->tracks()
                ->whereIn('listening_history_id', $trackIds)
                ->with(['genres', 'producers'])
                ->get();

            // Group by track_id to get unique tracks with play counts
            $sharedTracks = $allSharedPlays->groupBy('track_id')->map(function ($plays) {
                $firstPlay = $plays->first();
                $data = json_decode($firstPlay->track_data, true);

                $firstPlay->spotify_url = null;
                $firstPlay->apple_music_url = null;

                if (Str::lower($firstPlay->source) === 'spotify') {
                    $firstPlay->spotify_url = $data['external_urls']['spotify'] ?? null;
                }

                if (Str::lower($firstPlay->source) === 'apple music') {
                    $firstPlay->apple_music_url = $data['attributes']['url'] ?? null;
                }

                $firstPlay->play_count = $plays->count();
                $firstPlay->first_played_at = $plays->min('played_at');
                $firstPlay->last_played_at = $plays->max('played_at');

                return $firstPlay;
            })->values();

            return Inertia::render('SharedTracks', [
                'producer' => ['id' => 'artist', 'name' => $artistName],
                'collaborator' => $collaborator,
                'sharedTracks' => $sharedTracks,
            ]);
        }

        // Normal producer-to-producer flow
        $producer = Producer::findOrFail($producerId);

        $producerTrackIds = $producer->tracks()->where('user_id', $user->id)->pluck('listening_history_id');
        $collaboratorTrackIds = $collaborator->tracks()->where('user_id', $user->id)->pluck('listening_history_id');

        $sharedTrackIds = $producerTrackIds->intersect($collaboratorTrackIds);

        // Get all plays for shared tracks
        $allSharedPlays = ListeningHistory::with(['genres', 'producers'])
            ->whereIn('id', $sharedTrackIds)
            ->get();

        // Group by track_id to get unique tracks with play counts
        $sharedTracks = $allSharedPlays->groupBy('track_id')->map(function ($plays) {
            $firstPlay = $plays->first();
            $data = json_decode($firstPlay->track_data, true);

            $firstPlay->spotify_url = null;
            $firstPlay->apple_music_url = null;

            if (Str::lower($firstPlay->source) === 'spotify') {
                $firstPlay->spotify_url = $data['external_urls']['spotify'] ?? null;
            }

            if (Str::lower($firstPlay->source) === 'apple music') {
                $firstPlay->apple_music_url = $data['attributes']['url'] ?? null;
            }

            $firstPlay->play_count = $plays->count();
            $firstPlay->first_played_at = $plays->min('played_at');
            $firstPlay->last_played_at = $plays->max('played_at');

            return $firstPlay;
        })->values();

        return Inertia::render('SharedTracks', [
            'producer' => $producer,
            'collaborator' => $collaborator,
            'sharedTracks' => $sharedTracks,
        ]);
    }

    private function artistCollaborators(Producer $producer)
    {
        $user = Auth::user();

        // Get all plays and group by track for unique count
        $allPlays = $producer->tracks()->where('user_id', $user->id)->get();
        $uniqueTracks = $allPlays->groupBy('track_id');
        $tracks = $uniqueTracks->map(function ($plays) {
            return $plays->first();
        })->values();

        $collaborators = collect();
        $artistTracks = collect();

        foreach ($tracks as $track) {
            $artists = $this->splitArtistNames($track->artist_name);

            foreach ($artists as $artist) {
                $artist = trim($artist);

                if (!empty($artist)) {
                    // Store the artist with their track
                    $artistTracks->push([
                        'artist' => $artist,
                        'track' => $track
                    ]);
                    $collaborators->push($artist);
                }
            }
        }

        $artistCounts = $collaborators
            ->map(fn ($name) => trim($name))
            ->countBy();

        // Batch load cached artist images
        $artistNames = $artistCounts->keys();
        $cachedImages = ArtistImage::whereIn('artist_name', $artistNames)
            ->pluck('image_url', 'artist_name');

        // Include image_url, genres, and latest track for each unique artist
        $artistsWithDetails = $artistCounts->map(function ($count, $name) use ($cachedImages, $artistTracks, $producer) {
            // Use cached image, no fallback to Genius API for performance
            $image = $cachedImages->get($name);

            // Get all tracks where this artist appears
            $artistSpecificTracks = $artistTracks
                ->filter(fn ($item) => trim($item['artist']) === $name)
                ->pluck('track');

            // Get latest track
            $latestTrack = $artistSpecificTracks
                ->sortByDesc('played_at')
                ->first();

            // Get all genres from artist's tracks
            $genres = $artistSpecificTracks
                ->flatMap(fn ($track) => $track->genres->pluck('name'))
                ->unique()
                ->values()
                ->toArray();

            return [
                'name' => $name,
                'shared_tracks_count' => $count,
                'image_url' => $image,
                'genres' => $genres,
                'latest_track' => $latestTrack ? [
                    'track_name' => $latestTrack->track_name,
                    'played_at' => $latestTrack->played_at
                ] : null
            ];
        })->sortByDesc('shared_tracks_count')->values();

        return $artistsWithDetails;
    }

    public function sharedTracksWithArtist($producerId, $artist)
    {
        $user = Auth::user();

        $producer = Producer::whereHas('tracks', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($producerId);

        // Get all plays for tracks by this producer with matching artist
        $allSharedPlays = $producer->tracks()
            ->with(['genres', 'producers'])
            ->where('user_id', $user->id)
            ->where('artist_name', 'LIKE', "%$artist%")
            ->get();

        // Group by track_id to get unique tracks with play counts
        $uniqueSharedTracks = $allSharedPlays->groupBy('track_id')->map(function ($plays) {
            $firstPlay = $plays->first();
            return [
                'track' => $firstPlay,
                'play_count' => $plays->count(),
                'first_played_at' => $plays->min('played_at'),
                'last_played_at' => $plays->max('played_at')
            ];
        });

        return Inertia::render('SharedArtistTracks', [
            'producer' => $producer,
            'artist' => $artist,
            'sharedTracks' => $uniqueSharedTracks->map(function ($item) {
                $track = $item['track'];
                return [
                    'id' => $track->id,
                    'track_name' => $track->track_name,
                    'album_name' => $track->album_name,
                    'played_at' => $track->played_at,
                    'track_data' => $track->track_data,
                    'source' => $track->source,
                    'artist_name' => $track->artist_name,
                    'genres' => $track->genres,
                    'producers' => $track->producers->map(function ($producer) {
                        return [
                            'id' => $producer->id,
                            'name' => $producer->name,
                        ];
                    }),
                    'play_count' => $item['play_count'],
                    'first_played_at' => $item['first_played_at'],
                    'last_played_at' => $item['last_played_at']
                ];
            })->values(),
        ]);
    }

    /**
     * Enrich producer data with additional fields like latest_track, genres, etc.
     * This replicates the logic from DashboardController::processProducers
     */
    private function enrichProducerData($producers, $user)
    {
        return $producers->map(function ($producer) use ($user) {
            // Get all tracks for this producer played by this user
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
                    } elseif (strtolower($track->source) === 'apple music') {
                        // Apple Music duration extraction
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

                        // Extract popularity from popularity_data for Apple Music tracks
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
                                Log::error('Error processing popularity data in enrichProducerData', [
                                    'track_id' => $track->id,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    }

                    $totalMinutes += $duration / 60000;

                    // Update latest track
                    if (!$latestTrack || $track->played_at > $latestTrack->played_at) {
                        $latestTrack = $track;
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing track in enrichProducerData', [
                        'track_id' => $track->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Get genres
            $genres = $tracks->flatMap(fn($track) => $track->genres->pluck('name'))
                ->unique()
                ->take(10)
                ->values()
                ->toArray();

            $averagePopularity = $popularityCount > 0 ? round($popularitySum / $popularityCount, 2) : 0;

            // Add enriched data to producer
            $producer->track_count = $uniqueTracks->count();
            $producer->total_minutes = round($totalMinutes, 2);
            $producer->average_popularity = $averagePopularity;
            $producer->latest_track = $latestTrack;
            $producer->genres = $genres;

            return $producer;
        });
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
