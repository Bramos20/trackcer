<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ArtistImage;

class ListeningHistory extends Model
{
    use HasFactory;

     protected $table = 'listening_history';

     // Allow mass assignment for these fields
     protected $fillable = [
         'user_id',
         'track_id',
         'track_name',
         'artist_name',
         'album_name',
         'played_at',
         'track_data',
         'popularity_data',
         'source',
         'fetch_session_id',
         'position_in_fetch'
     ];

     protected $casts = [
        'track_data' => 'array',
        'popularity_data' => 'array',
        'played_at' => 'datetime',
    ];

    protected $appends = ['album_image_url', 'apple_music_id', 'isrc', 'preview_url', 'duration_ms'];


    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'genre_track', 'listening_history_id', 'genre_id');
    }

    public function producers()
    {
        return $this->belongsToMany(Producer::class, 'producer_track', 'listening_history_id', 'producer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function artistImage()
    {
        return $this->hasOne(ArtistImage::class, 'artist_name', 'artist_name');
    }

    /**
     * Get the album image URL from track_data
     */
    public function getAlbumImageUrlAttribute()
    {
        $trackData = $this->track_data;
        if (!$trackData) {
            return null;
        }

        // Check for Spotify format
        if (isset($trackData['album']['images'][0]['url'])) {
            return $trackData['album']['images'][0]['url'];
        }

        // Check for Apple Music format
        if (isset($trackData['attributes']['artwork']['url'])) {
            return str_replace('{w}x{h}', '300x300', $trackData['attributes']['artwork']['url']);
        }

        // Check for nested data array (Apple Music)
        if (isset($trackData['data'][0]['attributes']['artwork']['url'])) {
            return str_replace('{w}x{h}', '300x300', $trackData['data'][0]['attributes']['artwork']['url']);
        }

        return null;
    }

    /**
     * Get the Apple Music ID from track_data
     */
    public function getAppleMusicIdAttribute()
    {
        $trackData = $this->track_data;
        if (!$trackData) {
            return null;
        }

        // Direct ID
        if (isset($trackData['id'])) {
            return $trackData['id'];
        }

        // Nested in data array
        if (isset($trackData['data'][0]['id'])) {
            return $trackData['data'][0]['id'];
        }

        return null;
    }

    /**
     * Get the ISRC from track_data
     */
    public function getIsrcAttribute()
    {
        $trackData = $this->track_data;
        if (!$trackData) {
            return null;
        }

        // Spotify format
        if (isset($trackData['external_ids']['isrc'])) {
            return $trackData['external_ids']['isrc'];
        }

        // Apple Music format
        if (isset($trackData['attributes']['isrc'])) {
            return $trackData['attributes']['isrc'];
        }

        // Nested in data array
        if (isset($trackData['data'][0]['attributes']['isrc'])) {
            return $trackData['data'][0]['attributes']['isrc'];
        }

        return null;
    }

    /**
     * Get the preview URL from track_data
     */
    public function getPreviewUrlAttribute()
    {
        $trackData = $this->track_data;
        if (!$trackData) {
            return null;
        }

        // Spotify format
        if (isset($trackData['preview_url'])) {
            return $trackData['preview_url'];
        }

        // Apple Music format
        if (isset($trackData['attributes']['previews'][0]['url'])) {
            return $trackData['attributes']['previews'][0]['url'];
        }

        // Nested in data array
        if (isset($trackData['data'][0]['attributes']['previews'][0]['url'])) {
            return $trackData['data'][0]['attributes']['previews'][0]['url'];
        }

        return null;
    }

    /**
     * Get the duration in milliseconds from track_data
     */
    public function getDurationMsAttribute()
    {
        $trackData = $this->track_data;
        if (!$trackData) {
            return 0;
        }

        // Spotify format
        if (isset($trackData['duration_ms'])) {
            return (int) $trackData['duration_ms'];
        }

        // Apple Music format
        if (isset($trackData['attributes']['durationInMillis'])) {
            return (int) $trackData['attributes']['durationInMillis'];
        }

        // Nested in data array (Apple Music)
        if (isset($trackData['data'][0]['attributes']['durationInMillis'])) {
            return (int) $trackData['data'][0]['attributes']['durationInMillis'];
        }

        return 0;
    }

}
