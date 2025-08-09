import React, { useState, useEffect } from "react";
import { Head, router } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from "@/components/ui/card";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { useTheme } from "next-themes";

declare global {
  interface Window {
    MusicKit: any;
  }
}

interface Props {
  error?: string;
}

export default function AppleMusicAuth({ error: serverError }: Props) {
  const { theme } = useTheme();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(serverError || null);

  const developerToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiIsImtpZCI6IkM5R1A0QVlHNzIifQ.eyJpc3MiOiJDM0M5OTU4MlhaIiwiaWF0IjoxNzUxMDIxNDk5LCJleHAiOjE3NjY1NzM0OTl9.plU-0y4LbEJXdZgcdSIwjrQ359lXrwmyNDh7fFB92A2fEI2noQOkn4jdI3890V6418nkUtTnt2wtSfBUv5N9ng';

  const loadMusicKitJS = () => {
    if (typeof window.MusicKit !== 'undefined') {
      initializeMusicKit();
      return;
    }

    const script = document.createElement('script');
    script.src = 'https://js-cdn.music.apple.com/musickit/v3/musickit.js';
    script.onload = () => initializeMusicKit();
    script.onerror = () => {
      console.error('Failed to load MusicKit script');
      setError('Failed to load Apple Music. Please try again later.');
      setLoading(false);
    };
    document.head.appendChild(script);
  };

  const initializeMusicKit = async () => {
    try {
      console.log('Initializing MusicKit...');

      await window.MusicKit.configure({
        developerToken: developerToken,
        app: {
          name: 'TrackCer',
          build: '1.0.0'
        },
        declarativeMarkup: true,
      });

      console.log('MusicKit configured, requesting authorization...');

      const music = window.MusicKit.getInstance();

      music.addEventListener('authorizationStatusDidChange', (event: any) => {
        console.log('Authorization status changed:', event.authorizationStatus);
      });

      try {
        const musicUserToken = await music.authorize();

        if (musicUserToken) {
          console.log('Authorization successful!');
          submitTokenToServer(musicUserToken, music.storefrontId);
        } else {
          setError('Authorization was declined or failed.');
          setLoading(false);
        }
      } catch (authError: any) {
        console.error('MusicKit authorization error:', authError);
        
        if (authError.name === 'AUTHORIZATION_ERROR' || authError.message?.includes('mk-007')) {
          setError('Apple Music authorization failed. Please ensure you have an active Apple Music subscription.');
        } else {
          setError(`Failed to authorize with Apple Music: ${authError.message || 'Unknown error'}`);
        }
        setLoading(false);
      }
    } catch (error: any) {
      console.error('MusicKit initialization failed:', error);
      setError(`Failed to connect to Apple Music: ${error.message || 'Unknown error'}`);
      setLoading(false);
    }
  };

  const submitTokenToServer = (musicUserToken: string, storefrontId?: string) => {
    router.post('/apple-music/connect', {
      music_user_token: musicUserToken,
      storefront_id: storefrontId,
    });
  };

  const handleConnect = () => {
    setLoading(true);
    setError(null);
    loadMusicKitJS();
  };

  return (
    <>
      <Head title="Connect Apple Music" />
      
      <div className="min-h-screen bg-background">
        <div className="container mx-auto px-4 py-16">
          <div className="max-w-2xl mx-auto">
            <Card className="border-border">
              <CardHeader className="space-y-1 pb-6">
                <CardTitle className="text-2xl font-bold">Connect Apple Music</CardTitle>
              </CardHeader>
              
              <CardContent className="text-center space-y-6">
                <div className="space-y-3">
                  <h3 className="text-xl font-semibold">One more step!</h3>
                  <p className="text-muted-foreground">
                    You've successfully signed in with your Apple ID.
                  </p>
                  <p className="text-muted-foreground">
                    To access your music data, please connect your Apple Music account.
                  </p>
                </div>

                {error && (
                  <Alert variant="destructive">
                    <AlertDescription>{error}</AlertDescription>
                  </Alert>
                )}

                <Button
                  onClick={handleConnect}
                  disabled={loading}
                  size="lg"
                  className="bg-[#FF5842] hover:bg-[#FF4732] dark:bg-[#4033FB] dark:hover:bg-[#3529E0] text-white"
                >
                  {loading ? (
                    <>
                      <i className="fas fa-spinner fa-spin mr-2" />
                      Connecting...
                    </>
                  ) : (
                    <>
                      <i className="fab fa-apple mr-2" />
                      Connect Apple Music
                    </>
                  )}
                </Button>
              </CardContent>

              <CardFooter className="justify-center pt-6 pb-4">
                <p className="text-sm text-muted-foreground">
                  Connect to unlock your personalized data
                </p>
              </CardFooter>
            </Card>
          </div>
        </div>
      </div>
    </>
  );
}