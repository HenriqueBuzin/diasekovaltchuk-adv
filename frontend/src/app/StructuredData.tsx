import { useEffect } from 'react';

import type { SiteConfig } from '../types';
import { legalServiceSchema } from './seo';

interface StructuredDataProps {
  config: SiteConfig;
}

export function StructuredData({ config }: StructuredDataProps) {
  useEffect(() => {
    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.dataset.siteSchema = 'true';
    script.text = JSON.stringify(legalServiceSchema(config));
    document.head.append(script);
    return () => script.remove();
  }, [config]);

  return null;
}
