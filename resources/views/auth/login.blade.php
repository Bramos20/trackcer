@extends('layouts.app')

@section('head')
<!-- Include CSRF Token meta tag for JavaScript usage -->
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card rounded-lg shadow-sm  overflow-hidden border">
                <div class="card-header bg-white border-bottom p-4">
                    <h2 class="mb-0 fs-4 fw-bold">{{ __('Login') }}</h2>
                </div>
                <div class="card-body p-4">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    <div class="d-grid gap-3">
                        <a href="{{ route('auth.spotify') }}" class="btn btn-lg" style="background-color: #1db954; color: #121212; border-color: #1db954;">
                            <i class="fab fa-spotify"></i> Login with Spotify
                        </a> 
                        <!-- Direct Apple Sign in Button -->
                        <a href="{{ route('login.apple') }}" class="btn btn-lg" style="background-color: #f94c57; color: #121212; border-color: #f94c57;">
                            <i class="fab fa-apple"></i> Sign in with Apple
                        </a>
                    </div>
                </div>
                <div class="card-footer bg-white p-3 border-top">
                    <p class="text-muted small mb-0">
                        Login with your preferred streaming service
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<style>
.card.rounded-lg {
    border-radius: 0.5rem !important;
}
</style>


