# Dias Kovaltchuk Advogadas Associadas

Site institucional com backend Laravel 13 e frontend React 19 + TypeScript + Vite.

## Arquitetura

- `backend/`: Laravel, API, validação, adapters de CAPTCHA/DNS e envio SMTP;
- `frontend/`: React com TypeScript estrito, conteúdo, estilos e testes;
- `tests/e2e/`: cenários Playwright desktop e mobile;
- `Dockerfile`: build multi-stage do React, dependências Composer e runtime PHP 8.5.

Laravel responde `/api/*`, `/docs`, `/openapi.json` e entrega o bundle React nas demais rotas.

## Docker Secrets

O Jenkins cria o link para o env externo:

```bash
ln -sfn /root/projects/envs/diasekovaltchuk-adv.env .env
```

O Compose monta os valores como arquivos:

```text
FLASK_SECRET_KEY     -> /run/secrets/app_key -> APP_KEY_FILE
MAIL_PASSWORD        -> /run/secrets/mail_password
TURNSTILE_SECRET_KEY -> /run/secrets/turnstile_secret_key
RECAPTCHA_SECRET_KEY -> /run/secrets/recaptcha_secret_key
HCAPTCHA_SECRET_KEY  -> /run/secrets/hcaptcha_secret_key
```

`FLASK_SECRET_KEY` foi mantido no env externo por compatibilidade. Laravel normaliza esse segredo para uma chave
AES-256 sem expor o valor no ambiente do container. Proteja os envs externos com `chmod 600`.

## CAPTCHA e e-mail

```env
CAPTCHA_ENABLED=true
CAPTCHA_PROVIDERS=turnstile,recaptcha,hcaptcha
CAPTCHA_TIMEOUT_SECONDS=5
EMAIL_DNS_VALIDATION_ENABLED=true
```

Providers aceitos: `turnstile`, `recaptcha` e `hcaptcha`. Cada token é validado somente no provider que o gerou.
A consulta MX recusa domínios sem servidor de e-mail, mas não confirma a existência da caixa postal individual.

## Desenvolvimento local

Requisitos: PHP 8.5, Composer 2.10, Node.js 24 LTS e npm 12.

```powershell
composer --working-dir=backend install
npm ci
npx playwright install chromium

# terminal 1
$env:PORT = "5000"
php backend/artisan serve --host=127.0.0.1 --port=5000

# terminal 2
npm run dev
```

O Vite fica em `http://127.0.0.1:5173` e encaminha `/api` para Laravel.

## Qualidade e testes

```bash
composer --working-dir=backend format
composer --working-dir=backend analyse
php backend/artisan test
npm test
```

`npm test` executa a suíte completa: Composer audit, Pint, Larastan nível 10, PHPUnit com 100% de classes, métodos e
linhas, npm audit, Prettier, ESLint, TypeScript, Vitest com 100% de statements, branches, funções e linhas, além do
Playwright. Sem Xdebug/PCOV local, a cobertura
Laravel roda automaticamente no target Docker `backend-quality`.

O hook Husky é instalado por `npm install` e executa a mesma suíte antes de cada commit.

## Containers

```bash
docker compose -f docker-compose.yml up -d --build
docker compose -f docker-compose-prod.yml up -d --build
```

Desenvolvimento usa a porta 3003 e produção usa a porta 3002.
