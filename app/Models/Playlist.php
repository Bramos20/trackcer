<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'spotify_id', 
        'apple_music_id', 
        'apple_music_global_id', 
        'service', 
        'name', 
        'description'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
