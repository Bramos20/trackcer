<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return Inertia::render('Settings', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'profile_image' => $user->profile_image,
                'custom_profile_image' => $user->custom_profile_image,
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        $user->update([
            'name' => $request->name,
        ]);

        return redirect()->back()->with('success', 'Profile updated.');
    }

    public function updateProfileImage(Request $request)
    {
        $request->validate([
            'profile_image' => 'required|image|max:2048', // 2MB max
        ]);

        $user = Auth::user();

        // Delete old image if needed
        if ($user->custom_profile_image && $user->profile_image) {
            // Remove the "/profiles/..." path part from the full URL
            $oldPath = ltrim(parse_url($user->profile_image, PHP_URL_PATH), '/');
            Storage::disk('s3')->delete($oldPath);
        }

        // Upload the image with public visibility
        $path = $request->file('profile_image')->storePublicly("profile_images/{$user->id}", 's3');

        // Generate the public URL
        $url = Storage::disk('s3')->url($path);

        // Update user record
        $user->update([
            'profile_image' => $url,
            'custom_profile_image' => true,
        ]);

        return redirect()->back()->with('success', 'Profile image updated.');
    }
}
