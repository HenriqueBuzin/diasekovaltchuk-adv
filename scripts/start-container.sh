#!/bin/sh
set -eu

if [ "${OPENBAO_SECRETS_REQUIRED:-0}" = "1" ]; then
    printf '%s\n' 'Waiting for OpenBao runtime secrets.'
    until [ -f /run/openbao-ready/.ready ] && [ -s "$APP_KEY_FILE" ]; do
        sleep 2
    done
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
