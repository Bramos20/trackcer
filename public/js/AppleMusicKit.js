// AppleMusicKit.js - Add this to your public/js directory
document.addEventListener('musickitloaded', async function() {
    console.log('MusicKit JS loaded');
    try {
        // MusicKit is already configured through meta tags
        const music = MusicKit.getInstance();

        // Add event listener for the auth button if it exists
        const authButton = document.getElementById('apple-music-auth');
        if (authButton) {
            authButton.addEventListener('click', async function() {
                try {
                    // Show loading state
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connecting...';
                    this.disabled = true;

                    // Request authorization
                    const musicUserToken = await music.authorize();

                    // Send the token to the server
                    const response = await fetch('/callback/apple-music', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            music_user_token: musicUserToken
                        })
                    });

                    if (response.ok) {
                        // Redirect to dashboard or refresh page
                        window.location.href = '/dashboard';
                    } else {
                        throw new Error('Failed to save music token');
                    }
                } catch (error) {
                    console.error('Apple Music authorization failed:', error);

                    // Reset button
                    this.innerHTML = '<i class="fab fa-apple"></i> Connect Apple Music';
                    this.disabled = false;

                    // Show error message
                    const errorElement = document.getElementById('apple-music-error');
                    if (errorElement) {
                        errorElement.textContent = 'Failed to connect Apple Music. Please try again.';
                        errorElement.style.display = 'block';
                    }
                }
            });
        }

        // Check if we should auto-authorize
        const autoAuth = document.getElementById('auto-auth-music');
        if (autoAuth && autoAuth.value === '1') {
            try {
                console.log('Auto-authorizing Apple Music...');
                const musicUserToken = await music.authorize();

                // Send the token to the server
                const response = await fetch('/callback/apple-music', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        music_user_token: musicUserToken
                    })
                });

                if (response.ok) {
                    console.log('Apple Music token saved successfully');
                    // Redirect after successful auth
                    window.location.href = '/dashboard';
                }
            } catch (error) {
                console.error('Auto Apple Music authorization failed:', error);
            }
        }
    } catch (error) {
        console.error('MusicKit configuration error:', error);
    }
});
