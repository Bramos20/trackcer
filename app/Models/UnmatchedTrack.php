<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnmatchedTrack extends Model
{
    use HasFactory;
    protected $fillable = [
        'track_id',
        'track_name',
        'artist_name',
        'album_name',
    ];
}
