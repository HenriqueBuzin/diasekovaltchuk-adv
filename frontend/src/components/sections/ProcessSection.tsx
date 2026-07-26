import { processSteps } from '../../content/siteContent';

export function ProcessSection() {
  return (
    <section className="section process-section">
      <div className="container process-grid">
        <div>
          <span className="kicker">Como funciona</span>
          <h2>Você não fica no escuro.</h2>
        </div>
        <div className="process-steps">
          {processSteps.map(([step, title, text]) => (
            <article key={step}>
              <strong>{step}</strong>
              <h3>{title}</h3>
              <p>{text}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
