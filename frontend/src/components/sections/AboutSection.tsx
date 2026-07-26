import { aboutValues } from '../../content/siteContent';

export function AboutSection() {
  return (
    <section className="section intro-section" id="about">
      <div className="container">
        <div className="split-heading">
          <span className="kicker">O escritório</span>
          <h2>Uma advocacia que combina postura firme com escuta cuidadosa.</h2>
          <p>
            Fundada por Larissa de Souza Dias, OAB/SC 62.170, e Vitória Igarçaba Kovaltchuk, OAB/SC 67.779, a Dias &
            Kovaltchuk atua de forma contenciosa, preventiva e consultiva com ética, transparência e atenção real ao
            detalhe.
          </p>
        </div>
        <div className="value-grid">
          {aboutValues.map(([number, title, text]) => (
            <article key={number}>
              <span>{number}</span>
              <h3>{title}</h3>
              <p>{text}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
