import React, { useEffect, useState, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { RefreshCw } from 'lucide-react';
import { router } from '@inertiajs/react';

interface DataUpdateNotifierProps {
  checkInterval?: number; // in milliseconds
  enabled?: boolean;
}

export default function DataUpdateNotifier({
  checkInterval = 120000, // Check every 2 minutes (increased from 1 minute)
  enabled = true
}: DataUpdateNotifierProps) {
  const [showNotification, setShowNotification] = useState(false);
  const [isNavigating, setIsNavigating] = useState(false);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    let mounted = true;

    // Listen for Inertia navigation events
    const handleNavigationStart = () => {
      if (mounted) {
        setIsNavigating(true);
        // Cancel any pending requests
        if (abortControllerRef.current) {
          abortControllerRef.current.abort();
        }
      }
    };

    const handleNavigationEnd = () => {
      if (mounted) {
        setIsNavigating(false);
      }
    };

    // Inertia events
    router.on('start', handleNavigationStart);
    router.on('finish', handleNavigationEnd);

    return () => {
      mounted = false;
      // Cancel any pending requests on unmount
      if (abortControllerRef.current) {
        abortControllerRef.current.abort();
      }
    };
  }, []);

  useEffect(() => {
    if (!enabled || isNavigating) return;

    // Store the initial page load time
    const pageLoadTime = new Date();

    const checkForUpdates = async () => {
      // Skip check if navigating
      if (isNavigating) return;

      try {
        // Create new abort controller for this request
        abortControllerRef.current = new AbortController();

        // Make a lightweight request to check if data has been updated
        const response = await fetch('/api/check-data-updates', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          },
          body: JSON.stringify({
            since: pageLoadTime.toISOString()
          }),
          signal: abortControllerRef.current.signal
        });

        if (response.ok) {
          const data = await response.json();
          if (data.hasUpdates && !isNavigating) {
            setShowNotification(true);
          }
        }
      } catch (error) {
        // Ignore abort errors
        if (error.name !== 'AbortError') {
          console.error('Error checking for updates:', error);
        }
      }
    };

    // Check after 2 minutes initially, then at regular intervals
    const initialTimeout = setTimeout(checkForUpdates, 120000);
    const interval = setInterval(checkForUpdates, checkInterval);

    return () => {
      clearTimeout(initialTimeout);
      clearInterval(interval);
      if (abortControllerRef.current) {
        abortControllerRef.current.abort();
      }
    };
  }, [enabled, checkInterval, isNavigating]);

  const handleRefresh = () => {
    setShowNotification(false);
    window.location.reload();
  };

  const handleDismiss = () => {
    setShowNotification(false);
  };

  if (!showNotification) return null;

  return (
    <div className="fixed bottom-4 right-4 z-50 animate-in slide-in-from-bottom-2 duration-300">
      <div className="bg-background border rounded-lg shadow-lg p-4 max-w-sm">
        <div className="flex items-start gap-3">
          <RefreshCw className="h-5 w-5 text-primary mt-0.5" />
          <div className="flex-1">
            <h4 className="font-semibold text-sm">New data available</h4>
            <p className="text-sm text-muted-foreground mt-1">
              Your music library has been updated. Refresh to see the latest tracks and statistics.
            </p>
            <div className="flex gap-2 mt-3">
              <Button
                size="sm"
                onClick={handleRefresh}
                className="bg-primary hover:bg-primary/90"
              >
                Refresh Now
              </Button>
              <Button
                size="sm"
                variant="outline"
                onClick={handleDismiss}
              >
                Dismiss
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}