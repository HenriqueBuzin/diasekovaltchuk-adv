#!/bin/sh
set -eu

envs_root=${1:-/srv/vps/projects/envs}
project=diasekovaltchuk-adv

secret_value() {
  source_file=$1
  variable=$2
  value=$(grep -m 1 "^${variable}=" "$source_file" 2>/dev/null || true)
  value=${value#*=}
  printf '%s' "$value" | sed -e 's/\r$//' -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
}

migrate_environment() {
  stage=$1
  suffix=$2
  legacy_file=$3
  target_dir="$envs_root/$project/$stage"
  target_env="$target_dir/$project-$suffix.env"
  secrets_dir="$target_dir/secrets"

  test -f "$legacy_file" || {
    echo "Missing legacy env: $legacy_file" >&2
    exit 1
  }

  install -d -m 700 "$secrets_dir"

  app_key=$(secret_value "$legacy_file" APP_KEY)
  if [ -z "$app_key" ]; then
    app_key=$(secret_value "$legacy_file" FLASK_SECRET_KEY)
  fi

  test -n "$app_key" || {
    echo "APP_KEY or FLASK_SECRET_KEY is empty in $legacy_file" >&2
    exit 1
  }

  printf '%s' "$app_key" > "$secrets_dir/app_key"
  printf '%s' "$(secret_value "$legacy_file" MAIL_PASSWORD)" > "$secrets_dir/mail_password"
  printf '%s' "$(secret_value "$legacy_file" TURNSTILE_SECRET_KEY)" > "$secrets_dir/turnstile_secret_key"
  printf '%s' "$(secret_value "$legacy_file" RECAPTCHA_SECRET_KEY)" > "$secrets_dir/recaptcha_secret_key"
  printf '%s' "$(secret_value "$legacy_file" HCAPTCHA_SECRET_KEY)" > "$secrets_dir/hcaptcha_secret_key"
  chgrp 33 "$secrets_dir"/*
  chmod 640 "$secrets_dir"/*

  awk '
    ! /^(APP_KEY|FLASK_SECRET_KEY|MAIL_PASSWORD|TURNSTILE_SECRET_KEY|RECAPTCHA_SECRET_KEY|HCAPTCHA_SECRET_KEY|SECRETS_DIR)=/
  ' "$legacy_file" > "$target_env.tmp"
  printf '\nSECRETS_DIR=%s\n' "$secrets_dir" >> "$target_env.tmp"
  mv "$target_env.tmp" "$target_env"
  chmod 600 "$target_env"

  echo "Migrated $stage to $target_dir"
}

migrate_environment dev dev "$envs_root/$project-dev.env"
migrate_environment prod prod "$envs_root/$project.env"

echo "Legacy env files were preserved. Remove them only after both deployments pass."
