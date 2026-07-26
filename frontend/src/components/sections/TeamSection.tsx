import { teamProfiles } from '../../content/siteContent';

export function TeamSection() {
  return (
    <section className="section team-section" id="team">
      <div className="container">
        <div className="section-title">
          <span className="kicker">Advogadas</span>
          <h2>Duas sócias, uma condução direta e responsável.</h2>
        </div>
        <div className="team-showcase">
          {teamProfiles.map((profile) => (
            <article className="profile-card" key={profile.name}>
              <img src={profile.image} alt={profile.alt} width="1400" height="2100" loading="lazy" decoding="async" />
              <div>
                <span>{profile.label}</span>
                <h3>{profile.name}</h3>
                <p>{profile.text}</p>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
