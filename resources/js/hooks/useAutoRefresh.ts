import { useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';

interface UseAutoRefreshOptions {
  enabled?: boolean;
  interval?: number; // in milliseconds
  preserveScroll?: boolean;
  onRefresh?: () => void;
}

export function useAutoRefresh(
  url: string,
  options: UseAutoRefreshOptions = {}
) {
  const {
    enabled = true,
    interval = 60000, // Default to 1 minute
    preserveScroll = true,
    onRefresh
  } = options;

  const intervalRef = useRef<NodeJS.Timeout>();

  useEffect(() => {
    if (!enabled) return;

    const refresh = () => {
      // Use router.reload to force fresh data from server
      router.reload({
        preserveScroll,
        preserveState: false, // Always fetch fresh data
        onSuccess: () => {
          onRefresh?.();
        }
      });
    };

    intervalRef.current = setInterval(refresh, interval);

    return () => {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
      }
    };
  }, [enabled, interval, preserveScroll, onRefresh]);

  // Manual refresh function
  const manualRefresh = () => {
    router.reload({
      preserveScroll,
      preserveState: false,
      onSuccess: () => {
        onRefresh?.();
      }
    });
  };

  return { manualRefresh };
}