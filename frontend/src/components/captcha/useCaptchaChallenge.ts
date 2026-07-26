import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

import { activeCaptchaProviders } from '../../captchaProviders';
import type { CaptchaProviderConfig, CaptchaProviderName } from '../../types';
import { CAPTCHA_DEFINITIONS } from './captchaDefinitions';

const SCRIPT_TIMEOUT_MS = 6000;

type CaptchaValue = { provider: CaptchaProviderName | ''; token: string };

interface UseCaptchaChallengeOptions {
  enabled: boolean;
  providers: CaptchaProviderConfig[];
  legacyTurnstileSiteKey?: string;
  onChange: (value: CaptchaValue) => void;
}

export function useCaptchaChallenge({
  enabled,
  providers,
  legacyTurnstileSiteKey = '',
  onChange
}: UseCaptchaChallengeOptions) {
  const containerRef = useRef<HTMLDivElement>(null);
  const availableProviders = useMemo(
    () => activeCaptchaProviders(enabled, providers, legacyTurnstileSiteKey),
    [enabled, providers, legacyTurnstileSiteKey]
  );
  const providersKey = useMemo(
    () => availableProviders.map((provider) => `${provider.name}:${provider.siteKey}`).join('|'),
    [availableProviders]
  );
  const [activeSelection, setActiveSelection] = useState({ key: '', index: 0 });
  const activeIndex = activeSelection.key === providersKey ? activeSelection.index : 0;
  const activeProvider = availableProviders[activeIndex];

  const clearToken = useCallback(() => onChange({ provider: '', token: '' }), [onChange]);

  useEffect(() => {
    clearToken();
  }, [providersKey, clearToken]);

  useEffect(() => {
    const provider = activeProvider;
    const container = containerRef.current;
    if (!provider || !container) return undefined;

    const definition = CAPTCHA_DEFINITIONS[provider.name];
    let widgetId: string | number | undefined;
    let loaded = false;
    let cancelled = false;
    container.innerHTML = '';

    const failover = () => {
      if (cancelled) return;
      clearToken();
      setActiveSelection((current) => {
        const currentIndex = current.key === providersKey ? current.index : activeIndex;
        return {
          key: providersKey,
          index: currentIndex + 1 < availableProviders.length ? currentIndex + 1 : currentIndex
        };
      });
    };

    const solved = (token: string) => onChange({ provider: provider.name, token });
    const renderWidget = () => {
      if (cancelled || loaded) return;
      const api = window[definition.apiName];
      if (!api?.render || !containerRef.current) {
        failover();
        return;
      }
      loaded = true;
      widgetId = api.render(
        containerRef.current,
        definition.widgetOptions(provider, solved, clearToken) as Record<string, unknown>
      );
    };

    window[`__captchaLoaded_${provider.name}`] = renderWidget;
    const timeoutId = window.setTimeout(failover, SCRIPT_TIMEOUT_MS);
    const existingScript = document.getElementById(definition.scriptId) as HTMLScriptElement | null;

    if (window[definition.apiName]?.render) {
      renderWidget();
    } else if (!existingScript) {
      const script = document.createElement('script');
      script.id = definition.scriptId;
      script.src = definition.scriptSrc;
      script.async = true;
      script.defer = true;
      script.onerror = failover;
      document.head.append(script);
    }

    return () => {
      cancelled = true;
      window.clearTimeout(timeoutId);
      window[definition.apiName]?.remove?.(widgetId);
      delete window[`__captchaLoaded_${provider.name}`];
    };
  }, [activeIndex, activeProvider, availableProviders.length, clearToken, onChange, providersKey]);

  return { activeProvider, containerRef, enabled };
}
