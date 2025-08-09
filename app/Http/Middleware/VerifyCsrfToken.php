<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'callback/apple-music', // Add your callback route here
        'https://*.railway.app/*',
        'api/webhooks/cyanite',
        'callback/apple',
        'callback/apple/*',
    ];
}
