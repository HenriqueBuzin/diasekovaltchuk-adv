import type { CaptchaProviderConfig, CaptchaProviderName } from '../types';
import { CAPTCHA_DEFINITIONS } from './captcha/captchaDefinitions';
import { useCaptchaChallenge } from './captcha/useCaptchaChallenge';

type CaptchaValue = { provider: CaptchaProviderName | ''; token: string };

interface CaptchaChallengeProps {
  enabled: boolean;
  providers: CaptchaProviderConfig[];
  legacyTurnstileSiteKey?: string;
  onChange: (value: CaptchaValue) => void;
}

export function CaptchaChallenge(props: CaptchaChallengeProps) {
  const { activeProvider, containerRef, enabled } = useCaptchaChallenge(props);
  if (!enabled || !activeProvider) return null;

  return (
    <div className="turnstile-wrap">
      <div ref={containerRef} className={CAPTCHA_DEFINITIONS[activeProvider.name].className} />
    </div>
  );
}
