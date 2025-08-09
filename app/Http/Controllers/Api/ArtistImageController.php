<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArtistImage;
use App\Models\ListeningHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArtistImageController extends Controller
{
    /**
     * Get a single artist image by name
     */
    public function getArtistImage(Request $request)
    {
        $request->validate([
            'artist_name' => 'required|string'
        ]);
        
        $artistName = $request->input('artist_name');
        
        // Check if image is cached
        $artistImage = ArtistImage::where('artist_name', $artistName)->first();
        
        if ($artistImage) {
            return response()->json([
                'artist_name' => $artistName,
                'image_url' => $artistImage->image_url
            ]);
        }
        
        // Try to fetch from Genius API if not cached
        $token = config('services.genius.token');
        if ($token) {
            try {
                $imageUrl = $this->fetchArtistImageFromGenius($artistName, $token);
                if ($imageUrl) {
                    // Cache the result
                    ArtistImage::create([
                        'artist_name' => $artistName,
                        'image_url' => $imageUrl
                    ]);
                    
                    return response()->json([
                        'artist_name' => $artistName,
                        'image_url' => $imageUrl
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to fetch artist image', [
                    'artist' => $artistName,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return response()->json([
            'artist_name' => $artistName,
            'image_url' => null
        ]);
    }
    
    /**
     * Fetch artist images directly (no queue)
     */
    public function fetchImages(Request $request)
    {
        $limit = $request->input('limit', 10);
        $user = $request->user();
        
        // Get unique artists without images from user's listening history
        $artists = ListeningHistory::where('user_id', $user->id)
            ->select('artist_name')
            ->distinct()
            ->whereNotNull('artist_name')
            ->whereNotExists(function ($q) {
                $q->select('id')
                  ->from('artist_images')
                  ->whereColumn('artist_images.artist_name', 'listening_history.artist_name');
            })
            ->limit($limit)
            ->pluck('artist_name');
            
        if ($artists->isEmpty()) {
            return response()->json([
                'message' => 'All artists already have images',
                'fetched' => 0,
                'total_artists' => 0
            ]);
        }
        
        $token = config('services.genius.token');
        if (!$token) {
            return response()->json([
                'error' => 'Genius API token not configured',
                'message' => 'Please set GENIUS_TOKEN in environment variables'
            ], 500);
        }
        
        $fetched = 0;
        $failed = [];
        
        foreach ($artists as $artistName) {
            try {
                $imageUrl = $this->fetchArtistImageFromGenius($artistName, $token);
                
                if ($imageUrl) {
                    ArtistImage::create([
                        'artist_name' => $artistName,
                        'image_url' => $imageUrl
                    ]);
                    $fetched++;
                } else {
                    $failed[] = $artistName;
                }
                
                // Rate limiting
                usleep(200000); // 200ms
                
            } catch (\Exception $e) {
                Log::error('Failed to fetch artist image', [
                    'artist' => $artistName,
                    'error' => $e->getMessage()
                ]);
                $failed[] = $artistName;
            }
        }
        
        return response()->json([
            'message' => 'Artist images fetched',
            'fetched' => $fetched,
            'failed' => count($failed),
            'failed_artists' => array_slice($failed, 0, 5), // Show first 5 failed
            'total_processed' => $artists->count()
        ]);
    }
    
    /**
     * Batch fetch artist images
     */
    public function batch(Request $request)
    {
        $request->validate([
            'artist_names' => 'required|array',
            'artist_names.*' => 'string'
        ]);
        
        $artistNames = $request->input('artist_names');
        $images = [];
        
        // Get cached images
        $cachedImages = ArtistImage::whereIn('artist_name', $artistNames)
            ->pluck('image_url', 'artist_name')
            ->toArray();
        
        $images = $cachedImages;
        
        // Find artists without cached images
        $missingArtists = array_diff($artistNames, array_keys($cachedImages));
        
        // Optionally fetch missing images from Genius API
        if (!empty($missingArtists) && $request->input('fetch_missing', false)) {
            $token = config('services.genius.token');
            if ($token) {
                foreach (array_slice($missingArtists, 0, 10) as $artistName) { // Limit to 10 to avoid timeout
                    try {
                        $imageUrl = $this->fetchArtistImageFromGenius($artistName, $token);
                        if ($imageUrl) {
                            ArtistImage::create([
                                'artist_name' => $artistName,
                                'image_url' => $imageUrl
                            ]);
                            $images[$artistName] = $imageUrl;
                        }
                        usleep(200000); // Rate limiting
                    } catch (\Exception $e) {
                        Log::error('Failed to fetch artist image', [
                            'artist' => $artistName,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }
        
        return response()->json([
            'images' => $images,
            'cached_count' => count($cachedImages),
            'missing_count' => count($missingArtists),
            'missing_artists' => array_values($missingArtists)
        ]);
    }
    
    /**
     * Get artist image statistics
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        
        $totalArtists = ListeningHistory::where('user_id', $user->id)
            ->select('artist_name')
            ->distinct()
            ->whereNotNull('artist_name')
            ->count();
            
        $artistsWithImages = ListeningHistory::where('user_id', $user->id)
            ->select('artist_name')
            ->distinct()
            ->whereNotNull('artist_name')
            ->whereExists(function ($q) {
                $q->select('id')
                  ->from('artist_images')
                  ->whereColumn('artist_images.artist_name', 'listening_history.artist_name');
            })
            ->count();
            
        $artistsWithoutImages = $totalArtists - $artistsWithImages;
        
        // Get sample of artists without images
        $sampleWithoutImages = ListeningHistory::where('user_id', $user->id)
            ->select('artist_name')
            ->distinct()
            ->whereNotNull('artist_name')
            ->whereNotExists(function ($q) {
                $q->select('id')
                  ->from('artist_images')
                  ->whereColumn('artist_images.artist_name', 'listening_history.artist_name');
            })
            ->limit(10)
            ->pluck('artist_name');
        
        return response()->json([
            'total_artists' => $totalArtists,
            'artists_with_images' => $artistsWithImages,
            'artists_without_images' => $artistsWithoutImages,
            'coverage_percentage' => $totalArtists > 0 ? round(($artistsWithImages / $totalArtists) * 100, 2) : 0,
            'sample_without_images' => $sampleWithoutImages
        ]);
    }
    
    private function fetchArtistImageFromGenius($artistName, $token)
    {
        try {
            $response = Http::timeout(10)->withHeaders([
                'Authorization' => "Bearer $token"
            ])->get('https://api.genius.com/search', [
                'q' => $artistName
            ]);
            
            if (!$response->successful()) {
                return null;
            }
            
            $hits = $response->json()['response']['hits'] ?? [];
            
            // Look for exact match
            foreach ($hits as $hit) {
                $artist = $hit['result']['primary_artist'] ?? null;
                if ($artist && strcasecmp(trim($artist['name']), trim($artistName)) === 0) {
                    return $artist['image_url'] ?? null;
                }
            }
            
            // Try similar name match
            if (!empty($hits)) {
                $firstArtist = $hits[0]['result']['primary_artist'] ?? null;
                if ($firstArtist && $this->isSimilarName($artistName, $firstArtist['name'])) {
                    return $firstArtist['image_url'] ?? null;
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Genius API error', [
                'artist' => $artistName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    private function isSimilarName($name1, $name2)
    {
        $name1 = strtolower(trim($name1));
        $name2 = strtolower(trim($name2));
        
        if ($name1 === $name2) return true;
        
        // Remove parentheses and brackets
        $clean1 = preg_replace('/\s*\(.*?\)|\s*\[.*?\]/', '', $name1);
        $clean2 = preg_replace('/\s*\(.*?\)|\s*\[.*?\]/', '', $name2);
        
        if ($clean1 === $clean2) return true;
        
        // Check containment
        if (strlen($name1) > 3 && strlen($name2) > 3) {
            if (strpos($name1, $name2) !== false || strpos($name2, $name1) !== false) {
                return true;
            }
        }
        
        // Similarity check
        if (strlen($name1) > 5 && strlen($name2) > 5) {
            similar_text($name1, $name2, $percent);
            return $percent > 85;
        }
        
        return false;
    }
}