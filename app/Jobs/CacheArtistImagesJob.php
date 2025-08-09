<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ArtistImage;
use App\Models\User;

class CacheArtistImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 2;
    public $backoff = 30;

    protected $artistNames;

    /**
     * Create a new job instance.
     */
    public function __construct(array $artistNames)
    {
        $this->artistNames = $artistNames;
        $this->onQueue('low-priority'); // Use low priority queue
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info('Starting CacheArtistImagesJob', [
            'artist_count' => count($this->artistNames)
        ]);

        $processed = 0;
        $cached = 0;

        foreach ($this->artistNames as $artistName) {
            $artistName = trim($artistName);
            
            if (empty($artistName)) {
                continue;
            }

            // Check if we already have the image cached
            $existingImage = ArtistImage::where('artist_name', $artistName)->first();
            if ($existingImage) {
                continue; // Skip if already cached
            }

            try {
                // First try Genius API
                $imageUrl = $this->fetchArtistImageFromGenius($artistName);
                
                // If not found on Genius, try Spotify
                if (!$imageUrl) {
                    $imageUrl = $this->fetchArtistImageFromSpotify($artistName);
                }
                
                if ($imageUrl) {
                    ArtistImage::create([
                        'artist_name' => $artistName,
                        'image_url' => $imageUrl,
                    ]);
                    
                    $cached++;
                    Log::debug('Cached artist image', [
                        'artist' => $artistName,
                        'image_url' => $imageUrl
                    ]);
                }
                
                $processed++;
            } catch (\Exception $e) {
                Log::warning('Failed to cache artist image', [
                    'artist' => $artistName,
                    'error' => $e->getMessage()
                ]);
            }

            // Add small delay to respect API rate limits
            usleep(100000); // 100ms delay
        }

        Log::info('CacheArtistImagesJob completed', [
            'processed' => $processed,
            'cached' => $cached,
            'total' => count($this->artistNames)
        ]);
    }

    /**
     * Fetch artist image from Genius API
     */
    private function fetchArtistImageFromGenius($artistName)
    {
        try {
            // Try multiple search variations
            $searchVariations = $this->getSearchVariations($artistName);
            
            foreach ($searchVariations as $searchTerm) {
                $response = Http::timeout(15)->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.genius.token')
                ])->retry(3, 100)->get('https://api.genius.com/search', [
                    'q' => $searchTerm
                ]);

                if (!$response->successful()) {
                    continue;
                }

                $hits = $response->json()['response']['hits'] ?? [];
                
                if (empty($hits)) {
                    continue;
                }

                // First try exact match
                foreach ($hits as $hit) {
                    $artist = $hit['result']['primary_artist'] ?? null;
                    if ($artist && isset($artist['name']) && $this->isExactMatch($artist['name'], $artistName)) {
                        return $artist['image_url'] ?? null;
                    }
                }

                // Then try similar match
                foreach ($hits as $hit) {
                    $artist = $hit['result']['primary_artist'] ?? null;
                    if ($artist && isset($artist['name']) && $this->isSimilarMatch($artist['name'], $artistName)) {
                        return $artist['image_url'] ?? null;
                    }
                }

                // If we have hits but no exact/similar match, take the first artist if it's reasonably close
                if (!empty($hits)) {
                    $firstArtist = $hits[0]['result']['primary_artist'] ?? null;
                    if ($firstArtist && isset($firstArtist['name']) && $this->isReasonablyClose($firstArtist['name'], $artistName)) {
                        Log::info('Using first search result for artist image', [
                            'searched' => $artistName,
                            'found' => $firstArtist['name']
                        ]);
                        return $firstArtist['image_url'] ?? null;
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Genius search failed', [
                'artist' => $artistName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Fetch artist image from Genius artist endpoint
     */
    private function fetchGeniusArtistImage($artistId)
    {
        try {
            $response = Http::timeout(10)->withHeaders([
                'Authorization' => 'Bearer ' . config('services.genius.token')
            ])->get("https://api.genius.com/artists/{$artistId}");

            if (!$response->successful()) {
                return null;
            }

            return $response->json()['response']['artist']['image_url'] ?? null;
        } catch (\Exception $e) {
            Log::error('Failed to fetch artist image:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get search variations for an artist name
     */
    private function getSearchVariations($artistName)
    {
        $variations = [];
        
        // Original name
        $variations[] = $artistName;
        
        // Remove common suffixes/prefixes
        $cleanedName = preg_replace('/\s*\(.*?\)|\s*\[.*?\]/', '', $artistName);
        if ($cleanedName !== $artistName) {
            $variations[] = trim($cleanedName);
        }
        
        // Remove "The" prefix
        if (stripos($artistName, 'the ') === 0) {
            $variations[] = substr($artistName, 4);
        }
        
        // Try with "The" prefix if not present
        if (stripos($artistName, 'the ') !== 0) {
            $variations[] = 'The ' . $artistName;
        }
        
        // Remove common featuring patterns
        $patterns = [
            '/\s+(feat\.|featuring|ft\.|with)\s+.*/i',
            '/\s*&\s*.*/i',
            '/\s*,\s*.*/i'
        ];
        
        foreach ($patterns as $pattern) {
            $cleaned = preg_replace($pattern, '', $artistName);
            if ($cleaned !== $artistName && !empty(trim($cleaned))) {
                $variations[] = trim($cleaned);
            }
        }
        
        // Remove duplicates and empty values
        $variations = array_unique(array_filter($variations));
        
        return array_values($variations);
    }

    /**
     * Check if two artist names are an exact match (case insensitive)
     */
    private function isExactMatch($name1, $name2)
    {
        return strcasecmp(trim($name1), trim($name2)) === 0;
    }

    /**
     * Check if two artist names are similar
     */
    private function isSimilarMatch($name1, $name2)
    {
        $name1 = strtolower(trim($name1));
        $name2 = strtolower(trim($name2));
        
        // Exact match
        if ($name1 === $name2) {
            return true;
        }
        
        // Remove common variations and check again
        $clean1 = $this->cleanArtistName($name1);
        $clean2 = $this->cleanArtistName($name2);
        
        if ($clean1 === $clean2) {
            return true;
        }
        
        // Check if one contains the other (for bands with "The" prefix)
        if (strlen($name1) > 3 && strlen($name2) > 3) {
            if (str_contains($name1, $name2) || str_contains($name2, $name1)) {
                return true;
            }
        }
        
        // Calculate similarity percentage
        similar_text($clean1, $clean2, $percent);
        
        return $percent >= 85;
    }

    /**
     * Check if two artist names are reasonably close
     */
    private function isReasonablyClose($name1, $name2)
    {
        $clean1 = $this->cleanArtistName(strtolower(trim($name1)));
        $clean2 = $this->cleanArtistName(strtolower(trim($name2)));
        
        // Calculate similarity
        similar_text($clean1, $clean2, $percent);
        
        // More lenient threshold for "reasonably close"
        return $percent >= 70;
    }

    /**
     * Clean artist name for comparison
     */
    private function cleanArtistName($name)
    {
        // Convert to lowercase
        $name = strtolower($name);
        
        // Remove common variations
        $name = preg_replace('/\s*\(.*?\)|\s*\[.*?\]/', '', $name);
        
        // Remove "the" prefix
        $name = preg_replace('/^the\s+/i', '', $name);
        
        // Remove featuring artists
        $name = preg_replace('/\s+(feat\.|featuring|ft\.|with)\s+.*/i', '', $name);
        
        // Remove special characters but keep spaces
        $name = preg_replace('/[^\p{L}\p{N}\s]/u', '', $name);
        
        // Normalize spaces
        $name = preg_replace('/\s+/', ' ', trim($name));
        
        return $name;
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
                Log::debug('No Spotify access token available for artist image search');
                return null;
            }

            $searchUrl = 'https://api.spotify.com/v1/search?' . http_build_query([
                'q' => $artistName,
                'type' => 'artist',
                'limit' => 1
            ]);

            $response = Http::timeout(10)->withToken($spotifyUser->spotify_token)
                ->retry(3, 100)
                ->get($searchUrl);

            if ($response->status() === 401) {
                // Token expired, try to refresh
                if ($this->refreshSpotifyToken($spotifyUser)) {
                    // Retry with new token
                    $response = Http::timeout(10)->withToken($spotifyUser->spotify_token)
                        ->get($searchUrl);
                }
            }

            if (!$response->successful()) {
                Log::warning('Spotify artist search failed', [
                    'artist' => $artistName,
                    'status' => $response->status()
                ]);
                return null;
            }

            $artists = $response->json()['artists']['items'] ?? [];
            
            if (empty($artists)) {
                return null;
            }

            $spotifyArtist = $artists[0];
            
            // Verify artist name matches
            if (!$this->isSimilarMatch($artistName, $spotifyArtist['name'])) {
                Log::debug('Spotify artist name mismatch', [
                    'searched' => $artistName,
                    'found' => $spotifyArtist['name']
                ]);
                return null;
            }

            // Get the largest image
            $images = $spotifyArtist['images'] ?? [];
            if (!empty($images)) {
                Log::info('Found artist image on Spotify', [
                    'artist' => $artistName,
                    'spotify_artist' => $spotifyArtist['name']
                ]);
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
                Log::info('Successfully refreshed Spotify token for artist image search');
                return true;
            }

            Log::error('Failed to refresh Spotify token:', $data);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception during Spotify token refresh:', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}