<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit');
    }

    public function update(Request $request)
    {
        $user = Auth::user();
    
        $request->validate([
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);
    
        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $filename = 'profile_images/' . uniqid() . '_' . $file->getClientOriginalName();
    
            try {
                // Upload to S3 with public visibility
                Storage::disk('s3')->put($filename, file_get_contents($file), [
                    'visibility' => 'public',
                ]);
    
                // 🔍 Confirm the file exists
                if (!Storage::disk('s3')->exists($filename)) {
                    Log::error('S3 upload reported success, but file not found', [
                        'user_id' => $user->id,
                        'filename' => $filename,
                    ]);
                    return back()->with('error', 'Upload failed: File not found in S3 after upload.');
                }
    
                // ✅ Get the public URL
                $url = Storage::disk('s3')->url($filename);
    
                // Save to DB
                $user->profile_image = $url;
    
            } catch (\Exception $e) {
                Log::error('S3 upload exception for user ' . $user->id, [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
    
                return back()->with('error', 'S3 upload failed: ' . $e->getMessage());
            }
        }
    
        $user->save();
    
        return back()->with('success', 'Profile updated successfully.');
    }
    

    public function resetImageToMusicService()
    {
        $user = Auth::user();

        if ($user->spotify_token) {
            $response = Http::withToken($user->spotify_token)->get('https://api.spotify.com/v1/me');

            if ($response->ok()) {
                $data = $response->json();
                $user->profile_image = $data['images'][0]['url'] ?? $user->profile_image;
                $user->save();

                return back()->with('success', 'Profile image reset to Spotify.');
            }
        }

        // If Apple Music supported profile images in the future, you’d handle it here

        return back()->with('error', 'Could not reset image. Please reconnect your music account.');
    }
}
