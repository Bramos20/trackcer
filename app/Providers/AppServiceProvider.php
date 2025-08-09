<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Apple\AppleExtendSocialite;
use SocialiteProviders\Apple\Provider as AppleProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
   public function boot()
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Inertia::share([
            'auth' => fn () => [
                'user' => Auth::user(),
            ],
            'unreadCount' => fn () => Auth::check()
                ? Auth::user()->unreadNotifications()->count()
                : 0,
        ]);
    }
}
