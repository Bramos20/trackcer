@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Profile</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @elseif(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="profile_image" class="form-label">Upload Profile Image</label>
            <input type="file" class="form-control" name="profile_image">
        </div>

        <button type="submit" class="btn btn-primary">Save</button>
    </form>

    <form method="POST" action="{{ route('profile.reset.image') }}" class="mt-3">
        @csrf
        <button type="submit" class="btn btn-secondary">Reset to Spotify Image</button>
    </form>
</div>
@endsection
