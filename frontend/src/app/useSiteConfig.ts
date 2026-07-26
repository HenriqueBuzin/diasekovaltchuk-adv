import { useEffect, useState } from 'react';

import { loadSiteConfig } from '../api';
import type { SiteConfig } from '../types';

export function useSiteConfig() {
  const [config, setConfig] = useState<SiteConfig | null>(null);
  const [error, setError] = useState('');

  useEffect(() => {
    loadSiteConfig()
      .then(setConfig)
      .catch((reason: unknown) => setError((reason as Error).message));
  }, []);

  return { config, error };
}
