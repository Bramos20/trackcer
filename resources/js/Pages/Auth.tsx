import { useEffect, useState } from 'react';
import { router, usePage } from '@inertiajs/react';

export default function Auth() {
  const { props } = usePage();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [developerToken] = useState(
    'eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiIsImtpZCI6IldIMzVNSlM3MjUifQ.eyJpc3MiOiJDM0M5OTU4MlhaIiwiaWF0IjoxNzQyNzM3OTQ2LCJleHAiOjE3NTgyODk5NDZ9.TOtbXRqIKqHxE9ChQZu3Mdj76ZpHahTyWfjPqUnhJfeifuMjmzxPBJtA9cYY8fKrSglXHOXmvCuI5-OpCL9koA'
  );

  useEffect(() => {
    if (!window.MusicKit) {
      const script = document.createElement('script');
      script.src = 'https://js-cdn.music.apple.com/musickit/v3/musickit.js';
      script.onload = () => console.log('MusicKit loaded');
      script.onerror = () =>
        setError('Failed to load Apple Music. Please try again later.');
      document.head.appendChild(script);
    }
  }, []);

  const connectAppleMusic = async () => {
    setLoading(true);
    setError('');

    try {
      if (!window.MusicKit) {
        throw new Error('MusicKit not loaded.');
      }

      await window.MusicKit.configure({
        developerToken: developerToken,
        app: {
          name: 'Trackcer',
          build: '1.0.0',
        },
      });

      const music = window.MusicKit.getInstance();

      music.addEventListener('authorizationStatusDidChange', (event) => {
        console.log('Authorization status changed:', event.authorizationStatus);
      });

      const musicUserToken = await music.authorize();

      if (musicUserToken) {
        const storefrontId = music.storefrontId;

        router.post(
          route('apple-music.connect'),
          {
            music_user_token: musicUserToken,
            storefront_id: storefrontId,
          },
          {
            onError: (errors) => {
              setError(errors?.message || 'Authorization failed.');
              setLoading(false);
            },
          }
        );
      } else {
        throw new Error('Authorization was declined or failed.');
      }
    } catch (err) {
      console.error('MusicKit error:', err);
      setError(
        err.message.includes('mk-007')
          ? 'Apple Music authorization failed. Please ensure you have an active subscription.'
          : err.message
      );
      setLoading(false);
    }
  };

  return (
    <div className="container mx-auto max-w-xl mt-10">
      <div className="shadow-sm rounded-lg border overflow-hidden">
        <div className="bg-white border-b p-4">
          <h2 className="text-xl font-bold mb-0">Connect Apple Music</h2>
        </div>

        <div className="p-6 text-center">
          <h5 className="text-lg mb-3">One more step!</h5>
          <p className="mb-1">You've successfully signed in with your Apple ID.</p>
          <p className="mb-4">To access your music data, please connect your Apple Music account.</p>

          {props.flash?.error && (
            <div className="alert alert-danger mb-3">{props.flash.error}</div>
          )}

          {error && (
            <div className="alert alert-danger mb-3">{error}</div>
          )}

          <button
            onClick={connectAppleMusic}
            disabled={loading}
            className="btn btn-lg mt-4"
            style={{
              backgroundColor: '#f94c57',
              color: '#121212',
              borderColor: '#f94c57',
            }}
          >
            {loading ? (
              <span>
                <i className="fas fa-spinner fa-spin mr-2"></i> Connecting...
              </span>
            ) : (
              <span>
                <i className="fab fa-apple mr-2"></i> Connect Apple Music
              </span>
            )}
          </button>
        </div>

        <div className="bg-white border-t p-3 text-muted text-sm">
          Connect to unlock your personalized data
        </div>
      </div>
    </div>
  );
}