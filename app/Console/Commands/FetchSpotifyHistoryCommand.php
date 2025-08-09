<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FetchListeningHistoryJob;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class FetchSpotifyHistoryCommand extends Command
{
    protected $signature = 'app:fetch-spotify-history';
    protected $description = 'Fetch listening history for Spotify users';

    public function handle()
    {
        // Get users with Spotify token
        $spotifyUsers = User::whereNotNull('spotify_token')->get();

        $processedCount = 0;

        foreach ($spotifyUsers as $user) {
            FetchListeningHistoryJob::dispatch($user);
            $processedCount++;
            
            Log::info('Dispatched Spotify fetch job', [
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);
        }

        $this->info("Dispatched Spotify history jobs for {$processedCount} users");
        
        return Command::SUCCESS;
    }
}