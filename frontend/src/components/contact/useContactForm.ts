import { type ChangeEvent, type FormEvent, useCallback, useState } from 'react';

import { fireContactConversion } from '../../analytics';
import { sendContact } from '../../api';
import { formatPhoneBR } from '../../phone';
import type { CaptchaProviderName, ContactFieldName, ContactValues, SiteConfig } from '../../types';
import { validateContact, validateField } from '../../validation';

const EMPTY_FORM: ContactValues = { nome: '', telefone: '', email: '', assunto: '', mensagem: '', website: '' };

export type FormStatus = { type: 'success' | 'danger'; message: string };

export function useContactForm(config: SiteConfig) {
  const [values, setValues] = useState<ContactValues>(EMPTY_FORM);
  const [errors, setErrors] = useState<Partial<Record<ContactFieldName, string>>>({});
  const [status, setStatus] = useState<FormStatus | null>(null);
  const [sending, setSending] = useState(false);
  const [captchaToken, setCaptchaToken] = useState('');
  const [captchaProvider, setCaptchaProvider] = useState<CaptchaProviderName | ''>('');

  const setCaptcha = useCallback(({ provider, token }: { provider: CaptchaProviderName | ''; token: string }) => {
    setCaptchaProvider(provider);
    setCaptchaToken(token);
  }, []);

  const change = (event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const name = event.target.name as keyof ContactValues;
    const value = name === 'telefone' ? formatPhoneBR(event.target.value) : event.target.value;
    setValues((current) => ({ ...current, [name]: value }));
    if (name !== 'website' && errors[name]) {
      setErrors((current) => ({ ...current, [name]: validateField(name, value, config.fieldLimits[name]) }));
    }
  };

  const blur = (event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const name = event.target.name as ContactFieldName;
    const { value } = event.target;
    setErrors((current) => ({ ...current, [name]: validateField(name, value, config.fieldLimits[name]) }));
  };

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const nextErrors = validateContact(values, config.fieldLimits);
    setErrors(nextErrors);
    const firstInvalid = Object.keys(nextErrors)[0];
    if (firstInvalid) {
      (event.currentTarget.elements.namedItem(firstInvalid) as HTMLElement).focus();
      return;
    }

    setSending(true);
    setStatus(null);
    try {
      const response = await sendContact({ ...values, captchaProvider, captchaToken });
      if (response.conversion) fireContactConversion();
      setStatus({ type: 'success', message: response.message });
      setValues(EMPTY_FORM);
      setCaptchaProvider('');
      setCaptchaToken('');
    } catch (error: unknown) {
      setStatus({ type: 'danger', message: (error as Error).message });
    } finally {
      setSending(false);
    }
  };

  return {
    blur,
    captchaToken,
    change,
    closeStatus: () => setStatus(null),
    errors,
    sending,
    setCaptcha,
    status,
    submit,
    values
  };
}
