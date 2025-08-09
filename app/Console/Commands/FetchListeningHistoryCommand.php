<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class FetchListeningHistoryCommand extends Command
{
    protected $signature = 'app:fetch-listening-history';
    protected $description = 'Fetch listening history for all users (calls both Spotify and Apple Music commands)';

    public function handle()
    {
        $this->info('Fetching listening history for all users...');
        
        // Call the Spotify command
        $this->info('Running Spotify fetch...');
        Artisan::call('app:fetch-spotify-history');
        $spotifyOutput = Artisan::output();
        $this->line($spotifyOutput);
        
        // Call the Apple Music command
        $this->info('Running Apple Music fetch...');
        Artisan::call('app:fetch-apple-music-history');
        $appleMusicOutput = Artisan::output();
        $this->line($appleMusicOutput);
        
        $this->info('Completed fetching listening history for all users');
        
        return Command::SUCCESS;
    }
}
