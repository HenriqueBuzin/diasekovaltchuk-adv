import { StructuredData } from './app/StructuredData';
import { useSiteConfig } from './app/useSiteConfig';
import { ContactSection } from './components/ContactSection';
import { Navigation } from './components/Navigation';
import { AboutSection } from './components/sections/AboutSection';
import { HeroSection } from './components/sections/HeroSection';
import { PracticeSection } from './components/sections/PracticeSection';
import { ProcessSection } from './components/sections/ProcessSection';
import { TeamSection } from './components/sections/TeamSection';
import { WhatsAppLink } from './components/WhatsAppLink';
import type { SiteConfig } from './types';

interface SiteConfigProps {
  config: SiteConfig;
}

export function Site({ config }: SiteConfigProps) {
  return (
    <>
      <StructuredData config={config} />
      <Navigation whatsLinkNumber={config.whatsLinkNumber} />
      <main>
        <HeroSection whatsLinkNumber={config.whatsLinkNumber} />
        <AboutSection />
        <PracticeSection />
        <ProcessSection />
        <TeamSection />
        <ContactSection config={config} />
      </main>
      <WhatsAppLink number={config.whatsLinkNumber} className="floating-whatsapp wa-track" label="Falar no WhatsApp">
        <i className="bi bi-whatsapp" />
      </WhatsAppLink>
    </>
  );
}

export default function App() {
  const { config, error } = useSiteConfig();

  if (error)
    return (
      <main className="app-status" role="alert">
        {error}
      </main>
    );
  if (!config) return <main className="app-status">Carregando...</main>;
  return <Site config={config} />;
}
