<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use App\Models\ListeningHistory;
use App\Models\User;
use App\Models\Producer;
use App\Models\Genre;
use App\Models\ArtistImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Jobs\CacheArtistImagesJob;
use Carbon\Carbon;

class FetchListeningHistoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1000;
    public $tries = 2;
    public $backoff = 60;


    public $failOnTimeout = false;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $user;
    protected $developerToken = "eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiIsImtpZCI6IkM5R1A0QVlHNzIifQ.eyJpc3MiOiJDM0M5OTU4MlhaIiwiaWF0IjoxNzUxMDIxNDk5LCJleHAiOjE3NjY1NzM0OTl9.plU-0y4LbEJXdZgcdSIwjrQ359lXrwmyNDh7fFB92A2fEI2noQOkn4jdI3890V6418nkUtTnt2wtSfBUv5N9ng";

    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info('Starting FetchListeningHistoryJob', [
            'user_id' => $this->user->id,
            'has_spotify' => !empty($this->user->spotify_token),
            'has_apple_music' => !empty($this->user->apple_music_token)
        ]);

        $spotifyResult = null;
        $appleMusicResult = null;

        // Check if user has Spotify token, then fetch from Spotify
        if ($this->user->spotify_token) {
            try {
                $spotifyResult = $this->fetchSpotifyHistory();
                Log::info('Spotify fetch result', ['result' => $spotifyResult ? 'success' : 'failure']);
            } catch (\Exception $e) {
                // Log error but don't fail the job
                Log::error('Error in Spotify fetch but continuing job:', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Check if user has Apple Music token, then fetch from Apple Music
        if ($this->user->apple_music_token) {
            try {
                $appleMusicResult = $this->fetchAppleMusicHistory();
                Log::info('Apple Music fetch result', ['result' => $appleMusicResult ? 'success' : 'failure']);
            } catch (\Exception $e) {
                // Log error but don't fail the job
                Log::error('Error in Apple Music fetch but continuing job:', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('FetchListeningHistoryJob completed', [
            'spotify_success' => $spotifyResult !== null,
            'apple_music_success' => $appleMusicResult !== null
        ]);

        return [
            'spotify' => $spotifyResult,
            'apple_music' => $appleMusicResult
        ];
    }

    /**
     * Fetch listening history from Spotify
     */
    private function fetchSpotifyHistory()
    {
        try {
            // Get the most recent track timestamp to avoid fetching duplicates
            $mostRecentPlay = ListeningHistory::where('user_id', $this->user->id)
                ->where('source', 'spotify')
                ->orderBy('played_at', 'desc')
                ->first();

            $params = ['limit' => 50];
            if ($mostRecentPlay) {
                // Spotify API allows filtering by timestamp
                $afterTimestamp = Carbon::parse($mostRecentPlay->played_at)->timestamp * 1000; // Convert to milliseconds
                $params['after'] = $afterTimestamp;
            }

            $response = Http::withToken($this->user->spotify_token)
                ->get('https://api.spotify.com/v1/me/player/recently-played', $params);

            if ($response->status() === 401) {
                $this->refreshSpotifyTokenForUser($this->user);
                return $this->fetchSpotifyHistory();
            }

            if (!$response->ok()) {
                Log::error('Failed to fetch Spotify listening history:', [
                    'status' => $response->status(),
                    'error' => $response->body(),
                ]);
                return null;
            }

            $tracks = $response->json()['items'];

            // If no new tracks, return early
            if (empty($tracks)) {
                Log::info('No new tracks to process for Spotify user', ['user_id' => $this->user->id]);
                return;
            }

            $trackChunks = collect($tracks)->chunk(10);
            $processedCount = 0;

            // Generate a unique fetch session ID for Spotify
            $fetchSessionId = uniqid('spotify_' . $this->user->id . '_', true);

            // Create a database transaction for bulk inserts
            DB::beginTransaction();

            try {
                $positionInFetch = 0;
                foreach ($trackChunks as $chunk) {
                    foreach ($chunk as $track) {
                        $playedAt = $track['played_at'];
                        $trackData = $track['track'];

                        // Create or update the history record immediately
                        $artistName = implode(', ', array_column($trackData['artists'], 'name'));

                        // Extract duration, artwork URL and song URL from Spotify data
                        $durationMs = $trackData['duration_ms'] ?? null;

                        // Get the largest album artwork image
                        $albumArtworkUrl = null;
                        if (isset($trackData['album']['images']) && !empty($trackData['album']['images'])) {
                            $albumArtworkUrl = $trackData['album']['images'][0]['url'] ?? null;
                        }

                        // Get the Spotify URL for the song
                        $urlSong = $trackData['external_urls']['spotify'] ?? null;

                        // Restructure track_data to match iOS app expectations
                        // Move album images to root level for iOS app compatibility
                        $restructuredTrackData = $trackData;
                        if (isset($trackData['album']['images'])) {
                            $restructuredTrackData['images'] = $trackData['album']['images'];
                        }

                        // Also ensure duration_ms is at root level
                        $restructuredTrackData['duration_ms'] = $durationMs;

                        // Check if this exact play already exists within a small time window
                        // This prevents duplicates from the same API response while allowing legitimate replays
                        $playedAtTime = Carbon::parse($playedAt);
                        $existingPlay = ListeningHistory::where('user_id', $this->user->id)
                            ->where('track_id', $trackData['id'])
                            ->where('source', 'spotify')
                            ->whereBetween('played_at', [
                                $playedAtTime->copy()->subSeconds(5),
                                $playedAtTime->copy()->addSeconds(5)
                            ])
                            ->first();

                        if (!$existingPlay) {
                            // Create new record for this play
                            $history = ListeningHistory::create([
                                'user_id' => $this->user->id,
                                'track_id' => $trackData['id'],
                                'track_name' => $trackData['name'],
                                'artist_name' => $artistName,
                                'album_name' => $trackData['album']['name'],
                                'played_at' => $playedAt,
                                'track_data' => json_encode($restructuredTrackData),
                                'source' => 'spotify',
                                'fetch_session_id' => $fetchSessionId,
                                'position_in_fetch' => $positionInFetch
                            ]);

                            Log::debug('Created new Spotify track play', [
                                'track_id' => $trackData['id'],
                                'track_name' => $trackData['name'],
                                'played_at' => $playedAt,
                                'fetch_session_id' => $fetchSessionId,
                                'position' => $positionInFetch
                            ]);
                        } else {
                            $history = $existingPlay;
                            Log::debug('Skipped duplicate Spotify track within time window', [
                                'track_id' => $trackData['id'],
                                'track_name' => $trackData['name'],
                                'played_at' => $playedAt,
                                'existing_played_at' => $existingPlay->played_at
                            ]);
                        }

                        // Fetch and cache artist images
                        try {
                            $individualArtists = $this->splitArtistNames($artistName);
                            foreach ($individualArtists as $artist) {
                                $this->fetchAndCacheArtistImage($artist);
                            }
                        } catch (\Exception $e) {
                            Log::warning('Failed to fetch artist images for Spotify track', [
                                'track_id' => $trackData['id'],
                                'error' => $e->getMessage()
                            ]);
                        }

                        $processedCount++;
                        $positionInFetch++;
                    }
                }

                // Commit the transaction to save all track data
                DB::commit();

                // Artist images are now fetched directly during track processing

                // Now that all tracks are saved, try to attach genres
                // but in a separate transaction that can fail independently
                foreach ($trackChunks as $chunk) {
                    foreach ($chunk as $track) {
                        $trackData = $track['track'];

                        // Get the most recently saved history record for this track
                        $history = ListeningHistory::where([
                            'user_id' => $this->user->id,
                            'track_id' => $trackData['id'],
                            'source' => 'spotify'
                        ])->orderBy('created_at', 'desc')->first();

                        if ($history) {
                            // Try to attach genres, but don't let failures stop us
                            foreach ($trackData['artists'] as $artist) {
                                try {
                                    $this->attachGenresToTrackWithRetries($history, $artist['id'], $this->user);
                                } catch (\Exception $e) {
                                    Log::warning('Genre attachment failed but continuing', [
                                        'track_id' => $trackData['id'],
                                        'artist_id' => $artist['id'],
                                        'error' => $e->getMessage()
                                    ]);
                                    // Continue with next artist
                                }
                            }
                        }
                    }
                }

            } catch (\Exception $e) {
                // If there's any error in the transaction, roll back
                DB::rollBack();
                Log::error('Transaction failed when saving Spotify history', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

            Log::info('Spotify listening history updated successfully', [
                'processed_count' => $processedCount,
                'total_tracks' => count($tracks)
            ]);

            return [
                'processed' => $processedCount,
                'total' => count($tracks)
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching Spotify listening history:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Fetch listening history from Apple Music
     */
    private function fetchAppleMusicHistory()
    {
        try {
            Log::info('Fetching Apple Music history', [
                'user_id' => $this->user->id
            ]);


            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->developerToken}",
                'Music-User-Token' => $this->user->apple_music_token
            ])->get('https://api.music.apple.com/v1/me/recent/played/tracks');

            // Log response
            Log::debug('Apple Music API response', [
                'status' => $response->status(),
                'success' => $response->successful()
            ]);

            if (!$response->successful()) {
                Log::error('Failed to fetch Apple Music listening history:', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'error_message' => $response->body(),
                    'token_exists' => !empty($this->user->apple_music_token),
                    'token_length' => !empty($this->user->apple_music_token) ? strlen($this->user->apple_music_token) : 0
                ]);
                return null;
            }

            // Process the tracks
            $responseData = $response->json();

            // Check if we have the data in the expected format
            if (!isset($responseData['data']) || !is_array($responseData['data'])) {
                Log::warning('Unexpected Apple Music response format', [
                    'response_keys' => array_keys($responseData)
                ]);
                return null;
            }

            $tracks = $responseData['data'] ?? [];

            Log::info('Fetched Apple Music tracks', ['count' => count($tracks)]);

            // Generate a unique fetch session ID
            $fetchSessionId = uniqid('apple_music_' . $this->user->id . '_', true);

            // Get the last fetch session for this user
            $lastSession = ListeningHistory::where('user_id', $this->user->id)
                ->where('source', 'Apple Music')
                ->whereNotNull('fetch_session_id')
                ->orderBy('created_at', 'desc')
                ->orderBy('position_in_fetch', 'asc')
                ->take(50)
                ->get();

            // Find where new tracks start
            $newTrackStartIndex = $this->findNewTrackStartIndexImproved($tracks, $lastSession);

            // Log when we're processing many tracks (but don't limit them)
            if ($newTrackStartIndex > 20) {
                Log::info('Processing multiple new tracks for Apple Music', [
                    'user_id' => $this->user->id,
                    'new_tracks_count' => $newTrackStartIndex,
                    'total_tracks' => count($tracks)
                ]);
            }

            if ($newTrackStartIndex === 0) {
                Log::info('No new tracks to process for Apple Music user', [
                    'user_id' => $this->user->id
                ]);
                return [
                    'processed' => 0,
                    'total' => count($tracks)
                ];
            }

            DB::beginTransaction();

            try {
                $processed = 0;
                $allArtistNames = [];

                // Only process tracks from the new track start index
                for ($i = 0; $i < $newTrackStartIndex; $i++) {
                    $track = $tracks[$i];
                    if ($this->processAppleMusicTrack($track, $fetchSessionId, $i)) {
                        $processed++;

                        // Extract artist names for image caching
                        $attributes = $track['attributes'] ?? [];
                        $artistName = $attributes['artistName'] ?? '';
                        if (!empty($artistName)) {
                            $individualArtists = $this->splitArtistNames($artistName);
                            $allArtistNames = array_merge($allArtistNames, $individualArtists);
                        }
                    }
                }

                DB::commit();

                // Artist images are now fetched directly during track processing

                Log::info('Apple Music listening history updated successfully', [
                    'tracks_processed' => $processed,
                    'total_tracks' => count($tracks),
                    'new_tracks' => $newTrackStartIndex
                ]);

                return [
                    'processed' => $processed,
                    'total' => count($tracks)
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Transaction failed when saving Apple Music history', [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error fetching Apple Music listening history:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Process a single Apple Music track and save to listening history
     *
     * @param array $track
     * @return bool
     */
   private function processAppleMusicTrack($track, $fetchSessionId = null, $positionInFetch = null)
   {
       try {
           // Extract track data
           $attributes = $track['attributes'] ?? [];

           if (empty($attributes)) {
               Log::warning('Track missing attributes', ['track_id' => $track['id'] ?? 'unknown']);
               return false;
           }

           // Extract track details
           $trackId = $track['id'] ?? null;
           $trackName = $attributes['name'] ?? 'Unknown Track';
           $artistName = $attributes['artistName'] ?? 'Unknown Artist';
           $albumName = $attributes['albumName'] ?? 'Unknown Album';
           $durationMs = $attributes['durationInMillis'] ?? null;
           $playedAt = date('Y-m-d\TH:i:s\Z');

           // Extract additional fields
           $urlSong = $attributes['url'] ?? null;
           $appleMusicId = $track['id'] ?? null;

           // Extract album artwork URL
           $albumArtworkUrl = null;
           if (isset($attributes['artwork']['url'])) {
               $albumArtworkUrl = $attributes['artwork']['url'];
               // Replace placeholders with actual dimensions (e.g., 640x640)
               $albumArtworkUrl = str_replace(['{w}', '{h}'], ['640', '640'], $albumArtworkUrl);
           }

           // Restructure track_data to match iOS app expectations
           $restructuredTrackData = $track;

           // Add duration at root level for iOS compatibility
           $restructuredTrackData['duration_ms'] = $durationMs;
           $restructuredTrackData['duration'] = $durationMs;

           // Extract release date if available
           if (isset($attributes['releaseDate'])) {
               $restructuredTrackData['release_date'] = $attributes['releaseDate'];
           }

           // Add explicit content flag - Apple Music uses contentRating field
           if (isset($attributes['contentRating']) && $attributes['contentRating'] === 'explicit') {
               $restructuredTrackData['explicit'] = true;
           } else {
               $restructuredTrackData['explicit'] = false;
           }

           // Add preview URL if available
           if (isset($attributes['previews'][0]['url'])) {
               $restructuredTrackData['preview_url'] = $attributes['previews'][0]['url'];
           }

           // Convert artwork to images array for iOS compatibility
           if (isset($attributes['artwork'])) {
               $artwork = $attributes['artwork'];
               $sizes = [[300, 300], [640, 640]];
               $images = [];
               foreach ($sizes as $size) {
                   $url = str_replace(['{w}', '{h}'], [$size[0], $size[1]], $artwork['url']);
                   $images[] = [
                       'url' => $url,
                       'height' => $size[1],
                       'width' => $size[0]
                   ];
               }
               $restructuredTrackData['images'] = $images;
           }

           // Add external URLs for consistency with Spotify format
           $restructuredTrackData['external_urls'] = [
               'apple_music' => $urlSong
           ];

           // Enhanced duplicate detection for Apple Music
           // Check if this track was already saved in the last 10 minutes
           $recentPlay = ListeningHistory::where('user_id', $this->user->id)
               ->where('track_id', $trackId)
               ->where('source', 'Apple Music')
               ->where('created_at', '>=', Carbon::now()->subMinutes(10))
               ->orderBy('created_at', 'desc')
               ->first();

           if ($recentPlay) {
               // If we have a recent play, check if this is likely a duplicate
               // Get the last few fetches to analyze patterns
               $recentFetches = ListeningHistory::where('user_id', $this->user->id)
                   ->where('source', 'Apple Music')
                   ->where('fetch_session_id', '!=', $fetchSessionId)
                   ->whereNotNull('fetch_session_id')
                   ->where('created_at', '>=', Carbon::now()->subMinutes(15))
                   ->orderBy('created_at', 'desc')
                   ->groupBy('fetch_session_id')
                   ->pluck('fetch_session_id')
                   ->take(3);

               // Check if this track appeared in recent fetches
               $appearanceCount = ListeningHistory::where('user_id', $this->user->id)
                   ->where('track_id', $trackId)
                   ->where('source', 'Apple Music')
                   ->whereIn('fetch_session_id', $recentFetches)
                   ->count();

               // If the track appeared in multiple recent fetches, it's likely a duplicate
               if ($appearanceCount >= 2) {
                   Log::debug('Skipping likely duplicate Apple Music track', [
                       'track_id' => $trackId,
                       'track_name' => $trackName,
                       'position' => $positionInFetch,
                       'appearances_in_recent_fetches' => $appearanceCount
                   ]);
                   return false;
               }

               // Additional check: if the exact same track at a similar position was saved recently
               $positionRange = 3; // Allow for slight position variations
               $similarPositionPlay = ListeningHistory::where('user_id', $this->user->id)
                   ->where('track_id', $trackId)
                   ->where('source', 'Apple Music')
                   ->where('created_at', '>=', Carbon::now()->subMinutes(10))
                   ->whereBetween('position_in_fetch', [
                       max(0, $positionInFetch - $positionRange),
                       $positionInFetch + $positionRange
                   ])
                   ->first();

               if ($similarPositionPlay) {
                   Log::debug('Skipping Apple Music track with similar position', [
                       'track_id' => $trackId,
                       'track_name' => $trackName,
                       'current_position' => $positionInFetch,
                       'previous_position' => $similarPositionPlay->position_in_fetch
                   ]);
                   return false;
               }
           }

           // Add session tracking data
           $restructuredTrackData['fetch_timestamp'] = now()->toIso8601String();

           // Create a new record for this play
           $history = ListeningHistory::create([
               'user_id' => $this->user->id,
               'track_id' => $trackId,
               'track_name' => $trackName,
               'artist_name' => $artistName,
               'album_name' => $albumName,
               'played_at' => $playedAt,
               'track_data' => json_encode($restructuredTrackData),
               'source' => 'Apple Music',
               'fetch_session_id' => $fetchSessionId,
               'position_in_fetch' => $positionInFetch
           ]);

           Log::debug('Created new Apple Music track play', [
               'track_id' => $trackId,
               'track_name' => $trackName,
               'fetch_session_id' => $fetchSessionId,
               'position' => $positionInFetch
           ]);

           // Extract and attach genres if available
           try {
               if (isset($attributes['genreNames']) && is_array($attributes['genreNames'])) {
                   foreach ($attributes['genreNames'] as $genreName) {
                       $genre = Genre::firstOrCreate(['name' => $genreName]);
                       $history->genres()->syncWithoutDetaching($genre->id);
                   }
               }
           } catch (\Exception $e) {
               Log::warning('Failed to attach Apple Music genres but continuing', [
                   'track_id' => $trackId,
                   'error' => $e->getMessage()
               ]);
           }

           // Search for track on Spotify to get additional data
           try {
               $spotifyData = $this->searchSpotifyTrack($trackName, $artistName);
               if ($spotifyData) {
                   // Update the history record with Spotify data
                   $history->update(['popularity_data' => json_encode($spotifyData)]);

                   Log::info('Added Spotify data to Apple Music track', [
                       'track_id' => $trackId,
                       'spotify_id' => $spotifyData['spotify_id']
                   ]);
               }
           } catch (\Exception $e) {
               Log::warning('Failed to search Spotify for Apple Music track', [
                   'track_id' => $trackId,
                   'error' => $e->getMessage()
               ]);
           }

           // Fetch and attach producers from Genius API
           try {
               $this->fetchAndAttachProducers($history, $trackName, $artistName);
           } catch (\Exception $e) {
               Log::warning('Failed to fetch producers but continuing', [
                   'track_id' => $trackId,
                   'error' => $e->getMessage()
               ]);
           }

           // Fetch and cache artist images (handle multiple artists)
           try {
               $individualArtists = $this->splitArtistNames($artistName);
               foreach ($individualArtists as $artist) {
                   $this->fetchAndCacheArtistImage($artist);
               }
           } catch (\Exception $e) {
               Log::warning('Failed to fetch artist images but continuing', [
                   'artist' => $artistName,
                   'error' => $e->getMessage()
               ]);
           }

           return true;
       } catch (\Exception $e) {
           Log::error('Error processing Apple Music track:', [
               'track_id' => $track['id'] ?? 'unknown',
               'message' => $e->getMessage()
           ]);
           return false;
       }
   }

    /**
     * Search for a track on Spotify and return the data
     *
     * @param string $trackName Name of the track
     * @param string $artistName Name of the artist
     * @return array|null The Spotify track data or null if not found
     */
    private function searchSpotifyTrack($trackName, $artistName)
    {
        try {
            // Get a Spotify token from user ID 1 (in order to get popularity data)
            $spotifyUser = User::find(1);

            if (!$spotifyUser || empty($spotifyUser->spotify_token)) {
                Log::warning('No Spotify access token available for user ID 1');
                return null;
            }

            $accessToken = $spotifyUser->spotify_token;


            $query = urlencode("track:{$trackName} artist:{$artistName}");
            $searchUrl = "https://api.spotify.com/v1/search?q={$query}&type=track&limit=1";


            $response = Http::withToken($accessToken)
                ->get($searchUrl);

            // Handle 401 Unauthorized (expired token)
            if ($response->status() === 401) {
                // Refresh the token
                $this->refreshSpotifyTokenForUser($spotifyUser);

                // Retry the search with the new token
                return $this->searchSpotifyTrack($trackName, $artistName);
            }

            if (!$response->successful()) {
                Log::error('Failed to search Spotify:', [
                    'status' => $response->status(),
                    'error' => $response->body(),
                ]);
                return null;
            }

            $responseBody = $response->json();

            // Check if we got a track result
            if (isset($responseBody['tracks']['items'][0])) {
                $spotifyTrack = $responseBody['tracks']['items'][0];


                return [
                    'spotify_id' => $spotifyTrack['id'] ?? null,
                    'popularity' => $spotifyTrack['popularity'] ?? null,
                    'spotify_url' => $spotifyTrack['external_urls']['spotify'] ?? null,
                    'explicit' => $spotifyTrack['explicit'] ?? false,
                    'duration_ms' => $spotifyTrack['duration_ms'] ?? null,
                    'preview_url' => $spotifyTrack['preview_url'] ?? null,
                    'searched_at' => date('Y-m-d\TH:i:s\Z')
                ];
            }

            Log::info('No matching track found on Spotify', [
                'track_name' => $trackName,
                'artist_name' => $artistName
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error searching Spotify:', [
                'track_name' => $trackName,
                'artist_name' => $artistName,
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }


    /**
     * Refresh Spotify token for a specific user
     *
     * @param User $user The user whose token needs refreshing
     * @return bool Success indicator
     */
    private function refreshSpotifyTokenForUser($user)
    {
        try {
            $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $user->spotify_refresh_token,
                'client_id' => env('SPOTIFY_CLIENT_ID'),
                'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
            ]);

            $data = $response->json();

            if (isset($data['access_token'])) {
                $user->update(['spotify_token' => $data['access_token']]);
                Log::info('Successfully refreshed Spotify token for user', [
                    'user_id' => $user->id
                ]);
                return true;
            } else {
                Log::error('Failed to refresh Spotify token:', $data);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception during Spotify token refresh:', [
                'user_id' => $user->id,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Attach genres to track without retries and improved rate limit handling
     */
    private function attachGenresToTrackWithRetries($history, $artistId, $user)
    {
        try {
            $artistResponse = Http::withToken($user->spotify_token)
                ->get("https://api.spotify.com/v1/artists/{$artistId}");

            Log::debug('Spotify artist response', [
                'artist_id' => $artistId,
                'status' => $artistResponse->status(),
            ]);

            if ($artistResponse->status() === 401) {
                // Handle expired token
                $this->refreshSpotifyTokenForUser($user);
                // Try again with refreshed token
                $artistResponse = Http::withToken($user->spotify_token)
                    ->get("https://api.spotify.com/v1/artists/{$artistId}");
            }

            if ($artistResponse->status() === 429 || !$artistResponse->successful()) {
                // For rate limits or any other failure, immediately try Discogs
                Log::warning('Failed to get genres from Spotify, trying Discogs', [
                    'artist_id' => $artistId,
                    'status' => $artistResponse->status(),
                    'error' => $artistResponse->body(),
                ]);

                // Try Discogs as a fallback
                $this->attachGenresFromDiscogs($history, $history->artist_name, $history->track_name);
                return false;
            }

            if ($artistResponse->successful()) {
                $artistGenres = $artistResponse->json()['genres'] ?? [];

                foreach ($artistGenres as $genreName) {
                    $genre = Genre::firstOrCreate(['name' => $genreName]);
                    $history->genres()->syncWithoutDetaching($genre->id);
                }

                Log::info('Successfully attached genres to track', [
                    'track_id' => $history->track_id,
                    'artist_id' => $artistId,
                    'genre_count' => count($artistGenres),
                ]);

                return true;
            }
        } catch (\Exception $e) {
            Log::error('Exception in attachGenresToTrack:', [
                'artist_id' => $artistId,
                'message' => $e->getMessage()
            ]);

            // Try Discogs as a fallback
            $this->attachGenresFromDiscogs($history, $history->artist_name, $history->track_name);
            return false;
        }

        return false;
    }


    /**
     * Attach genres from Discogs API if they are not available from primary source
     */
    private function attachGenresFromDiscogs($history, $artistName, $trackName)
    {
        try {
            $discogsResponse = Http::get('https://api.discogs.com/database/search', [
                'q' => $trackName,
                'artist' => explode(',', $artistName)[0],
                'type' => 'release',
                'key' => config('services.discogs.key'),
                'secret' => config('services.discogs.secret'),
            ]);

            if ($discogsResponse->successful()) {
                $results = $discogsResponse->json()['results'] ?? [];

                if (!empty($results)) {
                    $genreNames = [];
                    foreach ($results as $result) {
                        if (isset($result['genre']) && is_array($result['genre'])) {
                            $genreNames = array_merge($genreNames, $result['genre']);
                        }
                        if (isset($result['style']) && is_array($result['style'])) {
                            $genreNames = array_merge($genreNames, $result['style']);
                        }
                    }

                    $genreNames = array_unique($genreNames);

                    foreach ($genreNames as $genreName) {
                        $genre = Genre::firstOrCreate(['name' => $genreName]);
                        $history->genres()->syncWithoutDetaching($genre->id);
                    }

                    Log::info('Attached genres from Discogs', [
                        'track_id' => $history->track_id,
                        'genres' => $genreNames
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error fetching genres from Discogs:', [
                'track_id' => $history->track_id,
                'message' => $e->getMessage()
            ]);
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
        
        $artists = preg_split('/[,&]|\s+(?:feat\.|featuring|ft\.|with)\s+/i', $artistName);
        return array_map('trim', $artists);
    }

    /**
     * Find the index where new tracks start in the Apple Music response
     * Returns 0 if no new tracks are found
     */
    private function findNewTrackStartIndex($fetchedTracks, $lastStoredTrackIds)
    {
        // If we have no stored tracks, all fetched tracks are new
        if (empty($lastStoredTrackIds)) {
            return count($fetchedTracks);
        }

        // Apple Music returns tracks in reverse chronological order (newest first)
        // We need to find where the new tracks end and old tracks begin

        for ($i = 0; $i < count($fetchedTracks); $i++) {
            $trackId = $fetchedTracks[$i]['id'] ?? null;

            // If we find a track that matches our last stored track,
            // everything before this index is new
            if ($trackId && in_array($trackId, $lastStoredTrackIds)) {
                return $i;
            }
        }

        // If we didn't find any matching tracks, all tracks might be new
        // But we should be cautious and check if the user has a long history
        if (count($fetchedTracks) >= 50) {
            // If we're fetching 50 tracks and none match, it's likely the user
            // has played many new songs. Process all of them.
            return count($fetchedTracks);
        }

        // For smaller fetches where nothing matches, assume all are new
        return count($fetchedTracks);
    }

    /**
     * Improved method to find where new tracks start using session comparison
     */
    private function findNewTrackStartIndexImproved($fetchedTracks, $lastSession)
    {
        // If we have no stored tracks, all fetched tracks are new
        if ($lastSession->isEmpty()) {
            return count($fetchedTracks);
        }

        // Get the most recent stored track to check time gap
        $mostRecentStoredTrack = $lastSession->first();
        $timeSinceLastFetch = Carbon::now()->diffInMinutes($mostRecentStoredTrack->created_at);
        
        // If more than 10 minutes have passed, be more lenient with duplicate detection
        // This allows for legitimate replays of songs
        if ($timeSinceLastFetch > 10) {
            Log::info('More than 10 minutes since last fetch, processing all tracks', [
                'minutes_passed' => $timeSinceLastFetch,
                'user_id' => $this->user->id
            ]);
            return count($fetchedTracks);
        }

        // Create a map of recent track IDs for faster lookup
        $recentTrackIds = $lastSession->pluck('track_id')->toArray();
        $lastSessionMap = [];
        foreach ($lastSession as $index => $track) {
            $lastSessionMap[$track->track_id] = $index;
        }

        // Look for the best overlap point
        $bestOverlapIndex = null;
        $bestOverlapScore = 0;

        // Check different starting positions in the fetched tracks
        for ($startPos = 0; $startPos < min(count($fetchedTracks), 30); $startPos++) {
            $overlapScore = 0;
            $matchedIndices = [];

            // Check how many tracks from this position match our recent history
            for ($i = 0; $i < min(20, count($fetchedTracks) - $startPos); $i++) {
                $fetchedTrackId = $fetchedTracks[$startPos + $i]['id'] ?? null;
                
                if ($fetchedTrackId && isset($lastSessionMap[$fetchedTrackId])) {
                    $storedIndex = $lastSessionMap[$fetchedTrackId];
                    // Give higher score to tracks that maintain relative order
                    if (empty($matchedIndices) || $storedIndex > max($matchedIndices)) {
                        $overlapScore += 2; // Bonus for maintaining order
                    } else {
                        $overlapScore += 1; // Still a match, but out of order
                    }
                    $matchedIndices[] = $storedIndex;
                }
            }

            if ($overlapScore > $bestOverlapScore) {
                $bestOverlapScore = $overlapScore;
                $bestOverlapIndex = $startPos;
            }
        }

        // If we found a good overlap (at least 3 matches), use it
        if ($bestOverlapScore >= 3 && $bestOverlapIndex !== null) {
            Log::info('Found overlap point for Apple Music tracks', [
                'overlap_index' => $bestOverlapIndex,
                'overlap_score' => $bestOverlapScore,
                'user_id' => $this->user->id
            ]);
            return $bestOverlapIndex;
        }

        // If tracks are being played in a very different order, look for any recent track
        // and be more flexible about what's considered "new"
        $firstMatchIndex = null;
        for ($i = 0; $i < count($fetchedTracks); $i++) {
            $fetchedTrackId = $fetchedTracks[$i]['id'] ?? null;
            if ($fetchedTrackId && in_array($fetchedTrackId, array_slice($recentTrackIds, 0, 5))) {
                $firstMatchIndex = $i;
                break;
            }
        }

        if ($firstMatchIndex !== null && $firstMatchIndex > 0) {
            Log::info('Found recent track at position, considering earlier tracks as new', [
                'position' => $firstMatchIndex,
                'user_id' => $this->user->id
            ]);
            return $firstMatchIndex;
        }

        // No clear overlap found - if it's been a while or the listening pattern is very different,
        // process all tracks to avoid missing legitimate plays
        Log::info('No clear overlap found, processing all tracks', [
            'fetched_count' => count($fetchedTracks),
            'user_id' => $this->user->id
        ]);
        return count($fetchedTracks);
    }

    /**
     * Fetch and attach producers for a track from Genius API
     */
    private function fetchAndAttachProducers($history, $trackName, $artistName)
    {
        $producers = $this->fetchProducersFromGenius($trackName, $artistName);

        foreach ($producers as $producerData) {
            if (!empty($producerData['name'])) {
                $producer = Producer::updateOrCreate(
                    ['name' => $producerData['name']],
                    ['image_url' => $producerData['image_url'] ?? null]
                );

                $history->producers()->syncWithoutDetaching([$producer->id]);
            }
        }
    }

    /**
     * Fetch producers from Genius API with caching
     */
    private function fetchProducersFromGenius($trackName, $artistName)
    {
        $cacheKey = "genius_producers_{$trackName}_{$artistName}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($trackName, $artistName) {
            $songId = $this->searchForSongOnGenius($trackName, $artistName);
            if (!$songId) {
                return [];
            }

            return $this->getProducersFromGeniusSong($songId);
        });
    }

    /**
     * Search for a song on Genius API
     */
    private function searchForSongOnGenius($trackName, $artistName)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.genius.token')
            ])->get('https://api.genius.com/search', [
                'q' => "{$trackName} {$artistName}"
            ]);

            if (!$response->successful()) {
                return null;
            }

            $hits = $response->json()['response']['hits'] ?? [];

            foreach ($hits as $hit) {
                $result = $hit['result'];
                if ($this->isGeniusSongMatch($result, $trackName, $artistName)) {
                    return $result['id'];
                }
            }
        } catch (\Exception $e) {
            Log::error('Genius search failed:', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Get producers from a Genius song
     */
    private function getProducersFromGeniusSong($songId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.genius.token')
            ])->get("https://api.genius.com/songs/{$songId}");

            if (!$response->successful()) {
                return [];
            }

            $song = $response->json()['response']['song'] ?? [];
            $producers = [];

            if (!empty($song['producer_artists'])) {
                foreach ($song['producer_artists'] as $producer) {
                    $imageUrl = $this->fetchGeniusProducerImage($producer['id']);

                    $producers[] = [
                        'name' => $producer['name'],
                        'image_url' => $imageUrl,
                    ];
                }
            }

            return $producers;
        } catch (\Exception $e) {
            Log::error('Failed to fetch song details:', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Fetch producer image from Genius API
     */
    private function fetchGeniusProducerImage($producerId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.genius.token')
            ])->get("https://api.genius.com/artists/{$producerId}");

            if (!$response->successful()) {
                return null;
            }

            return $response->json()['response']['artist']['image_url'] ?? null;
        } catch (\Exception $e) {
            Log::error('Failed to fetch producer image:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Check if Genius song matches the track
     */
    private function isGeniusSongMatch($result, $trackName, $artistName)
    {
        // Normalize names for comparison
        $resultTitle = $this->normalizeForComparison($result['title']);
        $resultArtist = $this->normalizeForComparison($result['primary_artist']['name']);
        $searchTrack = $this->normalizeForComparison($trackName);
        $searchArtist = $this->normalizeForComparison($artistName);

        // Check title match
        $titleMatch = str_contains($resultTitle, $searchTrack) || str_contains($searchTrack, $resultTitle);

        // Check artist match
        $artistMatch = str_contains($resultArtist, $searchArtist) || str_contains($searchArtist, $resultArtist);

        // If exact artist match fails, try fuzzy matching
        if (!$artistMatch) {
            $artistMatch = $this->fuzzyArtistMatch($resultArtist, $searchArtist);
        }

        return $titleMatch && $artistMatch;
    }

    /**
     * Fuzzy match for artist names
     */
    private function fuzzyArtistMatch($geniusArtist, $searchArtist)
    {
        // Replace common separators with spaces
        $geniusArtist = str_replace(['&', ',', 'feat.', 'ft.'], ' ', strtolower($geniusArtist));
        $searchArtist = str_replace(['&', ',', 'feat.', 'ft.'], ' ', strtolower($searchArtist));

        // Clean up spaces
        $geniusArtist = preg_replace('/\s+/', ' ', trim($geniusArtist));
        $searchArtist = preg_replace('/\s+/', ' ', trim($searchArtist));

        // Calculate similarity
        similar_text($geniusArtist, $searchArtist, $percentMatch);

        return $percentMatch >= 80; // 80% similarity threshold
    }

    /**
     * Normalize name for comparison
     */
    private function normalizeForComparison($name)
    {
        $name = strtolower($name);
        $name = preg_replace('/\([^)]*\)/', '', $name); // Remove parentheses content
        $name = preg_replace('/\[[^\]]*\]/', '', $name); // Remove brackets content
        $name = preg_replace('/[^\p{L}\p{N}\s]/u', '', $name); // Remove special characters
        return trim($name);
    }

    /**
     * Fetch and cache artist/singer image
     */
    private function fetchAndCacheArtistImage($artistName)
    {
        // Check if artist image already exists
        $existingImage = ArtistImage::where('artist_name', $artistName)->first();
        if ($existingImage) {
            return; // Already cached
        }

        $imageUrl = null;

        // First, try to search for artist on Genius
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.genius.token')
            ])->get('https://api.genius.com/search', [
                'q' => $artistName
            ]);

            if ($response->successful()) {
                $hits = $response->json()['response']['hits'] ?? [];

                // Look for exact artist match
                foreach ($hits as $hit) {
                    $artist = $hit['result']['primary_artist'] ?? null;
                    if ($artist && strcasecmp(trim($artist['name']), trim($artistName)) === 0) {
                        $imageUrl = $artist['image_url'] ?? null;
                        break;
                    }
                }

                // If no exact match, try similar match
                if (!$imageUrl && !empty($hits)) {
                    $firstArtist = $hits[0]['result']['primary_artist'] ?? null;
                    if ($firstArtist && $this->isArtistNameSimilar($artistName, $firstArtist['name'])) {
                        $imageUrl = $firstArtist['image_url'] ?? null;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch artist image from Genius', [
                'artist' => $artistName,
                'error' => $e->getMessage()
            ]);
        }

        // If no image found on Genius, try Spotify
        if (!$imageUrl) {
            try {
                // Get a Spotify token from user ID 1
                $spotifyUser = User::find(1);
                
                if ($spotifyUser && !empty($spotifyUser->spotify_token)) {
                    $searchUrl = 'https://api.spotify.com/v1/search?' . http_build_query([
                        'q' => $artistName,
                        'type' => 'artist',
                        'limit' => 1
                    ]);

                    $response = Http::withToken($spotifyUser->spotify_token)->get($searchUrl);

                    if ($response->status() === 401) {
                        // Refresh token and retry
                        $this->refreshSpotifyTokenForUser($spotifyUser);
                        $response = Http::withToken($spotifyUser->spotify_token)->get($searchUrl);
                    }

                    if ($response->successful()) {
                        $artists = $response->json()['artists']['items'] ?? [];
                        
                        if (!empty($artists)) {
                            $spotifyArtist = $artists[0];
                            
                            // Check if artist name matches
                            if ($this->isArtistNameSimilar($artistName, $spotifyArtist['name'])) {
                                // Get the largest image
                                $images = $spotifyArtist['images'] ?? [];
                                if (!empty($images)) {
                                    $imageUrl = $images[0]['url'];
                                    
                                    Log::info('Found artist image on Spotify', [
                                        'artist' => $artistName,
                                        'spotify_artist' => $spotifyArtist['name'],
                                        'image_url' => $imageUrl
                                    ]);
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to fetch artist image from Spotify', [
                    'artist' => $artistName,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Save artist image if found from either source
        if ($imageUrl) {
            ArtistImage::create([
                'artist_name' => $artistName,
                'image_url' => $imageUrl
            ]);

            Log::info('Cached artist image', [
                'artist' => $artistName,
                'image_url' => $imageUrl
            ]);
        } else {
            Log::warning('No artist image found on Genius or Spotify', [
                'artist' => $artistName
            ]);
        }
    }

    /**
     * Check if artist names are similar
     */
    private function isArtistNameSimilar($name1, $name2)
    {
        $name1 = strtolower(trim($name1));
        $name2 = strtolower(trim($name2));

        // Exact match
        if ($name1 === $name2) return true;

        // Remove common variations
        $clean1 = preg_replace('/\s*\(.*?\)|\s*\[.*?\]/', '', $name1);
        $clean2 = preg_replace('/\s*\(.*?\)|\s*\[.*?\]/', '', $name2);

        if ($clean1 === $clean2) return true;

        // Check if one contains the other
        if (strlen($name1) > 3 && strlen($name2) > 3) {
            if (strpos($name1, $name2) !== false || strpos($name2, $name1) !== false) {
                return true;
            }
        }

        // Similarity check
        similar_text($name1, $name2, $percent);
        return $percent > 85;
    }
}
