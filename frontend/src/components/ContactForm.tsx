import type { SiteConfig } from '../types';
import { CaptchaChallenge } from './CaptchaChallenge';
import { Field } from './contact/Field';
import { StatusAlert } from './contact/StatusAlert';
import { useContactForm } from './contact/useContactForm';

interface ContactFormProps {
  config: SiteConfig;
}

export function ContactForm({ config }: ContactFormProps) {
  const form = useContactForm(config);

  return (
    <div className="form-panel">
      <StatusAlert status={form.status} onClose={form.closeStatus} />
      <form className="contact-form" noValidate onSubmit={form.submit}>
        <div className="field-grid">
          <Field
            id="name"
            label="Nome"
            name="nome"
            type="text"
            placeholder="Fulano da Silva"
            autoComplete="name"
            value={form.values.nome}
            error={form.errors.nome}
            limits={config.fieldLimits.nome}
            onBlur={form.blur}
            onChange={form.change}
          />
          <Field
            id="tel"
            label="Telefone"
            name="telefone"
            type="tel"
            placeholder="(48) 99999-9999"
            autoComplete="tel"
            inputMode="tel"
            value={form.values.telefone}
            error={form.errors.telefone}
            limits={config.fieldLimits.telefone}
            onBlur={form.blur}
            onChange={form.change}
          />
        </div>
        <Field
          id="email"
          label="Email"
          name="email"
          type="email"
          placeholder="name@example.com"
          autoComplete="email"
          value={form.values.email}
          error={form.errors.email}
          limits={config.fieldLimits.email}
          onBlur={form.blur}
          onChange={form.change}
        />
        <Field
          id="subject"
          label="Assunto"
          name="assunto"
          type="text"
          placeholder="Assunto"
          value={form.values.assunto}
          error={form.errors.assunto}
          limits={config.fieldLimits.assunto}
          onBlur={form.blur}
          onChange={form.change}
        />
        <Field
          as="textarea"
          id="message"
          label="Resumo do caso"
          name="mensagem"
          placeholder="Mensagem"
          value={form.values.mensagem}
          error={form.errors.mensagem}
          limits={config.fieldLimits.mensagem}
          onBlur={form.blur}
          onChange={form.change}
        />
        <div className="visually-hidden" aria-hidden="true">
          <label htmlFor="website">Seu site</label>
          <input
            type="text"
            name="website"
            id="website"
            tabIndex={-1}
            autoComplete="off"
            value={form.values.website}
            onChange={form.change}
          />
        </div>
        <CaptchaChallenge
          enabled={config.captchaEnabled}
          providers={config.captchaProviders}
          legacyTurnstileSiteKey={config.turnstileSiteKey}
          onChange={form.setCaptcha}
        />
        <button
          type="submit"
          id="submitBtn"
          className="send-button"
          disabled={form.sending || (config.captchaEnabled && !form.captchaToken)}
        >
          {form.sending ? 'Enviando...' : 'Enviar para análise'} <i className="bi bi-send" />
        </button>
      </form>
    </div>
  );
}
