<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
Use App\Models\User;
use App\Models\ListeningHistory;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(10)
            ->through(function ($notification) use ($user) {
                // Get the track data from listening history
                $trackData = null;
                $trackUrl = null;
                $source = null;
                
                if (isset($notification->data['track_id']) && isset($notification->data['played_by_user_id'])) {
                    // Look up the track from the user who played it, not the notification recipient
                    $listeningHistory = ListeningHistory::where('user_id', $notification->data['played_by_user_id'])
                        ->where('id', $notification->data['track_id'])
                        ->first();
                    
                    if ($listeningHistory) {
                        $trackData = json_decode($listeningHistory->track_data, true);
                        $source = $listeningHistory->source;
                        
                        // Extract URL based on source
                        if ($source === 'spotify' && isset($trackData['external_urls']['spotify'])) {
                            $trackUrl = $trackData['external_urls']['spotify'];
                        } elseif (strtolower($source) === 'apple music' && isset($trackData['attributes']['url'])) {
                            $trackUrl = $trackData['attributes']['url'];
                        }
                    }
                }
                
                return [
                    'id' => $notification->id,
                    'track_name' => $notification->data['track_name'],
                    'artist_name' => $notification->data['artist_name'],
                    'played_by_user_id' => $notification->data['played_by_user_id'],
                    'played_by_name' => $notification->data['played_by_name'],
                    'track_id' => $notification->data['track_id'],
                    'producer_name' => $notification->data['producer_name'] ?? 'Unknown',
                    'track_url' => $trackUrl,
                    'source' => $source,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at->diffForHumans(),
                ];
            });

        // Mark notifications as read after fetching them
        $user->unreadNotifications->markAsRead();

        return Inertia::render('Notifications', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Delete a single notification
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->first();
        
        if ($notification) {
            $notification->delete();
            return response()->json(['message' => 'Notification deleted successfully']);
        }
        
        return response()->json(['message' => 'Notification not found'], 404);
    }

    /**
     * Delete multiple notifications
     */
    public function bulkDelete(Request $request)
    {
        $user = Auth::user();
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return response()->json(['message' => 'No notifications selected'], 400);
        }
        
        $deleted = $user->notifications()->whereIn('id', $ids)->delete();
        
        return response()->json([
            'message' => $deleted . ' notification(s) deleted successfully',
            'deleted' => $deleted
        ]);
    }

    /**
     * Delete all notifications
     */
    public function deleteAll()
    {
        $user = Auth::user();
        $deleted = $user->notifications()->delete();
        
        return response()->json([
            'message' => $deleted . ' notification(s) deleted successfully',
            'deleted' => $deleted
        ]);
    }
}
