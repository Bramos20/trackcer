<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListeningHistory;
use App\Models\Producer;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $range = $request->input('range', 'week'); // today, week, month, year
        
        // Calculate date range
        $startDate = $this->getStartDate($range);
        $endDate = now();
        
        $cacheKey = "dashboard_stats_{$user->id}_{$range}";
        
        // For "today", use shorter cache time (30 seconds) or no cache
        $cacheTime = $range === 'today' ? 30 : 300;
        
        try {
            $stats = Cache::remember($cacheKey, $cacheTime, function () use ($user, $startDate, $endDate, $range) {
            // Get basic stats
            // Format dates for string comparison since played_at is stored as string
            $startDateStr = $startDate->toIso8601String();
            $endDateStr = $endDate->toIso8601String();
            
            \Log::info('Dashboard date range', [
                'range' => $range,
                'startDate' => $startDateStr,
                'endDate' => $endDateStr,
                'timezone' => config('app.timezone'),
                'user_id' => $user->id
            ]);
            
            // Get basic counts
            $trackCount = DB::table('listening_history')
                ->where('user_id', $user->id)
                ->whereBetween('played_at', [$startDateStr, $endDateStr])
                ->count();
                
            // Debug: Check if there are any tracks today
            if ($range === 'today') {
                $recentTracks = DB::table('listening_history')
                    ->where('user_id', $user->id)
                    ->orderBy('played_at', 'desc')
                    ->limit(5)
                    ->get(['played_at', 'track_name', 'artist_name']);
                    
                // Also check what's in the date range
                $tracksInRange = DB::table('listening_history')
                    ->where('user_id', $user->id)
                    ->whereBetween('played_at', [$startDateStr, $endDateStr])
                    ->orderBy('played_at', 'desc')
                    ->limit(5)
                    ->get(['played_at', 'track_name']);
                    
                \Log::info('Recent tracks for today check', [
                    'recent_tracks' => $recentTracks,
                    'tracks_in_range' => $tracksInRange,
                    'track_count_today' => $trackCount,
                    'start_date' => $startDateStr,
                    'end_date' => $endDateStr
                ]);
            }
                
            $uniqueArtists = DB::table('listening_history')
                ->where('user_id', $user->id)
                ->whereBetween('played_at', [$startDateStr, $endDateStr])
                ->distinct()
                ->count('artist_name');
                
            // Get tracks to calculate total minutes from track_data
            // Use chunking to avoid memory issues with large datasets
            $totalMinutes = 0;
            
            $processedTracks = 0;
            ListeningHistory::where('user_id', $user->id)
                ->whereBetween('played_at', [$startDateStr, $endDateStr])
                ->chunk(100, function ($tracks) use (&$totalMinutes, &$processedTracks) {
                    foreach ($tracks as $track) {
                        $processedTracks++;
                        
                        // Get track data and ensure it's an array
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
                        }
                        
                        $totalMinutes += $duration / 60000;
                    }
                });
                
            \Log::info('Dashboard stats calculated', [
                'user_id' => $user->id,
                'date_range' => $range,
                'total_tracks' => $trackCount,
                'processed_tracks' => $processedTracks,
                'total_minutes' => round($totalMinutes),
                'unique_artists' => $uniqueArtists
            ]);
            
            $listeningStats = (object) [
                'total_tracks' => $trackCount,
                'total_minutes' => round($totalMinutes),
                'unique_artists' => $uniqueArtists
            ];
            
            // Get unique producers count
            $uniqueProducers = DB::table('listening_history')
                ->join('producer_track', 'listening_history.id', '=', 'producer_track.listening_history_id')
                ->where('listening_history.user_id', $user->id)
                ->whereBetween('listening_history.played_at', [$startDateStr, $endDateStr])
                ->distinct()
                ->count('producer_track.producer_id');
                
            // Debug unique producers for today
            if ($range === 'today') {
                $producerDebug = DB::table('listening_history')
                    ->join('producer_track', 'listening_history.id', '=', 'producer_track.listening_history_id')
                    ->where('listening_history.user_id', $user->id)
                    ->whereBetween('listening_history.played_at', [$startDateStr, $endDateStr])
                    ->select('producer_track.producer_id', 'listening_history.played_at')
                    ->limit(5)
                    ->get();
                    
                \Log::info('Producer debug for today', [
                    'unique_producers_count' => $uniqueProducers,
                    'sample_producer_tracks' => $producerDebug
                ]);
            }
            
            // Get top producers
            $topProducerIds = DB::table('producers')
                ->join('producer_track', 'producers.id', '=', 'producer_track.producer_id')
                ->join('listening_history', 'producer_track.listening_history_id', '=', 'listening_history.id')
                ->where('listening_history.user_id', $user->id)
                ->whereBetween('listening_history.played_at', [$startDateStr, $endDateStr])
                ->select('producers.id', DB::raw('COUNT(DISTINCT listening_history.id) as track_count'))
                ->groupBy('producers.id')
                ->orderBy('track_count', 'desc')
                ->limit(5)
                ->get();
                
            $topProducers = collect();
            foreach ($topProducerIds as $producerData) {
                $producer = Producer::find($producerData->id);
                if (!$producer) continue;
                
                // Calculate minutes for this producer
                $producerMinutes = 0;
                ListeningHistory::where('user_id', $user->id)
                    ->whereBetween('played_at', [$startDateStr, $endDateStr])
                    ->whereHas('producers', function($q) use ($producer) {
                        $q->where('producer_id', $producer->id);
                    })
                    ->chunk(50, function($tracks) use (&$producerMinutes) {
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
                            
                            $producerMinutes += $duration / 60000;
                        }
                    });
                
                $topProducers->push((object)[
                    'id' => $producer->id,
                    'name' => $producer->name,
                    'imageUrl' => $producer->image_url,
                    'trackCount' => $producerData->track_count,
                    'totalMinutes' => round($producerMinutes)
                ]);
            }
            
            // Get top artists
            $topArtistData = DB::table('listening_history')
                ->leftJoin('artist_images', 'listening_history.artist_name', '=', 'artist_images.artist_name')
                ->where('user_id', $user->id)
                ->whereBetween('played_at', [$startDateStr, $endDateStr])
                ->select(
                    'listening_history.artist_name',
                    'artist_images.image_url',
                    DB::raw('COUNT(*) as play_count')
                )
                ->groupBy('listening_history.artist_name', 'artist_images.image_url')
                ->orderBy('play_count', 'desc')
                ->limit(5)
                ->get();
                
            $topArtists = collect();
            foreach ($topArtistData as $artistData) {
                // Calculate minutes for this artist
                $artistMinutes = 0;
                ListeningHistory::where('user_id', $user->id)
                    ->whereBetween('played_at', [$startDateStr, $endDateStr])
                    ->where('artist_name', $artistData->artist_name)
                    ->chunk(50, function($tracks) use (&$artistMinutes) {
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
                            
                            $artistMinutes += $duration / 60000;
                        }
                    });
                
                $topArtists->push((object)[
                    'artistName' => $artistData->artist_name,
                    'imageUrl' => $artistData->image_url,
                    'playCount' => $artistData->play_count,
                    'totalMinutes' => round($artistMinutes)
                ]);
            }
            
            // Get genre breakdown
            $genreData = DB::table('genres')
                ->join('genre_track', 'genres.id', '=', 'genre_track.genre_id')
                ->join('listening_history', 'genre_track.listening_history_id', '=', 'listening_history.id')
                ->where('listening_history.user_id', $user->id)
                ->whereBetween('listening_history.played_at', [$startDateStr, $endDateStr])
                ->select(
                    'genres.id',
                    'genres.name',
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('genres.id', 'genres.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();
                
            $genreBreakdown = collect();
            foreach ($genreData as $genre) {
                // Calculate minutes for this genre
                $genreMinutes = 0;
                ListeningHistory::where('user_id', $user->id)
                    ->whereBetween('played_at', [$startDateStr, $endDateStr])
                    ->whereHas('genres', function($q) use ($genre) {
                        $q->where('genre_id', $genre->id);
                    })
                    ->chunk(50, function($tracks) use (&$genreMinutes) {
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
                            
                            $genreMinutes += $duration / 60000;
                        }
                    });
                
                $genreBreakdown->push((object)[
                    'name' => $genre->name,
                    'count' => $genre->count,
                    'totalMinutes' => round($genreMinutes)
                ]);
            }
            
            // Calculate total for percentages
            $totalGenreCounts = $genreBreakdown->sum('count');
            $totalGenreMinutes = $genreBreakdown->sum('totalMinutes');
            
            // Add percentages to each genre
            $genreBreakdown = $genreBreakdown->map(function ($genre) use ($totalGenreCounts, $totalGenreMinutes) {
                $genre->totalMinutes = round($genre->totalMinutes);
                $genre->percentage = $totalGenreCounts > 0 ? round(($genre->count / $totalGenreCounts) * 100, 1) : 0;
                $genre->minutesPercentage = $totalGenreMinutes > 0 ? round(($genre->totalMinutes / $totalGenreMinutes) * 100, 1) : 0;
                return $genre;
            });
            
            // Get listening trend (daily for week, weekly for month, monthly for year)
            $trend = $this->getListeningTrend($user->id, $range, $startDateStr, $endDateStr);
            
            return [
                'totalTracks' => $listeningStats->total_tracks ?? 0,
                'totalMinutes' => round($listeningStats->total_minutes ?? 0),
                'uniqueArtists' => $listeningStats->unique_artists ?? 0,
                'uniqueProducers' => $uniqueProducers,
                'topProducers' => $topProducers,
                'topArtists' => $topArtists,
                'genreBreakdown' => $genreBreakdown,
                'listeningTrend' => $trend,
                'dateRange' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                ],
            ];
            });
            
            return response()->json($stats);
        } catch (\Exception $e) {
            \Log::error('Dashboard stats error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'range' => $range
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch dashboard statistics',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }
    
    /**
     * Get start date based on range
     */
    private function getStartDate($range)
    {
        switch ($range) {
            case 'today':
                // For today, use the last 24 hours instead of calendar day
                // This avoids timezone issues
                return now()->subHours(24);
            case 'week':
                return now()->subDays(7);
            case 'month':
                return now()->subDays(30);
            case 'year':
                return now()->subDays(365);
            case 'all':
                // Return a very old date for "all time"
                return now()->subYears(50);
            default:
                return now()->subDays(7);
        }
    }
    
    /**
     * Get the top producer of the day
     */
    public function topProducerToday(Request $request)
    {
        $user = $request->user();
        $cacheKey = "top_producer_today_{$user->id}_" . now()->format('Y-m-d');
        
        try {
            $topProducer = Cache::remember($cacheKey, 300, function () use ($user) {
                // Use last 24 hours instead of calendar day to match dashboard behavior
                $endDate = now()->toIso8601String();
                $startDate = now()->subHours(24)->toIso8601String();
                
                // Debug logging
                \Log::info('Top producer today query', [
                    'user_id' => $user->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]);
                
                $producerData = DB::table('producers')
                    ->join('producer_track', 'producers.id', '=', 'producer_track.producer_id')
                    ->join('listening_history', 'producer_track.listening_history_id', '=', 'listening_history.id')
                    ->where('listening_history.user_id', $user->id)
                    ->whereBetween('listening_history.played_at', [$startDate, $endDate])
                    ->select(
                        'producers.id',
                        'producers.name',
                        'producers.image_url as imageUrl',
                        DB::raw('COUNT(DISTINCT listening_history.id) as totalTracks')
                    )
                    ->groupBy('producers.id', 'producers.name', 'producers.image_url')
                    ->orderBy('totalTracks', 'desc')
                    ->first();
                
                \Log::info('Top producer result', [
                    'found' => $producerData !== null,
                    'producer_name' => $producerData->name ?? 'none',
                    'track_count' => $producerData->totalTracks ?? 0
                ]);
                
                if ($producerData) {
                    // Convert to int
                    $producerData->totalTracks = (int) $producerData->totalTracks;
                    $producerData->id = (int) $producerData->id;
                    
                    // Calculate total minutes for this producer
                    $totalMinutes = 0;
                    ListeningHistory::where('user_id', $user->id)
                        ->whereBetween('played_at', [$startDate, $endDate])
                        ->whereHas('producers', function($q) use ($producerData) {
                            $q->where('producer_id', $producerData->id);
                        })
                        ->chunk(50, function($tracks) use (&$totalMinutes) {
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
                        });
                    
                    $producerData->totalMinutes = round($totalMinutes);
                }
                
                return $producerData;
            });
            
            if (!$topProducer) {
                return response()->json([
                    'producer' => null,
                    'message' => 'No producers played today'
                ]);
            }
            
            return response()->json([
                'producer' => $topProducer,
                'date' => now()->toDateString()
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Top producer today error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch top producer',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }
    
    /**
     * Get listening trend data
     */
    private function getListeningTrend($userId, $range, $startDateStr, $endDateStr)
    {
        try {
            // Get raw data first - we need to get track_data to extract duration
            $rawData = DB::table('listening_history')
                ->where('user_id', $userId)
                ->whereBetween('played_at', [$startDateStr, $endDateStr])
                ->select('played_at', 'track_data', 'source')
                ->get();
            
            if ($rawData->isEmpty()) {
                return collect([]);
            }
            
            // Process data in PHP based on range
            if ($range === 'year') {
                // Group by year-month
                $grouped = $rawData->groupBy(function ($item) {
                    $date = Carbon::parse($item->played_at);
                    return $date->format('Y-m');
                });
                
                $trend = $grouped->map(function ($items, $yearMonth) {
                    $totalMinutes = $items->sum(function ($item) {
                        // Extract duration from track_data
                        $trackData = is_string($item->track_data) ? json_decode($item->track_data, true) : $item->track_data;
                        $duration = 0;
                        
                        if ($item->source === 'spotify') {
                            $duration = $trackData['duration_ms'] ?? 0;
                        } elseif ($item->source === 'Apple Music') {
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
                        
                        return $duration / 60000;
                    });
                    
                    return (object) [
                        'date' => $yearMonth,
                        'track_count' => $items->count(),
                        'minutes' => round($totalMinutes)
                    ];
                })->values()->sortBy('date')->values();
            } else {
                // Group by date
                $grouped = $rawData->groupBy(function ($item) {
                    $date = Carbon::parse($item->played_at);
                    return $date->format('Y-m-d');
                });
                
                $trend = $grouped->map(function ($items, $date) {
                    $totalMinutes = $items->sum(function ($item) {
                        // Extract duration from track_data
                        $trackData = is_string($item->track_data) ? json_decode($item->track_data, true) : $item->track_data;
                        $duration = 0;
                        
                        if ($item->source === 'spotify') {
                            $duration = $trackData['duration_ms'] ?? 0;
                        } elseif ($item->source === 'Apple Music') {
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
                        
                        return $duration / 60000;
                    });
                    
                    return (object) [
                        'date' => $date,
                        'track_count' => $items->count(),
                        'minutes' => round($totalMinutes)
                    ];
                })->values()->sortBy('date')->values();
            }
            
            return $trend;
        } catch (\Exception $e) {
            // If there's an error, return empty trend data
            \Log::error('Listening trend error: ' . $e->getMessage());
            return collect([]);
        }
    }
}