import { useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';

export function useFeaturePolling(hasPending: boolean) {
  const attemptsRef = useRef(0);

  useEffect(() => {
    if (!hasPending) {
      attemptsRef.current = 0;
      return;
    }
    const interval = window.setInterval(() => {
      if (attemptsRef.current >= 6) {
        window.clearInterval(interval);
        return;
      }
      attemptsRef.current += 1;
      router.reload({ only: ['versus'], preserveScroll: true });
    }, 10_000);

    return () => window.clearInterval(interval);
  }, [hasPending]);
}
