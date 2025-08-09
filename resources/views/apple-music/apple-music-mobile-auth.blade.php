<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apple Music Authorization</title>
    <script src="https://js-cdn.music.apple.com/musickit/v3/musickit.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        .container {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 90%;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }
        p {
            color: #666;
            margin-bottom: 30px;
        }
        .auth-button {
            background-color: #fc3c44;
            color: white;
            border: none;
            padding: 12px 32px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .auth-button:hover {
            background-color: #e63946;
        }
        .auth-button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .error {
            color: #e63946;
            margin-top: 20px;
        }
        .success {
            color: #42ba96;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Connect Apple Music</h1>
        <p>Authorize TrackCer to access your Apple Music library</p>
        <button id="authButton" class="auth-button" onclick="authorizeAppleMusic()">
            Authorize Apple Music
        </button>
        <div id="status"></div>
    </div>

    <script>
        // Configure MusicKit
        document.addEventListener('musickitloaded', async function() {
            try {
                await MusicKit.configure({
                    developerToken: '{{ config("services.apple.client_secret") }}',
                    app: {
                        name: 'TrackCer',
                        build: '1.0'
                    }
                });
            } catch (err) {
                console.error('Failed to configure MusicKit:', err);
                showError('Failed to initialize Apple Music');
            }
        });

        async function authorizeAppleMusic() {
            const button = document.getElementById('authButton');
            const status = document.getElementById('status');
            
            button.disabled = true;
            button.textContent = 'Authorizing...';
            status.innerHTML = '';

            try {
                const music = MusicKit.getInstance();
                const userToken = await music.authorize();
                
                if (userToken) {
                    status.innerHTML = '<p class="success">Authorization successful!</p>';
                    button.textContent = 'Authorized';
                    
                    // Redirect back to the iOS app with the token
                    setTimeout(() => {
                        window.location.href = `trackcer://apple-music-success?token=${userToken}`;
                    }, 1000);
                } else {
                    throw new Error('No user token received');
                }
            } catch (error) {
                console.error('Authorization error:', error);
                showError('Authorization failed. Please try again.');
                button.disabled = false;
                button.textContent = 'Authorize Apple Music';
            }
        }

        function showError(message) {
            const status = document.getElementById('status');
            status.innerHTML = `<p class="error">${message}</p>`;
        }
    </script>
</body>
</html>