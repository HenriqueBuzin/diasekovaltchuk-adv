import { practiceAreas } from '../../content/siteContent';

export function PracticeSection() {
  return (
    <section className="section practice-section" id="acting">
      <div className="container">
        <div className="section-title">
          <span className="kicker">Áreas de atuação</span>
          <h2>Um escritório preparado para proteger o que não pode esperar.</h2>
        </div>
        <div className="practice-board">
          <article className="practice-tile large">
            <i className="bi bi-shield-lock practice-icon" aria-hidden="true" />
            <span>Tribunal do Júri, investigação e processo penal</span>
            <h3>Direito Criminal</h3>
            <p>
              Defesa técnica em crimes contra a vida, inquéritos, processos, execução penal e instâncias comuns ou
              federais.
            </p>
          </article>
          {practiceAreas.map(([icon, title, description]) => (
            <article className="practice-tile" key={title}>
              <i className={`bi ${icon} practice-icon`} aria-hidden="true" />
              <h3>{title}</h3>
              <p>{description}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
