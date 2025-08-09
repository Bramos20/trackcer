<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ListeningHistory;
use App\Models\ArtistImage;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CacheArtistImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artists:cache-images
                            {--force : Refresh images even if already cached}
                            {--limit= : Limit number of artists to process}
                            {--delay=200 : Delay between API calls in milliseconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache artist images from Genius and Spotify APIs for all artists in listening history';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎵 Starting artist image caching...');

        $force = $this->option('force');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $delay = (int) $this->option('delay') * 1000; // Convert to microseconds

        // Get all unique artist names from listening history
        $this->info('📊 Extracting unique artists from listening history...');
        $uniqueArtists = $this->extractUniqueArtists();

        $this->info("Found {$uniqueArtists->count()} unique artists");

        // Filter out already cached artists (unless force option is used)
        if (!$force) {
            $cachedArtists = ArtistImage::pluck('artist_name')->toArray();
            $uniqueArtists = $uniqueArtists->reject(function($artist) use ($cachedArtists) {
                return in_array($artist, $cachedArtists);
            });

            $this->info("Found {$uniqueArtists->count()} artists without cached images");
        }

        if ($uniqueArtists->isEmpty()) {
            $this->info('✅ All artists already have cached images!');
            return Command::SUCCESS;
        }

        // Apply limit if specified
        if ($limit) {
            $uniqueArtists = $uniqueArtists->take($limit);
            $this->info("Processing limited to {$limit} artists");
        }

        // Create progress bar
        $progressBar = $this->output->createProgressBar($uniqueArtists->count());
        $progressBar->setFormat('verbose');

        $this->info("\n🖼️  Fetching images from Genius and Spotify APIs...");
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($uniqueArtists as $artistName) {
            try {
                // First try Genius API
                $imageUrl = $this->fetchArtistImageFromGenius($artistName);
                $source = 'Genius';

                // If not found on Genius, try Spotify
                if (!$imageUrl) {
                    $imageUrl = $this->fetchArtistImageFromSpotify($artistName);
                    $source = 'Spotify';
                }

                if ($imageUrl) {
                    // Store or update the cached image
                    ArtistImage::updateOrCreate(
                        ['artist_name' => $artistName],
                        [
                            'image_url' => $imageUrl,
                            'updated_at' => now()
                        ]
                    );

                    $successCount++;
                    $progressBar->setMessage("✅ {$artistName} ({$source})", 'status');
                } else {
                    $errorCount++;
                    $progressBar->setMessage("❌ {$artistName} (no image found)", 'status');
                }

            } catch (\Exception $e) {
                $errorCount++;
                $progressBar->setMessage("💥 {$artistName} (error: {$e->getMessage()})", 'status');
                Log::error('Error caching artist image', [
                    'artist' => $artistName,
                    'error' => $e->getMessage()
                ]);
            }

            $progressBar->advance();

            // Rate limiting delay
            if ($delay > 0) {
                usleep($delay);
            }
        }

        $progressBar->finish();

        $this->newLine(2);
        $this->info("🎉 Image caching completed!");
        $this->info("✅ Successfully cached: {$successCount} images");
        $this->info("❌ Failed: {$errorCount} images");

        return Command::SUCCESS;
    }

    /**
     * Extract unique artists from listening history
     */
    private function extractUniqueArtists()
    {
        $allTracks = ListeningHistory::select('artist_name')->get();
        $uniqueArtists = collect();

        foreach ($allTracks as $track) {
            $individualArtists = $this->splitArtistNames($track->artist_name);
            foreach ($individualArtists as $artistName) {
                $artistName = trim($artistName);
                if (!empty($artistName)) {
                    $uniqueArtists->push($artistName);
                }
            }
        }

        return $uniqueArtists->unique()->values();
    }

    /**
     * Split artist names by common separators
     */
    private function splitArtistNames($artistName)
    {
        // List of known artists with commas that shouldn't be split
        $knownArtistsWithCommas = [
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

        // Check if this is a known artist that shouldn't be split
        foreach ($knownArtistsWithCommas as $knownArtist) {
            if (stripos($artistName, $knownArtist) !== false) {
                return [$artistName];
            }
        }

        // If comma is followed by "The" it might be part of the name
        if (preg_match('/,\s+The\b/i', $artistName)) {
            // Only split by featuring and ampersand
            $artists = preg_split('/[&]|\s+(?:feat\.|featuring|ft\.|with)\s+/i', $artistName);
        } else {
            // Normal splitting including commas
            $artists = preg_split('/[,&]|\s+(?:feat\.|featuring|ft\.|with)\s+/i', $artistName);
        }

        return array_map('trim', $artists);
    }

    /**
     * Fetch artist image from Genius API
     */
    private function fetchArtistImageFromGenius($artistName)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.genius.token')
            ])->get('https://api.genius.com/search', [
                'q' => $artistName
            ]);

            if (!$response->successful()) {
                return null;
            }

            $hits = $response->json()['response']['hits'] ?? [];

            foreach ($hits as $hit) {
                if (isset($hit['result']['primary_artist']['name']) &&
                    strtolower($hit['result']['primary_artist']['name']) === strtolower($artistName)) {
                    $artistId = $hit['result']['primary_artist']['id'];
                    return $this->fetchGeniusArtistImage($artistId);
                }
            }

            return null;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Fetch artist image from Genius artist endpoint
     */
    private function fetchGeniusArtistImage($artistId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.genius.token')
            ])->get("https://api.genius.com/artists/{$artistId}");

            if (!$response->successful()) {
                return null;
            }

            return $response->json()['response']['artist']['image_url'] ?? null;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Fetch artist image from Spotify API
     */
    private function fetchArtistImageFromSpotify($artistName)
    {
        try {
            // Get a Spotify token from user ID 1
            $spotifyUser = User::find(1);

            if (!$spotifyUser || empty($spotifyUser->spotify_token)) {
                return null;
            }

            $searchUrl = 'https://api.spotify.com/v1/search?' . http_build_query([
                'q' => $artistName,
                'type' => 'artist',
                'limit' => 1
            ]);

            $response = Http::withToken($spotifyUser->spotify_token)->get($searchUrl);

            if ($response->status() === 401) {
                // Token expired, try to refresh
                if ($this->refreshSpotifyToken($spotifyUser)) {
                    // Retry with new token
                    $response = Http::withToken($spotifyUser->spotify_token)->get($searchUrl);
                }
            }

            if (!$response->successful()) {
                return null;
            }

            $artists = $response->json()['artists']['items'] ?? [];

            if (empty($artists)) {
                return null;
            }

            $spotifyArtist = $artists[0];

            // Verify artist name matches (case insensitive)
            if (strcasecmp($artistName, $spotifyArtist['name']) !== 0) {
                // Try a more lenient match
                similar_text(strtolower($artistName), strtolower($spotifyArtist['name']), $percent);
                if ($percent < 85) {
                    return null;
                }
            }

            // Get the largest image
            $images = $spotifyArtist['images'] ?? [];
            if (!empty($images)) {
                return $images[0]['url'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to fetch artist image from Spotify', [
                'artist' => $artistName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Refresh Spotify token for a user
     */
    private function refreshSpotifyToken($user)
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
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Exception during Spotify token refresh:', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
