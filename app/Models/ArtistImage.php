<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtistImage extends Model
{
    protected $fillable = [
        'artist_name',
        'image_url',
        'genius_artist_id',
    ];
}
