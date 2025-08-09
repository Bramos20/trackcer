<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Authenticated;
use App\Jobs\FetchListeningHistoryJob;
use App\Jobs\FetchProducersJob;

class FetchUserDataFirstLogin
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  Authenticated  $event
     * @return void
     */
    public function handle(Authenticated $event)
    {
        $user = $event->user;

        // checking if user is new who hasn't had their initial data fetched yet that way the user doesn't have to muanually do it
        if (!$user->initial_data_fetched) {
            // Dispatch jobs to fetch user data
            FetchListeningHistoryJob::dispatch($user);
            FetchProducersJob::dispatch($user);

            // Mark that we've fetched initial data
            $user->initial_data_fetched = true;
            $user->save();
        }
    }
}
