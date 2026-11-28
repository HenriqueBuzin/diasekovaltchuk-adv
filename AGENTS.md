# Contrato de reconstrução

Este arquivo reúne os contratos que precisam ser preservados ao alterar a aplicação.

## Produto

Site institucional com SPA React e backend Laravel. O frontend consulta `/api/site-config`, envia contatos para
`/api/contact` e mantém conversões por WhatsApp. Laravel serve a API, documentação opcional e o fallback da SPA
pela mesma porta.

O formulário aceita Turnstile, reCAPTCHA e hCaptcha em ordem configurável. O fallback ocorre no frontend apenas
quando o script falha ou expira; o backend valida o token exclusivamente no provider informado. A validação MX
confirma o domínio, não a caixa postal.

## Arquitetura e versões

- PHP 8.5.13 e Composer 2.10.2. Laravel 13 recebe as correções de segurança fixadas no lockfile.
- Node.js 24.22.3 LTS, npm 12.0.2, React 19.2.8 e TypeScript 6.0.3 estrito.
- Pint 1.30.3, Larastan 3.10.0/PHPStan 2.2.7 e PHPUnit 13.2.6.
- Prettier 3.9.6, ESLint 10.15.0, Vitest 4.1.12, Vite 8.4.0 e Playwright 1.62.1.

O backend usa controllers finos, Form Requests, value objects e serviços/adapters para CAPTCHA, DNS e e-mail.
O frontend permanece totalmente TypeScript. Dependências são resolvidas pelos lockfiles versionados.

## Configuração e segurança

A configuração vem do `.env` externo, ligado pelo Jenkins ao arquivo
`/srv/vps/projects/envs/diasekovaltchuk-adv/<prod|dev>/diasekovaltchuk-adv-<prod|dev>.env`.
A pasta `secrets/` fica ao lado do env e `SECRETS_DIR` aponta para ela. Compose monta cinco
arquivos em `/run/secrets`, sem copiar valores para o ambiente do processo. Laravel recebe
a chave pelo `APP_KEY_FILE`. Valores diretos sensíveis são sobrescritos com vazio no ambiente do serviço.

Os diretórios de secrets usam `0700`; o env usa `0600`. Arquivos de secrets usam
`0640` e grupo `33` (`www-data`) para o runtime PHP sem root conseguir lê-los
nos bind mounts do Compose. Não tornar segredos legíveis por todos os usuários.

Não adicionar banco, Redis ou outro serviço stateful. O site não precisa deles.

## Qualidade

`php scripts/run_tests.php` executa auditorias, Pint, Larastan nível 10, PHPUnit, cobertura backend, Prettier,
ESLint, typecheck, Vitest e Playwright. Backend exige 100% de classes, métodos e linhas; frontend exige 100% de
statements, branches, functions e linhas. Quando Xdebug/PCOV não existe localmente, a cobertura PHP roda no target
Docker `backend-quality` com Xdebug.

Husky executa a suíte antes de cada commit. GitHub Actions repete todos os gates com Xdebug e Chromium. Não reduzir
thresholds, remover testes nem criar baseline do PHPStan.

## Infraestrutura

Existem `docker-compose.yml` para desenvolvimento e `docker-compose-prod.yml` para produção. As portas
3003 e 3002 são apenas internas, sem publicações no host. O NPM usa os aliases
`diasekovaltchuk-adv-dev:3003` e `diasekovaltchuk-adv:3002`. O firewall mantém o bloqueio
padrão das portas de aplicação; somente o proxy publica HTTP/HTTPS. O serviço canônico é `app`, na rede externa `proxy-network`, com aliases estáveis,
healthcheck em `/api/site-config`, hardening, logs rotacionados e imagens por SHA.

Jenkins mantém `Install`, `Verify`, `Compose`, `Container` e `Deploy`. `Verify` usa o target `backend-quality`;
`Deploy` valida o env antes de derrubar a versão atual e sobe a imagem já construída.

## Critério de aceite

Preservar conteúdo, APIs, CAPTCHA, MX, envio de e-mail, SEO, responsividade e acessibilidade; passar todos os gates,
produzir Compose válidos e container saudável; manter `main` e `dev` com a mesma árvore ao concluir.
