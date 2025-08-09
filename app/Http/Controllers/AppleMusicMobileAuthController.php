<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class AppleMusicMobileAuthController extends Controller
{
    /**
     * Show the Apple Music authentication page for mobile apps
     */
    public function showMobileAuth(Request $request)
    {
        // This page will handle Apple Music authentication for mobile apps
        return view('apple-music-mobile-auth');
    }
    
    /**
     * Handle the authentication success callback
     */
    public function handleSuccess(Request $request)
    {
        return redirect('trackcer://apple-music-success?token=' . $request->input('token'));
    }
}