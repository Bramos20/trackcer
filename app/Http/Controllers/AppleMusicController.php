<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AppleMusicController extends Controller
{
    /**
     * Show the Apple Music authorization page.
     *
     * @return \Illuminate\View\View
     */
    public function authPage()
    {
        return view('apple-music.auth');
    }

    /**
     * Handle the callback from Apple Music authorization.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function callback(Request $request)
    {
        $request->validate([
            'music_user_token' => 'required|string',
        ]);

        try {
            // Get the current authenticated user
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            // Save the Apple Music token in to the user db table
            $user->apple_music_token = $request->music_user_token;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Apple Music account connected successfully',
                'redirect' => route('dashboard')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect Apple Music: ' . $e->getMessage(),
            ], 500);
        }
    }
}
