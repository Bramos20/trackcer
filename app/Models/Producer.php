<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producer extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'spotify_id','discogs_id', 'image_url'];


    public function tracks()
    {
        return $this->belongsToMany(ListeningHistory::class, 'producer_track', 'producer_id', 'listening_history_id');
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows')->withTimestamps();
    }

    public function favouritedBy()
    {
        return $this->belongsToMany(User::class, 'favourites')->withTimestamps();
    }

}
