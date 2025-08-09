<?php


namespace App\Services;

use App\Models\Producer;
use Illuminate\Http\Request;

class ProducerService
{
    public function getFilteredProducers(Request $request)
    {
        $query = Producer::query();

        if ($request->filled('genre')) {
            $genreId = $request->input('genre');

            $query->whereHas('tracks.genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', '%' . $search . '%');
        }

        // You can add more filters here (e.g., popularity) later.

        return $query->paginate(15)->withQueryString();
    }
}