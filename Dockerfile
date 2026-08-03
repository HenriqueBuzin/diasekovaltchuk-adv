FROM node:24.18.1-bookworm-slim AS frontend-dependencies

WORKDIR /app
COPY package.json package-lock.json tsconfig.json ./
RUN npm install --global npm@12.0.2 \
    && npm ci

FROM frontend-dependencies AS frontend-quality

COPY . ./
RUN npm audit --audit-level=high \
    && npm run format:frontend:check \
    && npm run lint:frontend \
    && npm run typecheck:frontend \
    && npm run test:frontend

FROM frontend-dependencies AS frontend-build

COPY frontend/ ./frontend/
RUN npm run build

FROM php:8.5.9-cli-trixie AS php-base

ENV TZ=America/Sao_Paulo

RUN apt-get update \
    && apt-get install --yes --no-install-recommends libicu-dev libzip-dev unzip \
    && docker-php-ext-install intl zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.10.1 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app/backend

FROM php-base AS backend-dependencies

COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader

FROM php-base AS backend-test-environment

RUN apt-get update \
    && apt-get install --yes --no-install-recommends autoconf g++ make \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get purge --yes --auto-remove autoconf g++ make \
    && rm -rf /var/lib/apt/lists/*
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-interaction --no-scripts --prefer-dist
COPY backend/ ./
RUN composer dump-autoload --no-interaction --optimize

FROM backend-test-environment AS backend-quality

RUN composer audit \
    && composer format:check \
    && composer analyse \
    && XDEBUG_MODE=coverage php -d memory_limit=1G vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml \
    && php scripts/check-coverage.php coverage.xml

FROM php-base AS runtime

ENV APP_ENV=production \
    LOG_CHANNEL=stderr

COPY --chown=www-data:www-data backend/ ./
COPY --chown=www-data:www-data --from=backend-dependencies /app/backend/vendor ./vendor
COPY --chown=www-data:www-data --from=frontend-build /app/frontend/dist ./public
RUN install -d -o www-data -g www-data \
    bootstrap/cache \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

USER www-data
EXPOSE 3002 3003
CMD ["sh", "-c", "exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
