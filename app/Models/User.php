<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'spotify_id',
        'spotify_token',
        'spotify_refresh_token',
        'apple_id',
        'apple_token',
        'apple_refresh_token',
        'apple_music_token',
        'profile_image',
        'custom_profile_image',
        'terms_accepted_at',
        'initial_data_fetched',
        'last_login_at',
    ];

    public function followedProducers()
    {
        return $this->belongsToMany(Producer::class, 'follows')->withTimestamps();
    }

    public function favouriteProducers()
    {
        return $this->belongsToMany(Producer::class, 'favourites')->withTimestamps();
    }

    public function listeningHistory()
    {
        return $this->hasMany(ListeningHistory::class);
    }
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'apple_music_storefront',
        'spotify_token',
        'spotify_refresh_token',
        'apple_token',
        'apple_refresh_token',
    ];
    

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'last_login_at' => 'datetime',
        'initial_data_fetched' => 'boolean',
    ];
    
    /**
     * Get the user's Apple ID attribute for iOS compatibility
     */
    public function getAppleIdAttribute()
    {
        return array_key_exists('apple_id', $this->attributes) ? $this->attributes['apple_id'] : null;
    }
    
    /**
     * Get the user's Apple Music token attribute for iOS compatibility
     */
    public function getAppleMusicTokenAttribute()
    {
        return array_key_exists('apple_music_token', $this->attributes) ? $this->attributes['apple_music_token'] : null;
    }
    
    
    /**
     * Get the user's terms accepted at attribute for iOS compatibility
     */
    public function getTermsAcceptedAtAttribute()
    {
        return array_key_exists('terms_accepted_at', $this->attributes) ? $this->attributes['terms_accepted_at'] : null;
    }
    
    /**
     * Get the user's initial data fetched attribute for iOS compatibility
     */
    public function getInitialDataFetchedAttribute()
    {
        if (!array_key_exists('initial_data_fetched', $this->attributes)) {
            return false;
        }
        
        $value = $this->attributes['initial_data_fetched'];
        
        // Handle different types - convert to boolean
        if (is_bool($value)) {
            return $value;
        }
        
        // Convert numeric values to boolean (0 = false, 1 = true)
        if (is_numeric($value)) {
            return (bool) $value;
        }
        
        return false;
    }
    
    /**
     * Get the user's last login at attribute for iOS compatibility
     */
    public function getLastLoginAtAttribute()
    {
        return array_key_exists('last_login_at', $this->attributes) ? $this->attributes['last_login_at'] : null;
    }
    
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['appleId', 'appleMusicToken', 'termsAcceptedAt', 'initialDataFetched', 'lastLoginAt'];
    
    /**
     * Convert the model instance to an array.
     * Override to handle both web and API contexts
     *
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();
        
        // For API requests, ensure camelCase attributes are included
        if (request()->is('api/*')) {
            // The appends already handle this
        }
        
        // Ensure profile_image and custom_profile_image are included
        $array['profile_image'] = $this->profile_image;
        $array['custom_profile_image'] = $this->custom_profile_image;
        
        return $array;
    }
}
