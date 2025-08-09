<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;
use App\Services\AppleSocialiteProvider;

class AppleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Socialite::extend('apple', function ($app) {
            $config = $app['config']['services.apple'];

            return Socialite::buildProvider(AppleSocialiteProvider::class, $config);
        });
    }
}
