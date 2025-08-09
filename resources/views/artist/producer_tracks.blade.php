@extends('layouts.app')

@section('content')
<div class="container max-w-3xl mx-auto py-6">
    <h2 class="text-xl font-semibold mb-4">Tracks by {{ $producer->name }} for {{ $artistName }}</h2>

    @if ($tracks->isEmpty())
        <p class="text-gray-600">No tracks found.</p>
    @else
        <ul class="space-y-3">
            @foreach ($tracks as $track)
                <li class="bg-gray-100 rounded p-3 flex justify-between items-center">
                    <div>
                        <p class="font-medium">{{ $track->track_name }}</p>
                        <p class="text-sm text-gray-600">Played on {{ \Carbon\Carbon::parse($track->played_at)->toDayDateTimeString() }}</p>
                    </div>
                    @if (!empty($track->spotify_url))
                        <a href="{{ $track->spotify_url }}" target="_blank"
                           class="text-green-600 hover:underline text-sm">
                            ▶ Play on Spotify
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
    <div class="mt-6">
        <a href="{{ route('artist.collaborations', ['artistName' => $artistName]) }}"
           class="text-blue-600 hover:underline">
           ← Back to Producers
        </a>
    </div>
</div>
@endsection