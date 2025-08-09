<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FetchProducersJob;
use App\Models\User;

class FetchProducersCommand extends Command
{
    protected $signature = 'app:fetch-producers';
    protected $description = 'Fetch producers for all users';

    public function handle()
    {
        $users = User::where('spotify_token', '!=', null)
                     ->orWhere('apple_music_token', '!=', null)
                     ->get();

        foreach ($users as $user) {
            FetchProducersJob::dispatch($user);
        }

        $this->info("Dispatched producer fetch jobs for {$users->count()} users");
    }
}
