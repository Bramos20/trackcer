<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use App\Models\ListeningHistory;
use App\Models\Producer;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Notifications\TrackPlayedByAnotherUserNotification;

class FetchProducersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
        public $tries = 3;
        public $backoff = 60;
        public $failOnTimeout = false;

    protected $user;
    protected $maxRetries = 3;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function handle()
    {
        try {
            $tracks = ListeningHistory::with('producers')
                ->where('user_id', $this->user->id)
                ->whereDoesntHave('producers')
                ->get();

            foreach ($tracks->chunk(50) as $chunk) {
                foreach ($chunk as $track) {
                    $this->processTrack($track);
                    usleep(100000); // 100ms delay between tracks
                }
                sleep(2); // 2 second delay between chunks
            }
        } catch (\Exception $e) {
            Log::error('FetchProducersJob failed:', ['error' => $e->getMessage()]);
        }
    }

    private function processTrack($track)
    {
        if (empty($track->track_name) || empty($track->artist_name)) {
            return;
        }

        $producers = $this->fetchProducersFromGenius(
            $track->track_name,
            $track->artist_name
        );

        $producerIds = [];

        foreach ($producers as $producerData) {
            if (!empty($producerData['name'])) {
                $producer = Producer::updateOrCreate(
                    ['name' => $producerData['name']],
                    ['image_url' => $producerData['image_url'] ?? null]
                );

                $track->producers()->syncWithoutDetaching([$producer->id]);
                $producerIds[] = $producer->id;
            }
        }

        // 🔔 Notify users who previously listened to tracks by any of these producers
        if (!empty($producerIds)) {
            $currentUser = $track->user; // assuming your ListeningHistory model has a 'user' relationship

            $usersToNotify = User::where('id', '!=', $currentUser->id)
                ->whereHas('listeningHistory', function ($query) use ($producerIds) {
                    $query->whereHas('producers', function ($q) use ($producerIds) {
                        $q->whereIn('producers.id', $producerIds);
                    });
                })
                ->get();

            foreach ($usersToNotify as $user) {
                $user->notify(new TrackPlayedByAnotherUserNotification($track, $currentUser));
            }
        }
    }

    private function fetchProducersFromGenius($trackName, $artistName)
    {
        $cacheKey = "genius_producers_{$trackName}_{$artistName}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($trackName, $artistName) {
            $songId = $this->searchForSong($trackName, $artistName);
            if (!$songId) {
                return [];
            }

            return $this->getProducersFromSong($songId);
        });
    }

    private function searchForSong($trackName, $artistName)
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
                if ($this->isSongMatch($result, $trackName, $artistName)) {
                    return $result['id'];
                }
            }
        } catch (\Exception $e) {
            Log::error('Genius search failed:', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function getProducersFromSong($songId)
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
                    $imageUrl = $this->fetchGeniusArtistImage($producer['id']);

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

    private function isSongMatch($result, $trackName, $artistName)
    {
        // Normalize input
        $resultTitle = $this->normalizeName($result['title']);
        $resultArtist = $this->normalizeName($result['primary_artist']['name']);
        $searchTrack = $this->normalizeName($trackName);
        $searchArtist = $this->normalizeName($artistName);

        // Try exact match first
        $titleMatch = str_contains($resultTitle, $searchTrack) || str_contains($searchTrack, $resultTitle);
        $artistMatch = str_contains($resultArtist, $searchArtist) || str_contains($searchArtist, $resultArtist);

        // If exact match fails, try flexible matching
        if (!$artistMatch) {
            $artistMatch = $this->fuzzyMatch($resultArtist, $searchArtist);
        }

        return $titleMatch && $artistMatch;
    }

    private function fuzzyMatch($geniusArtist, $spotifyArtist)
    {
        // Replace commas and ampersands with spaces for better matching
        $geniusArtist = str_replace(['&', ','], ' ', strtolower($geniusArtist));
        $spotifyArtist = str_replace(['&', ','], ' ', strtolower($spotifyArtist));

        // Trim extra spaces
        $geniusArtist = preg_replace('/\s+/', ' ', trim($geniusArtist));
        $spotifyArtist = preg_replace('/\s+/', ' ', trim($spotifyArtist));

        // Compute similarity
        similar_text($geniusArtist, $spotifyArtist, $percentMatch);

        return $percentMatch >= 80; // If 80% similar, consider it a match
    }


    private function normalizeName($name)
    {
        $name = strtolower($name);
        $name = preg_replace('/\([^)]*\)/', '', $name); // Remove content in parentheses
        $name = preg_replace('/\[[^\]]*\]/', '', $name); // Remove content in brackets
        $name = preg_replace('/[^\p{L}\p{N}\s]/u', '', $name); // Remove special characters
        return trim($name);
    }
}
