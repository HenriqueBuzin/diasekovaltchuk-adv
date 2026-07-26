import type { CaptchaProviderConfig, CaptchaProviderName } from '../../types';

export interface CaptchaDefinition {
  apiName: 'turnstile' | 'grecaptcha' | 'hcaptcha';
  className: string;
  scriptId: string;
  scriptSrc: string;
  widgetOptions: (provider: CaptchaProviderConfig, solved: (token: string) => void, cleared: () => void) => object;
}

export const CAPTCHA_DEFINITIONS: Record<CaptchaProviderName, CaptchaDefinition> = {
  turnstile: {
    apiName: 'turnstile',
    className: 'cf-turnstile',
    scriptId: 'cloudflare-turnstile-script',
    scriptSrc: 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=__captchaLoaded_turnstile',
    widgetOptions: (provider, solved, cleared) => ({
      sitekey: provider.siteKey,
      theme: 'auto',
      callback: solved,
      'expired-callback': cleared,
      'error-callback': cleared
    })
  },
  recaptcha: {
    apiName: 'grecaptcha',
    className: 'g-recaptcha',
    scriptId: 'google-recaptcha-script',
    scriptSrc: 'https://www.google.com/recaptcha/api.js?render=explicit&onload=__captchaLoaded_recaptcha',
    widgetOptions: (provider, solved, cleared) => ({
      sitekey: provider.siteKey,
      theme: 'dark',
      callback: solved,
      'expired-callback': cleared,
      'error-callback': cleared
    })
  },
  hcaptcha: {
    apiName: 'hcaptcha',
    className: 'h-captcha',
    scriptId: 'hcaptcha-script',
    scriptSrc: 'https://js.hcaptcha.com/1/api.js?render=explicit&onload=__captchaLoaded_hcaptcha',
    widgetOptions: (provider, solved, cleared) => ({
      sitekey: provider.siteKey,
      theme: 'dark',
      callback: solved,
      'expired-callback': cleared,
      'error-callback': cleared
    })
  }
};
