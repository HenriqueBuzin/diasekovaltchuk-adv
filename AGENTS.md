# Contrato de reconstrução

Este é o contrato técnico canônico do site Dias e Kovaltchuk. `README.md`
explica uso e operação para pessoas; este arquivo reúne tudo que um agente
precisa preservar ao reconstruir ou alterar a aplicação.

## Produto

Site institucional de advocacia com SPA React e backend Flask. A página
apresenta escritório, áreas de atuação, profissionais, conversão por WhatsApp e
formulário de contato. O frontend envia contatos para `/api/contact` e consulta
`/api/site-config`. O Flask serve `/api/*`, o bundle compilado e o fallback da
SPA pela mesma porta.

O formulário valida nome, telefone, e-mail e consentimento, aceita CAPTCHA
Turnstile, reCAPTCHA e hCaptcha em ordem configurável e informa ao backend o
provider que gerou o token. O fallback só ocorre por indisponibilidade de
carregamento/timeout; tokens nunca são testados em provider diferente. A
validação MX confirma apenas o domínio, não a caixa postal. O envio usa Flask
Mail e não pode expor credenciais.

## Arquitetura e versões

- Python 3.14.6, Poetry 2.4.1, Flask 3.1.3.
- Node.js 24.18.0 LTS, npm 11.16.0, React/React DOM 19.2.8.
- TypeScript 6.0.3 estrito, Vite 8.1.5, Vitest 4.1.10 e Playwright 1.62.0.
- Black 26.5.1, isort 8.0.1 e Flake8 7.3.0.

`backend/routes` contém API, estáticos e erros; `backend/services` contém
CAPTCHA, contato e DNS; `frontend/src/content` concentra textos;
`frontend/src/components/sections` concentra seções visuais. React deve
permanecer totalmente TypeScript. Dependências usam versões exatas e os
lockfiles pertencem ao commit. Node/npm são bloqueados por `.nvmrc`, `engines`,
`packageManager` e `.npmrc`.

## Configuração e segurança

Configuração vem de `.env` externo. Na VPS ele é um link para
`/root/projects/envs/diasekovaltchuk-adv.env` ou
`diasekovaltchuk-adv-dev.env`. Segredos Flask, e-mail e CAPTCHA entram no
Compose por `secrets.environment` e chegam ao processo por `*_FILE`; variáveis
diretas vazias impedem exposição em `docker inspect`. Nunca versionar valores.

Variáveis funcionais incluem `PORT`, `CAPTCHA_ENABLED`, `CAPTCHA_PROVIDERS`,
`CAPTCHA_TIMEOUT_SECONDS`, chaves de cada provider,
`EMAIL_DNS_VALIDATION_ENABLED` e configuração SMTP. Não criar PostgreSQL nem
Redis: integrações stateful futuras chegam por rede externa e env.

## Testes

`scripts/run_tests.py` e os wrappers PowerShell/shell executam Black, isort,
Flake8, Prettier, ESLint, typecheck, testes backend/frontend, build e E2E.
Existem suítes unit, API, integration, functional, regression, smoke e E2E em
desktop/mobile. Backend e frontend exigem 100% em linhas e branches; no
frontend também statements e functions. Não reduzir thresholds ou exclusões.

E2E deve tentar a plataforma configurada quando existir e usar Playwright
1.62.0 como adapter de fallback. O workflow instala Chromium em ambiente
limpo.

## Infraestrutura

Existem exatamente `docker-compose.yml` (dev) e
`docker-compose-prod.yml` (produção), sem `version` e sem profiles. Os projetos
são `diasekovaltchuk-adv-dev` e `diasekovaltchuk-adv`. A aplicação é
inseparável e por isso o serviço canônico é `app`; imagens são
`diasekovaltchuk-adv/app:TAG-dev` e `:TAG`.

Comandos ficam no Dockerfile. Ambos os Compose exigem healthcheck, init,
graceful stop, logs rotacionados, labels `infra.*`, `no-new-privileges` e rede
externa `proxy-network`. Produção usa porta 3002 e dev 3003. O Dockerfile
multi-stage compila React em Node e executa Flask em Python/Poetry.

Jenkins mantém exatamente `Install`, `Verify`, `Compose`, `Container` e
`Deploy`, cria o link env, valida antes de derrubar, usa imagem pelo SHA e sobe
com `--no-build --pull never --remove-orphans --wait`. GitHub Actions roda em
`main`/`dev`, usa actions fixadas por SHA, todos os gates, Compose, build, SBOM
e scan. Branches `main` e `dev` devem terminar com a mesma árvore.

## Critério de aceite

Reconstrução correta preserva todo conteúdo e conversões, APIs, CAPTCHA,
validação DNS, envio de e-mail, responsividade e acessibilidade; passa todas as
suítes e 100% de cobertura; produz os dois Compose válidos, container saudável,
segredos somente por arquivo e árvores idênticas em `main`/`dev`.
