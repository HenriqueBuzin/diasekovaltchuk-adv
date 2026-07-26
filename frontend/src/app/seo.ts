import type { SiteConfig } from '../types';

export function legalServiceSchema(config: SiteConfig) {
  return {
    '@context': 'https://schema.org',
    '@type': 'LegalService',
    name: 'Dias Kovaltchuk Advogadas Associadas',
    alternateName: 'Dias & Kovaltchuk Advogadas',
    description:
      'Advocacia com atendimento online em direito criminal, civil, família, saúde, consumidor, trabalhista e previdenciário.',
    url: 'https://diasekovaltchukadv.com/',
    image: 'https://diasekovaltchukadv.com/images/logo.png',
    logo: 'https://diasekovaltchukadv.com/images/logo.png',
    telephone: `+${config.whatsLinkNumber}`,
    email: config.contactEmail,
    priceRange: '$$',
    areaServed: [
      { '@type': 'Country', name: 'Brasil' },
      { '@type': 'State', name: 'Santa Catarina' }
    ],
    address: {
      '@type': 'PostalAddress',
      addressLocality: 'Florianópolis',
      addressRegion: 'SC',
      addressCountry: 'BR'
    },
    founder: [
      { '@type': 'Person', name: 'Larissa de Souza Dias', jobTitle: 'Advogada', identifier: 'OAB/SC 62.170' },
      { '@type': 'Person', name: 'Vitória Igarçaba Kovaltchuk', jobTitle: 'Advogada', identifier: 'OAB/SC 67.779' }
    ],
    sameAs: [config.socialFacebook, config.socialInstagram]
  };
}
