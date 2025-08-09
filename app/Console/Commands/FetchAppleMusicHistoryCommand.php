<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FetchListeningHistoryJob;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class FetchAppleMusicHistoryCommand extends Command
{
    protected $signature = 'app:fetch-apple-music-history';
    protected $description = 'Fetch listening history for Apple Music users';

    public function handle()
    {
        // Get users with Apple Music token
        $appleMusicUsers = User::whereNotNull('apple_music_token')->get();

        $processedCount = 0;

        foreach ($appleMusicUsers as $user) {
            FetchListeningHistoryJob::dispatch($user);
            $processedCount++;
            
            Log::info('Dispatched Apple Music fetch job', [
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);
        }

        $this->info("Dispatched Apple Music history jobs for {$processedCount} users");
        
        return Command::SUCCESS;
    }
}