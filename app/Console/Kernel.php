<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\FetchListeningHistoryJob;
use App\Jobs\FetchProducersJob;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Apple Music fetching - every 5 minutes
        $schedule->command('app:fetch-apple-music-history')->everyFiveMinutes()->withoutOverlapping();
        
        // Spotify fetching - every 30 minutes
        $schedule->command('app:fetch-spotify-history')->everyThirtyMinutes()->withoutOverlapping();
        
        // Producers fetching - keep at 5 minutes
        $schedule->command('app:fetch-producers')->everyFiveMinutes()->withoutOverlapping();
    }
    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');

    }
}
