<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProducersLoaded
{
    use Dispatchable, SerializesModels;

    public $user;
    public $producers;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\User  $user
     * @param  \Illuminate\Database\Eloquent\Collection  $producers
     * @return void
     */
    public function __construct($user, $producers)
    {
        $this->user = $user;
        $this->producers = $producers;
    }
}
