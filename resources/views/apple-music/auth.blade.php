@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm rounded-lg overflow-hidden border">
                <div class="card-header bg-white border-bottom p-4">
                    <h2 class="mb-0 fs-4 fw-bold">Connect Apple Music</h2>
                </div>

                <div class="card-body p-4 text-center">
                    <h5 class="mb-3">One more step!</h5>

                    <p>You've successfully signed in with your Apple ID.</p>
                    <p>To access your music data, please connect your Apple Music account.</p>

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div id="apple-music-error" class="alert alert-danger mt-3" style="display: none;"></div>

                    <button id="apple-music-auth" class="btn btn-lg mt-4" style="background-color: #f94c57; color: #121212; border-color: #f94c57;">
                          <i class="fab fa-apple"></i> Connect Apple Music
                    </button>


                </div>

                <div class="card-footer bg-white p-3 border-top">
                    <p class="text-muted small mb-0">
                        Connect to unlock your personalized data
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get reference to the Apple Music connect button
    const appleAuthButton = document.getElementById('apple-music-auth');
    const errorContainer = document.getElementById('apple-music-error');

    if (!appleAuthButton) return;

    // Handle button click
    appleAuthButton.addEventListener('click', function(event) {
        event.preventDefault();

        // Show loading state
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connecting...';
        this.disabled = true;
        errorContainer.style.display = 'none';

        // Load the MusicKit JS if not already loaded
        if (typeof MusicKit === 'undefined') {
            loadMusicKitJS();
        } else {
            initializeMusicKit();
        }
    });

    // Load the MusicKit SDK
    function loadMusicKitJS() {
        const script = document.createElement('script');
        script.src = 'https://js-cdn.music.apple.com/musickit/v3/musickit.js';
        script.onload = initializeMusicKit;
        script.onerror = function(error) {
            console.error('Failed to load MusicKit script:', error);
            showError('Failed to load Apple Music. Please try again later.');
        };
        document.head.appendChild(script);
    }

    // Initialize MusicKit with developer token
    // Initialize MusicKit with developer token
    async function initializeMusicKit() {
        try {
            console.log('Initializing MusicKit...');

            // Your developer token - ensure this is valid and not expired
            const developerToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiIsImtpZCI6IkM5R1A0QVlHNzIifQ.eyJpc3MiOiJDM0M5OTU4MlhaIiwiaWF0IjoxNzUxMDIxNDk5LCJleHAiOjE3NjY1NzM0OTl9.plU-0y4LbEJXdZgcdSIwjrQ359lXrwmyNDh7fFB92A2fEI2noQOkn4jdI3890V6418nkUtTnt2wtSfBUv5N9ng';

            // Configure MusicKit with more detailed options
            await MusicKit.configure({
                developerToken: developerToken,
                app: {
                    name: 'Trackcer',
                    build: '1.0.0'
                },
                // Add these explicitly to help with authorization
                declarativeMarkup: true,
                developerToken: developerToken
            });

            console.log('MusicKit configured, requesting authorization...');

            // Get MusicKit instance
            const music = MusicKit.getInstance();

            // Add detailed error logging
            music.addEventListener('authorizationStatusDidChange', (event) => {
                console.log('Authorization status changed:', event.authorizationStatus);
                if (event.authorizationStatus === 0) {
                    console.log('User is not authorized');
                }
            });

            music.addEventListener('storefrontCountryCodeDidChange', (event) => {
                console.log('Storefront country changed:', event.storefrontCountryCode);
            });

            // Request user authorization with error handling
            try {
                const musicUserToken = await music.authorize();

                // If authorization successful
                if (musicUserToken) {
                    console.log('Authorization successful! Token:', musicUserToken.substring(0, 10) + '...');

                    // Send the token to the server via form submission
                    submitTokenToServer(musicUserToken, music.storefrontId);
                } else {
                    // Reset button if authorization was canceled
                    resetButton();
                    showError('Authorization was declined or failed.');
                }
            } catch (authError) {
                console.error('MusicKit authorization error:', authError);

                // Check for specific error codes
                if (authError.name === 'AUTHORIZATION_ERROR' || authError.message.includes('mk-007')) {
                    showError('Apple Music authorization failed. Please ensure you have an active Apple Music subscription.');
                } else {
                    showError('Failed to authorize with Apple Music: ' + authError.message);
                }
            }
        } catch (error) {
            console.error('MusicKit initialization failed:', error);
            showError('Failed to connect to Apple Music: ' + error.message);
        }
    }

    // Function to submit token to the server via form
    function submitTokenToServer(musicUserToken, storefrontId) {
            // Create a form for submission
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("apple-music.connect") }}';

            // Add CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);

            // Add music token
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'music_user_token';
            tokenInput.value = musicUserToken;
            form.appendChild(tokenInput);

            // Add storefront ID if available
            if (storefrontId) {
                const storefrontInput = document.createElement('input');
                storefrontInput.type = 'hidden';
                storefrontInput.name = 'storefront_id';
                storefrontInput.value = storefrontId;
                form.appendChild(storefrontInput);
            }

            // Submit the form
            document.body.appendChild(form);
            form.submit();
        }

        // Function to show error
        function showError(message) {
            errorContainer.textContent = message;
            errorContainer.style.display = 'block';
            resetButton();
        }

        // Function to reset button
        function resetButton() {
            appleAuthButton.innerHTML = '<i class="fab fa-apple"></i> Connect Apple Music';
            appleAuthButton.disabled = false;
        }
    });
   </script>
    @endpush

<style>
.card.rounded-lg {
    border-radius: 0.5rem !important;
}
</style>

