<?php

namespace App\Http\Controllers;

use App\Models\Producer;
use App\Models\ListeningHistory;
use App\Models\ArtistImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Inertia\Response;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class ArtistController extends Controller
{
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
    public function showAllArtists(Request $request): Response
    {
        $user = Auth::user();
        $searchQuery = $request->get('search', '');
        $start = $request->query('start');
        $end = $request->query('end');
        $range = $request->query('range', 'all');
        $perPage = $request->get('per_page', 16);
        $currentPage = (int) $request->get('page', 1);

        // Optimized approach: Use database aggregation to count unique tracks
        $query = ListeningHistory::where('user_id', $user->id)
            ->select('artist_name', 
                     DB::raw('COUNT(DISTINCT track_id) as track_count'),
                     DB::raw('COUNT(*) as play_count'),
                     DB::raw('MAX(played_at) as latest_played_at')
            );

        // Apply search filter
        if ($searchQuery) {
            $query->where('artist_name', 'LIKE', '%' . $searchQuery . '%');
        }

        // Apply date range filter
        if ($range === 'today') {
            $query->whereBetween('played_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()]);
        } elseif ($range === 'week') {
            $query->whereBetween('played_at', [Carbon::now()->startOfWeek(Carbon::MONDAY), Carbon::now()->endOfWeek(Carbon::SUNDAY)]);
        } elseif ($range === 'last7') {
            $query->whereBetween('played_at', [Carbon::now()->subDays(7)->startOfDay(), Carbon::now()->endOfDay()]);
        } elseif ($range === 'custom' && $start && $end) {
            $query->whereBetween('played_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()]);
        }

        // Group by artist name and order by track count
        $query->groupBy('artist_name')
              ->orderByDesc('track_count');

        // Get all results without pagination first to properly sort after splitting
        $allArtists = $query->get();

        // Process the paginated results to split artists and aggregate properly
        $processedArtists = collect();
        $artistNames = collect();
        
        // Initialize empty popularity data
        $artistPopularityData = [];

        foreach ($allArtists as $artistGroup) {
            $individualArtists = $this->splitArtistNames($artistGroup->artist_name);
            
            foreach ($individualArtists as $artistName) {
                $artistName = trim($artistName);
                if (empty($artistName)) continue;
                
                $artistNames->push($artistName);
                
                if (!$processedArtists->has($artistName)) {
                    $processedArtists->put($artistName, [
                        'artist_name' => $artistName,
                        'track_count' => 0,
                        'play_count' => 0,
                        'total_minutes' => 0,
                        'average_popularity' => 0,
                        'latest_played_at' => null,
                    ]);
                }
                
                $artistData = $processedArtists->get($artistName);
                $artistData['track_count'] += $artistGroup->track_count;
                $artistData['play_count'] = ($artistData['play_count'] ?? 0) + $artistGroup->play_count;
                
                // Get popularity from pre-calculated data
                $popularity = $artistPopularityData[$artistGroup->artist_name] ?? 0;
                
                // For split artists, we'll use the same popularity for each part
                if ($popularity > 0) {
                    // If this is the first time we're seeing this artist, set the popularity
                    if ($artistData['average_popularity'] == 0) {
                        $artistData['average_popularity'] = $popularity;
                    }
                }
                
                // Update latest played
                if (!$artistData['latest_played_at'] || $artistGroup->latest_played_at > $artistData['latest_played_at']) {
                    $artistData['latest_played_at'] = $artistGroup->latest_played_at;
                }
                
                $processedArtists->put($artistName, $artistData);
            }
        }

        // Sort processed artists by track count (convert to integer to ensure proper sorting)
        $sortedArtists = $processedArtists->sortByDesc(function ($artist) {
            return (int) $artist['track_count'];
        })->values();
        
        // Preload artist images for processed artists
        $uniqueArtistNames = $sortedArtists->pluck('artist_name')->unique();
        $cachedImages = ArtistImage::whereIn('artist_name', $uniqueArtistNames)
            ->pluck('image_url', 'artist_name');

        // Get latest track info and genres for the current page - ensure values() to maintain order
        $pageArtists = $sortedArtists->values()->forPage($currentPage, $perPage);
        $artistNamesForPage = $pageArtists->pluck('artist_name');
        
        // Calculate duration for artists on current page
        $artistDurations = collect();
        foreach ($artistNamesForPage as $artistName) {
            $totalDuration = ListeningHistory::where('user_id', $user->id)
                ->where('artist_name', 'LIKE', '%' . $artistName . '%');
                
            // Apply date range filter if needed
            if ($range === 'today') {
                $totalDuration->whereBetween('played_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()]);
            } elseif ($range === 'week') {
                $totalDuration->whereBetween('played_at', [Carbon::now()->startOfWeek(Carbon::MONDAY), Carbon::now()->endOfWeek(Carbon::SUNDAY)]);
            } elseif ($range === 'last7') {
                $totalDuration->whereBetween('played_at', [Carbon::now()->subDays(7)->startOfDay(), Carbon::now()->endOfDay()]);
            } elseif ($range === 'custom' && $start && $end) {
                $totalDuration->whereBetween('played_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()]);
            }
            
            $tracks = $totalDuration->get();
            $duration = 0;
            
            foreach ($tracks as $track) {
                $trackData = is_array($track->track_data) ? $track->track_data : json_decode($track->track_data, true);
                
                if ($track->source === 'spotify' && isset($trackData['duration_ms'])) {
                    $duration += $trackData['duration_ms'];
                } elseif ($track->source === 'Apple Music') {
                    if (isset($trackData['attributes']['durationInMillis'])) {
                        $duration += $trackData['attributes']['durationInMillis'];
                    } elseif (isset($trackData['data'][0]['attributes']['durationInMillis'])) {
                        $duration += $trackData['data'][0]['attributes']['durationInMillis'];
                    }
                }
            }
            
            $artistDurations->put($artistName, $duration / 60000); // Convert to minutes
        }
        
        // Calculate popularity only for artists on current page
        $pageArtistPopularity = $this->calculateArtistPopularity($user, $artistNamesForPage->toArray());
        
        // Debug: Log calculated popularity
        Log::info('Page artist popularity calculated:', [
            'count' => count($pageArtistPopularity),
            'sample' => array_slice($pageArtistPopularity, 0, 3)
        ]);
        
        // Fetch latest track details and genres only for current page artists
        $latestTracks = collect();
        $artistGenres = collect();
        
        if ($artistNamesForPage->isNotEmpty()) {
            // Get latest track for each artist
            $latestTrackQuery = ListeningHistory::where('user_id', $user->id)
                ->whereIn('artist_name', function($query) use ($artistNamesForPage, $user) {
                    $query->select('artist_name')
                        ->from('listening_history')
                        ->where('user_id', $user->id)
                        ->whereIn('artist_name', $artistNamesForPage)
                        ->groupBy('artist_name');
                })
                ->orderBy('played_at', 'desc');
            
            // Get one latest track per artist
            foreach ($artistNamesForPage as $artistName) {
                $latestTrack = (clone $latestTrackQuery)->where('artist_name', 'LIKE', '%' . $artistName . '%')->first();
                if ($latestTrack) {
                    $latestTracks->put($artistName, $latestTrack);
                }
            }
            
            // Get genres for artists on current page
            $genreData = ListeningHistory::where('user_id', $user->id)
                ->whereIn('artist_name', $artistNamesForPage)
                ->with('genres:id,name')
                ->select('artist_name', 'id')
                ->get()
                ->groupBy('artist_name');
            
            foreach ($genreData as $artistName => $tracks) {
                $genres = $tracks->flatMap(fn($track) => $track->genres)->unique('id')->take(10);
                $artistGenres->put($artistName, $genres);
            }
        }

        // Format final data - maintain the order from sorted artists
        $finalArtistsData = $pageArtists->map(function ($item) use ($cachedImages, $latestTracks, $artistGenres, $pageArtistPopularity, $artistDurations) {
            $latestTrack = $latestTracks->get($item['artist_name']);
            $genres = $artistGenres->get($item['artist_name'], collect());
            
            // Get calculated popularity for this artist
            $calculatedPopularity = $pageArtistPopularity[$item['artist_name']] ?? 0;
            
            // Get duration for this artist
            $totalMinutes = $artistDurations->get($item['artist_name'], 0);

            return [
                'artist_name' => $item['artist_name'],
                'track_count' => $item['track_count'],
                'play_count' => $item['play_count'] ?? $item['track_count'], // Fallback for compatibility
                'total_minutes' => round($totalMinutes, 2),
                'average_popularity' => round($calculatedPopularity, 2),
                'latest_track' => $latestTrack,
                'genres' => $genres->pluck('name')->values(),
                'image_url' => $cachedImages->get($item['artist_name']),
            ];
        });

        // Create a manual paginator with the processed data
        $paginator = new LengthAwarePaginator(
            $finalArtistsData->values(),
            $sortedArtists->count(), // Use the total count of sorted artists
            $perPage,
            $currentPage,
            [
                'path' => route('artists'),
                'pageName' => 'page',
            ]
        );
        
        // Append query parameters
        $paginator->appends($request->query());

        $topArtistsByRange = match ($range) {
            'today' => $this->getTopArtistsByRange(Carbon::now()->startOfDay(), Carbon::now()->endOfDay()),
            'week' => $this->getTopWeeklyArtists(),
            'last7' => $this->getTopArtistsByRange(Carbon::now()->subDays(7), Carbon::now()),
            'custom' => ($start && $end)
                ? $this->getTopArtistsByRange(Carbon::parse($start), Carbon::parse($end))
                : collect(),
            default => $this->getTopArtists(),
        };

        return Inertia::render('Artists', [
            'artistsData' => [
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'links' => $paginator->linkCollection(),
                ],
            ],
            'searchQuery' => $searchQuery,
            'topArtists' => $this->getTopArtists()->toArray(),
            'weeklyTopArtists' => $this->getTopWeeklyArtists()->toArray(),
            'topArtistsByRange' => $topArtistsByRange->toArray(),
            'tab' => $request->get('tab', 'list'),
            'range' => $range,
        ]);
    }

    private function getTopArtists()
    {
        // Use database aggregation instead of loading all tracks
        $topArtists = ListeningHistory::whereNotNull('artist_name')
            ->select('artist_name', DB::raw('COUNT(*) as track_count'))
            ->groupBy('artist_name')
            ->orderByDesc('track_count')
            ->limit(10)
            ->get();

        // Process to handle split artists
        $artistCounts = collect();
        
        foreach ($topArtists as $artistGroup) {
            $individualArtists = $this->splitArtistNames($artistGroup->artist_name);
            foreach ($individualArtists as $artistName) {
                $artistName = trim($artistName);
                if (empty($artistName)) continue;
                
                $artistCounts->put($artistName, 
                    $artistCounts->get($artistName, 0) + $artistGroup->track_count
                );
            }
        }

        $topArtistNames = $artistCounts->sortByDesc(function ($count) {
            return $count;
        })->take(10);

        // Fetch images for top artists
        $artistImages = ArtistImage::whereIn('artist_name', $topArtistNames->keys())
            ->pluck('image_url', 'artist_name');

        return $topArtistNames->map(function ($count, $artistName) use ($artistImages) {
            return (object) [
                'artist_name' => $artistName,
                'track_count' => $count,
                'image_url' => $artistImages->get($artistName)
            ];
        })->values();
    }

    private function getTopArtistsByRange($start, $end)
    {
        if (!$start || !$end) {
            return collect();
        }

        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->endOfDay();

        // Use database aggregation
        $topArtists = ListeningHistory::whereNotNull('artist_name')
            ->whereBetween('played_at', [$startDate, $endDate])
            ->select('artist_name', DB::raw('COUNT(*) as track_count'))
            ->groupBy('artist_name')
            ->orderByDesc('track_count')
            ->limit(10)
            ->get();
        
        // Process to handle split artists
        $artistCounts = collect();

        foreach ($topArtists as $artistGroup) {
            $individualArtists = $this->splitArtistNames($artistGroup->artist_name);
            foreach ($individualArtists as $artistName) {
                $artistName = trim($artistName);
                if (empty($artistName)) continue;
                
                $artistCounts->put($artistName, 
                    $artistCounts->get($artistName, 0) + $artistGroup->track_count
                );
            }
        }

        $topArtistNames = $artistCounts->sortByDesc(function ($count) {
            return $count;
        })->take(10);

        // Fetch images for top artists
        $artistImages = ArtistImage::whereIn('artist_name', $topArtistNames->keys())
            ->pluck('image_url', 'artist_name');

        return $topArtistNames->map(function ($count, $artistName) use ($artistImages) {
            return (object) [
                'artist_name' => $artistName,
                'track_count' => $count,
                'image_url' => $artistImages->get($artistName)
            ];
        })->values();
    }

    private function getTopWeeklyArtists()
    {
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        // Use database aggregation
        $topArtists = ListeningHistory::whereNotNull('artist_name')
            ->whereBetween('played_at', [$startOfWeek, $endOfWeek])
            ->select('artist_name', DB::raw('COUNT(*) as track_count'))
            ->groupBy('artist_name')
            ->orderByDesc('track_count')
            ->limit(10)
            ->get();
        
        // Process to handle split artists
        $artistCounts = collect();

        foreach ($topArtists as $artistGroup) {
            $individualArtists = $this->splitArtistNames($artistGroup->artist_name);
            foreach ($individualArtists as $artistName) {
                $artistName = trim($artistName);
                if (empty($artistName)) continue;
                
                $artistCounts->put($artistName, 
                    $artistCounts->get($artistName, 0) + $artistGroup->track_count
                );
            }
        }

        $topArtistNames = $artistCounts->sortByDesc(function ($count) {
            return $count;
        })->take(10);

        // Fetch images for top artists
        $artistImages = ArtistImage::whereIn('artist_name', $topArtistNames->keys())
            ->pluck('image_url', 'artist_name');

        return $topArtistNames->map(function ($count, $artistName) use ($artistImages) {
            return (object) [
                'artist_name' => $artistName,
                'play_count' => $count,
                'image_url' => $artistImages->get($artistName)
            ];
        })->values();
    }

    private function fetchImageForArtistName($artistName)
    {
        // Check if we already have the image cached
        $cachedImage = ArtistImage::where('artist_name', $artistName)->first();
        if ($cachedImage) {
            return $cachedImage->image_url;
        }

        // If not cached, fetch from Genius API
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
                if ($hit['result']['primary_artist']['name'] && 
                    strtolower($hit['result']['primary_artist']['name']) === strtolower($artistName)) {
                    $artistId = $hit['result']['primary_artist']['id'];
                    $imageUrl = $this->fetchGeniusArtistImage($artistId);
                    
                    // Store in database for future use
                    if ($imageUrl) {
                        ArtistImage::create([
                            'artist_name' => $artistName,
                            'image_url' => $imageUrl,
                            'genius_artist_id' => $artistId,
                        ]);
                    }
                    
                    return $imageUrl;
                }
            }

            return null; // No match found
        } catch (\Exception $e) {
            Log::error('Genius search failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

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
            Log::error('Failed to fetch artist image:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function show($artistName)
    {
        // Decode the artist name from URL encoding
        $artistName = urldecode($artistName);
        $user = Auth::user();

        // Get all plays for tracks by this artist
        $allPlays = ListeningHistory::where('user_id', $user->id)
            ->where('artist_name', 'LIKE', '%' . $artistName . '%')
            ->with(['genres', 'producers'])
            ->get()
            ->filter(function ($track) use ($artistName) {
                // Double-check that the artist actually appears in the track
                $individualArtists = $this->splitArtistNames($track->artist_name);
                return in_array($artistName, array_map('trim', $individualArtists));
            });

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

        if ($allPlays->isEmpty()) {
            abort(404, 'Artist not found in your listening history.');
        }

        $producerCollaborators = $this->producerCollaboratorsForArtist($artistName);

        // Use cached image, fallback to Genius API only if not cached
        $cachedImage = ArtistImage::where('artist_name', $artistName)->first();
        $artistImage = $cachedImage ? $cachedImage->image_url : $this->fetchImageForArtistName($artistName);

        $genres = $tracks->flatMap(fn($track) => $track->genres)->unique('id');

        // Calculate total minutes from ALL plays (not just unique tracks)
        $totalMinutes = $allPlays->sum(function ($track) {
            $data = json_decode($track->track_data, true);
            return $track->source === 'spotify'
                ? ($data['duration_ms'] ?? 0)
                : ($data['attributes']['durationInMillis'] ?? 0);
        }) / 60000;

        $averagePopularity = round($tracks->avg(function ($track) {
            $data = json_decode($track->track_data, true);
            return $track->source === 'spotify'
                ? ($data['popularity'] ?? 0)
                : ($data['attributes']['trackNumber'] ?? 0); // fallback
        }));

        $durationByGenre = $genres->mapWithKeys(function ($genre) use ($tracks) {
            $total = $tracks->filter(fn($track) =>
                $track->genres->contains('id', $genre->id)
            )->sum(function ($track) {
                $data = json_decode($track->track_data, true);
                return $track->source === 'spotify'
                    ? ($data['duration_ms'] ?? 0)
                    : ($data['attributes']['durationInMillis'] ?? 0);
            });
            return [$genre->name => round($total / 60000, 2)];
        });

        $popularityData = $tracks->map(function ($track) {
            $data = json_decode($track->track_data, true);
            return $track->source === 'spotify'
                ? ($data['popularity'] ?? 0)
                : null;
        })->filter()->values();

        // Weekly data
        $sevenDaysAgo = now()->subDays(6)->startOfDay();
        $today = now()->endOfDay();

        $weeklyListeningData = collect(range(0, 6))->map(function ($i) use ($allPlays) {
            $date = now()->subDays(6 - $i)->toDateString();

            $dayPlays = $allPlays->filter(function ($track) use ($date) {
                return \Carbon\Carbon::parse($track->played_at)->toDateString() === $date;
            });

            $totalMs = $dayPlays->sum(function ($track) {
                $data = is_string($track->track_data)
                    ? json_decode($track->track_data, true)
                    : $track->track_data;

                return $track->source === 'spotify'
                    ? ($data['duration_ms'] ?? 0)
                    : ($data['attributes']['durationInMillis'] ?? 0);
            });

            return [
                'day' => \Carbon\Carbon::parse($date)->format('D'),
                'duration' => round($totalMs / 60000, 2),
            ];
        });

        return Inertia::render('ArtistShow', [
            'artist' => [
                'name' => $artistName,
                'image_url' => $artistImage,
            ],
            'stats' => [
                'total_minutes' => round($totalMinutes, 2),
                'average_popularity' => $averagePopularity,
                'durationByGenre' => $durationByGenre->toArray(),
                'popularityDistribution' => $popularityData->toArray(),
                'weeklyListeningData' => $weeklyListeningData->toArray(),
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
            'producerCollaborators' => $producerCollaborators ?? [],
            'currentProducerId' => 'artist',
            'topProducersForArtist' => $this->topProducersForArtist($artistName)->toArray(),
        ]);
    }

    private function calculateArtistPopularity($user, $artistNames)
    {
        // We need to get all tracks that might contain any of these artists
        // First, let's get all unique artist names from the database
        $allArtistNames = ListeningHistory::where('user_id', $user->id)
            ->pluck('artist_name')
            ->unique();
        
        // Filter to only those that contain our target artists
        $relevantArtistNames = $allArtistNames->filter(function($dbArtistName) use ($artistNames) {
            foreach ($artistNames as $artistName) {
                if (stripos($dbArtistName, $artistName) !== false) {
                    return true;
                }
            }
            return false;
        });
        
        // Fetch actual tracks to calculate popularity
        $tracks = ListeningHistory::where('user_id', $user->id)
            ->whereIn('artist_name', $relevantArtistNames)
            ->get();
            
        $popularityByArtist = [];
        
        // Debug: Log first few tracks
        $debugCount = 0;
        
        foreach ($tracks as $track) {
            // Ensure track_data is properly decoded
            $trackData = $track->track_data;
            if (is_string($trackData)) {
                $trackData = json_decode($trackData, true);
            }
            
            // Ensure popularity_data is properly decoded
            $popData = $track->popularity_data;
            if (is_string($popData)) {
                $popData = json_decode($popData, true);
            }
            
            $popularity = 0;
            
            if ($track->source === 'spotify') {
                // For Spotify, popularity is directly in track_data
                if (is_array($trackData) && isset($trackData['popularity'])) {
                    $popularity = (int) $trackData['popularity'];
                }
            } elseif ($track->source === 'Apple Music') {
                // For Apple Music, popularity is in popularity_data
                if (is_array($popData) && isset($popData['popularity'])) {
                    $popularity = (int) $popData['popularity'];
                }
            }
            
            // Debug first few tracks
            if ($debugCount < 3) {
                Log::info('Track popularity debug:', [
                    'artist' => $track->artist_name,
                    'source' => $track->source,
                    'track_name' => $track->track_name,
                    'has_track_data' => !empty($trackData),
                    'is_track_data_array' => is_array($trackData),
                    'spotify_popularity' => (is_array($trackData) && isset($trackData['popularity'])) ? $trackData['popularity'] : 'not found',
                    'has_popularity_data' => !empty($popData),
                    'is_popularity_data_array' => is_array($popData),
                    'apple_popularity' => (is_array($popData) && isset($popData['popularity'])) ? $popData['popularity'] : 'not found',
                    'calculated_popularity' => $popularity
                ]);
                $debugCount++;
            }
            
            // Split the artist name and assign popularity to each individual artist
            $individualArtists = $this->splitArtistNames($track->artist_name);
            foreach ($individualArtists as $individualArtist) {
                $individualArtist = trim($individualArtist);
                
                // Only process if this artist is in our target list
                if (!in_array($individualArtist, $artistNames)) {
                    continue;
                }
                
                if (!isset($popularityByArtist[$individualArtist])) {
                    $popularityByArtist[$individualArtist] = [
                        'sum' => 0,
                        'count' => 0
                    ];
                }
                
                if ($popularity > 0) {
                    $popularityByArtist[$individualArtist]['sum'] += $popularity;
                    $popularityByArtist[$individualArtist]['count']++;
                }
            }
        }
        
        // Calculate averages
        $averages = [];
        foreach ($artistNames as $artistName) {
            if (isset($popularityByArtist[$artistName]) && $popularityByArtist[$artistName]['count'] > 0) {
                $averages[$artistName] = round($popularityByArtist[$artistName]['sum'] / $popularityByArtist[$artistName]['count'], 2);
            } else {
                $averages[$artistName] = 0;
            }
        }
        
        // Debug: Log final averages
        Log::info('Artist popularity averages:', [
            'requested_artists' => count($artistNames),
            'calculated_artists' => count($averages),
            'sample' => array_slice($averages, 0, 5)
        ]);
        
        return $averages;
    }
    
    private function producerCollaboratorsForArtist(string $artistName)
    {
        $user = Auth::user();

        // Optimize: Get track IDs directly from database
        $trackIds = ListeningHistory::where('user_id', $user->id)
            ->where('artist_name', 'LIKE', '%' . $artistName . '%')
            ->pluck('id');

        // Find producers who worked on those tracks
        $collaborators = Producer::whereHas('tracks', function ($q) use ($trackIds) {
                $q->whereIn('listening_history_id', $trackIds);
            })
            ->withCount(['tracks as collaboration_count' => function ($q) use ($trackIds) {
                $q->whereIn('listening_history_id', $trackIds);
            }])
            ->distinct()
            ->get();

        // Add latest track and genres for each producer
        foreach ($collaborators as $producer) {
            // Get latest track for this producer with this artist
            $latestTrack = ListeningHistory::where('user_id', $user->id)
                ->where('artist_name', 'LIKE', '%' . $artistName . '%')
                ->whereHas('producers', function ($q) use ($producer) {
                    $q->where('producers.id', $producer->id);
                })
                ->orderBy('played_at', 'desc')
                ->first();
            
            $producer->latest_track = $latestTrack;
            
            // Get genres from tracks produced by this producer for this artist
            $genres = ListeningHistory::where('user_id', $user->id)
                ->where('artist_name', 'LIKE', '%' . $artistName . '%')
                ->whereHas('producers', function ($q) use ($producer) {
                    $q->where('producers.id', $producer->id);
                })
                ->with('genres')
                ->get()
                ->flatMap(fn($track) => $track->genres)
                ->unique('id')
                ->take(10)
                ->pluck('name');
            
            $producer->genres = $genres;
        }

        return $collaborators;
    }

    private function topProducersForArtist(string $artistName, int $limit = 10)
    {
        $user = Auth::user();
        
        // Optimize: Get track IDs directly from database
        $trackIds = ListeningHistory::where('user_id', $user->id)
            ->where('artist_name', 'LIKE', '%' . $artistName . '%')
            ->pluck('id');

        return Producer::select('producers.id', 'producers.name', 'producers.image_url')
            ->join('producer_track', 'producers.id', '=', 'producer_track.producer_id')
            ->whereIn('producer_track.listening_history_id', $trackIds)
            ->groupBy('producers.id', 'producers.name', 'producers.image_url')
            ->selectRaw('COUNT(*) as track_count')
            ->orderByDesc('track_count')
            ->limit($limit)
            ->get();
    }

}