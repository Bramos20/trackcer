<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ArtistImage;
use App\Models\Producer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
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
        
        // Split by common separators and clean up
        $artists = preg_split('/[,&]|\s+(?:feat\.|featuring|ft\.|with)\s+/i', $artistName);
        
        return array_map(function($artist) {
            return trim($artist);
        }, $artists);
    }
    
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }
        
        try {
            $results = [];
            
            // Search Artists from user's listening history
            $artistsQuery = $user->listeningHistory()
                ->where('artist_name', 'LIKE', "%{$query}%")
                ->select('artist_name')
                ->distinct()
                ->get();
            
            // Process artists to handle split names
            $processedArtists = collect();
            
            foreach ($artistsQuery as $history) {
                $individualArtists = $this->splitArtistNames($history->artist_name);
                
                foreach ($individualArtists as $artistName) {
                    $artistName = trim($artistName);
                    if (empty($artistName)) continue;
                    
                    // Check if this individual artist matches the search query
                    if (stripos($artistName, $query) !== false) {
                        $processedArtists->push($artistName);
                    }
                }
            }
            
            // Get unique artists and limit to 5
            $uniqueArtists = $processedArtists->unique()->take(5);
            
            // Get images for the artists
            $artistImages = ArtistImage::whereIn('artist_name', $uniqueArtists)
                ->pluck('image_url', 'artist_name');
            
            $artists = $uniqueArtists->map(function ($artistName) use ($artistImages) {
                return [
                    'id' => urlencode($artistName), // Use encoded artist name as ID
                    'name' => $artistName,
                    'type' => 'artist',
                    'image' => $artistImages->get($artistName)
                ];
            });
            
            // Search Producers that have tracks in user's listening history
            $producers = Producer::whereHas('tracks', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->where('name', 'LIKE', "%{$query}%")
                ->select('id', 'name', 'image_url')
                ->limit(5)
                ->get()
                ->map(function ($producer) {
                    return [
                        'id' => $producer->id,
                        'name' => $producer->name,
                        'type' => 'producer',
                        'image' => $producer->image_url
                    ];
                });
            
            // Search Tracks from user's listening history
            // Search by track name, artist name, or album name
            $tracksQuery = $user->listeningHistory()
                ->where(function($q) use ($query) {
                    $q->where('track_name', 'LIKE', "%{$query}%")
                      ->orWhere('artist_name', 'LIKE', "%{$query}%")
                      ->orWhere('album_name', 'LIKE', "%{$query}%");
                })
                ->select('id', 'track_name', 'artist_name', 'album_name', 'track_data', 'played_at', 'source', 'track_id', 
                    DB::raw("CASE 
                        WHEN track_name LIKE '{$query}%' THEN 1 
                        WHEN track_name LIKE '%{$query}%' THEN 2 
                        WHEN artist_name LIKE '{$query}%' THEN 3
                        WHEN artist_name LIKE '%{$query}%' THEN 4
                        ELSE 5 
                    END as relevance"))
                ->orderBy('relevance')
                ->orderBy('played_at', 'desc')
                ->get()
                ->unique('track_id')  // Keep only unique tracks by track_id
                ->take(10);  // Show more results when searching by artist
            
            // Process tracks to extract album artwork
            $tracks = $tracksQuery->map(function ($track) {
                // Extract album artwork from track_data JSON
                $trackData = json_decode($track->track_data, true);
                $albumArtwork = null;
                
                if ($track->source === 'Apple Music') {
                    // Handle Apple Music artwork
                    if (isset($trackData['attributes']['artwork']['url'])) {
                        $artwork = $trackData['attributes']['artwork'];
                        $albumArtwork = $artwork['url'];
                        // Replace width and height placeholders
                        $width = max(100, $artwork['width'] ?? 300);
                        $height = max(100, $artwork['height'] ?? 300);
                        $albumArtwork = str_replace('{w}', $width, $albumArtwork);
                        $albumArtwork = str_replace('{h}', $height, $albumArtwork);
                    }
                } elseif ($track->source === 'spotify') {
                    // Handle Spotify artwork
                    if (isset($trackData['album']['images'][0]['url'])) {
                        $albumArtwork = $trackData['album']['images'][0]['url'];
                    }
                }
                
                return [
                    'id' => $track->id,
                    'name' => $track->track_name,
                    'type' => 'track',
                    'albumArtwork' => $albumArtwork,
                    'artistName' => $track->artist_name
                ];
            });
            
            // Combine all results
            $results = collect()
                ->merge($artists)
                ->merge($producers)
                ->merge($tracks)
                ->values();
            
            return response()->json(['results' => $results]);
            
        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage(), [
                'query' => $query,
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'An error occurred while searching',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}