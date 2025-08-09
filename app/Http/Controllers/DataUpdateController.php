<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ListeningHistory;
use App\Models\Producer;
use Carbon\Carbon;

class DataUpdateController extends Controller
{
    /**
     * Check if there have been data updates since a given timestamp
     */
    public function checkUpdates(Request $request)
    {
        $request->validate([
            'since' => 'required|date'
        ]);

        $user = Auth::user();
        $since = Carbon::parse($request->since);

        // Check if there are new listening history entries
        $hasNewTracks = ListeningHistory::where('user_id', $user->id)
            ->where('created_at', '>', $since)
            ->exists();

        // Check if there are updated listening history entries
        $hasUpdatedTracks = ListeningHistory::where('user_id', $user->id)
            ->where('updated_at', '>', $since)
            ->where('created_at', '<=', $since)
            ->exists();

        // Check if there are new or updated producers
        $hasProducerUpdates = Producer::whereHas('tracks', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where(function ($query) use ($since) {
                $query->where('created_at', '>', $since)
                      ->orWhere('updated_at', '>', $since);
            })
            ->exists();

        $hasUpdates = $hasNewTracks || $hasUpdatedTracks || $hasProducerUpdates;

        return response()->json([
            'hasUpdates' => $hasUpdates,
            'lastChecked' => now()->toISOString(),
            'details' => [
                'newTracks' => $hasNewTracks,
                'updatedTracks' => $hasUpdatedTracks,
                'producerUpdates' => $hasProducerUpdates
            ]
        ]);
    }
}