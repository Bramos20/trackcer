@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>My Producer Playlists</h1>
    <div class="row">
        @forelse ($playlists as $playlist)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <img src="{{ $playlist->image_url }}" class="card-img-top" alt="{{ $playlist->name }}">
                    <div class="card-body">
                        <h5 class="card-title">{{ $playlist->name }}</h5>
                        <p class="card-text">
                            @if($playlist->spotify_id)
                            <strong>Tracks:</strong> {{ $playlist->track_count }}
                            @endif
                        </p>
                        <a href="{{ $playlist->external_url }}" target="_blank" class="btn" style="background-color: #0e7490;color: white;">
                            View on {{ $playlist->service === 'apple_music' ? 'Apple Music' : 'Spotify' }}
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">
                    You haven't created any playlists yet.
                </div>
            </div>
        @endforelse
    </div>
</div>

<style>

.card {
    border-radius: 0.75rem;
    border-color: rgba(229, 231, 235, 1);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    background-color: transparent;
    border-bottom: 1px solid rgba(229, 231, 235, 1);
    padding: 1.25rem;
}

.card-footer {
    background-color: transparent;
    border-top: 1px solid rgba(229, 231, 235, 1);
    padding: 1rem 1.25rem;
}

/* Consistent spacing */
.mb-3 {
    margin-bottom: 1rem !important;
}

/* Button styling */
.btn {
    border-radius: 0.5rem;
    font-weight: 500;
    padding: 0.5rem 1rem;
    transition: all 0.2s ease;
}

.btn-primary {
    background-color: rgba(99, 102, 241, 1);
    border-color: rgba(99, 102, 241, 1);
}

.btn-primary:hover {
    background-color: rgba(79, 82, 221, 1);
    border-color: rgba(79, 82, 221, 1);
}

.btn-success {
    background-color: rgba(34, 197, 94, 1);
    border-color: rgba(34, 197, 94, 1);
}

.btn-success:hover {
    background-color: rgba(22, 163, 74, 1);
    border-color: rgba(22, 163, 74, 1);
}

.btn-info {
    background-color: rgba(14, 165, 233, 1);
    border-color: rgba(14, 165, 233, 1);
    color: white;
}

.btn-info:hover {
    background-color: rgba(2, 132, 199, 1);
    border-color: rgba(2, 132, 199, 1);
    color: white;
}

/* Form controls */
.form-select, .form-control {
    border-radius: 0.5rem;
    border-color: rgba(209, 213, 219, 1);
    padding: 0.5rem 0.75rem;
}

.form-select:focus, .form-control:focus {
    border-color: rgba(99, 102, 241, 0.5);
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.25);
}

/* Consistent alert styling */
.alert {
    border-radius: 0.5rem;
    border: 1px solid transparent;
    padding: 1rem;
}

.alert-success {
    background-color: rgba(240, 253, 244, 1);
    border-color: rgba(187, 247, 208, 1);
    color: rgba(22, 101, 52, 1);
}

.alert-danger {
    background-color: rgba(254, 242, 242, 1);
    border-color: rgba(254, 202, 202, 1);
    color: rgba(153, 27, 27, 1);
}

/* Make buttons consistent width */
.btn-primary, .btn-success {
    min-width: 110px;
}




</style>

@endsection
