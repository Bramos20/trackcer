<?php


namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class TrackPlayedByAnotherUserNotification extends Notification
{
    public $track;
    public $playedBy;

    public function __construct($track, $playedBy)
    {
        $this->track = $track->load('producers');
        $this->playedBy = $playedBy;
    }

    public function via($notifiable)
    {
        return ['database']; // or ['database', 'mail'] if you want email too
    }

    public function toDatabase($notifiable)
    {
        return [
            'track_name' => $this->track->track_name,
            'artist_name' => $this->track->artist_name,
            'played_by_user_id' => $this->playedBy->id,
            'played_by_name' => $this->playedBy->name,
            'track_id' => $this->track->id,
            'producer_name' => $this->track->producers->pluck('name')->join(', '),
        ];
    }
}
