@extends('layouts.app')

@section('content')
<div class="container max-w-4xl mx-auto py-6">
    <h1 class="text-2xl font-bold mb-4">Producers Who Worked with {{ $artistName }}</h1>

    @if ($artistImage)
        <div class="mb-6">
            <img src="{{ $artistImage }}" alt="{{ $artistName }}" class="rounded-full w-40 h-40 object-cover">
        </div>
    @endif

    <div>
        @if ($producers->isEmpty())
            <p class="text-gray-600">No producers found for this artist in your listening history.</p>
        @else
            <ul class="list-disc pl-6 space-y-3">
                @foreach ($producers as $producer)
                    <li class="mb-2 font-semibold flex justify-between items-center">
                        <span>{{ $producer->name }}</span>
                        <a href="{{ route('artist.producer.tracks', ['artistName' => rawurlencode($artistName), 'producer' => $producer->id]) }}"
                           class="text-blue-600 hover:underline text-sm">
                            View Tracks
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
