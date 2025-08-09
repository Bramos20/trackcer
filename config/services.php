<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'spotify' => [
    'client_id' => env('SPOTIFY_CLIENT_ID'),
    'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
    'redirect' => env('SPOTIFY_REDIRECT_URI'),

    ],

    'discogs' => [
    'key' => env('DISCOGS_API_KEY'),
    'secret' => env('DISCOGS_API_SECRET'),
    ],

    'apple' => [
            'client_id' => env('APPLE_CLIENT_ID'),
            'client_secret' => env('APPLE_CLIENT_SECRET'),
            'developer_token' => env('APPLE_MUSIC_DEVELOPER_TOKEN'),
            'redirect' => env('APPLE_REDIRECT_URI'),
            'team_id' => env('APPLE_TEAM_ID'),
            'key_id' => env('APPLE_KEY_ID'),
            'private_key_path' => env('APPLE_PRIVATE_KEY_PATH'),
            'key_path' => env('APPLE_MUSIC_KEY_PATH'),
            'api_url' => env('APPLE_MUSIC_API_URL', 'https://api.music.apple.com/v1'),
    ],

    'apple_music' => [
    'api_url' => env('APPLE_MUSIC_API_URL', 'https://api.music.apple.com'),
    ],

    'genius' => [
    'token' => env('GENIUS_TOKEN'),
    'client_id' => env('GENIUS_CLIENT_ID'),
    'client_secret' => env('GENIUS_CLIENT_SECRET'),
],



    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

];
