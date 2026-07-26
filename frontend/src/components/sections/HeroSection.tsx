import { heroContent } from '../../content/siteContent';
import { WhatsAppLink } from '../WhatsAppLink';

interface HeroSectionProps {
  whatsLinkNumber: string;
}

export function HeroSection({ whatsLinkNumber }: HeroSectionProps) {
  return (
    <section className="hero-section">
      <div className="hero-media" aria-hidden="true">
        <img src="/images/equipe.webp" alt="" width="2016" height="2488" fetchPriority="high" decoding="async" />
      </div>
      <div className="container-fluid hero-shell">
        <div className="hero-copy">
          <span className="kicker">{heroContent.kicker}</span>
          <h1>{heroContent.title}</h1>
          <p>{heroContent.description}</p>
          <div className="hero-actions">
            <WhatsAppLink number={whatsLinkNumber} className="primary-action wa-track">
              <i className="bi bi-whatsapp" /> Quero atendimento agora
            </WhatsAppLink>
            <a className="secondary-action" href="#contact">
              Enviar meu caso <i className="bi bi-arrow-down" />
            </a>
          </div>
        </div>
        <aside className="decision-card" aria-label="Atendimento jurídico">
          <h2>{heroContent.decisionTitle}</h2>
          <p>{heroContent.decisionDescription}</p>
          <div className="decision-list">
            {heroContent.decisionItems.map((item) => (
              <span key={item}>
                <i className="bi bi-check2" /> {item}
              </span>
            ))}
          </div>
        </aside>
      </div>
      <div className="trust-strip" aria-label="Diferenciais do escritório">
        {heroContent.trustItems.map(([value, label]) => (
          <div key={value}>
            <strong>{value}</strong>
            <span>{label}</span>
          </div>
        ))}
      </div>
    </section>
  );
}
