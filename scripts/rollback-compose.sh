#!/bin/sh
set -eu

environment=${1:?use: rollback-compose.sh <prod|dev> <image-tag>}
export IMAGE_TAG=${2:?use: rollback-compose.sh <prod|dev> <image-tag>}

case "$environment" in
  prod)
    export COMPOSE_PROJECT_NAME=diasekovaltchuk-adv
    config_dir=/root/projects/envs/diasekovaltchuk-adv/prod
    env_file=$config_dir/diasekovaltchuk-adv-prod.env
    set -- -f docker-compose-prod.yml
    ;;
  dev)
    export COMPOSE_PROJECT_NAME=diasekovaltchuk-adv-dev
    config_dir=/root/projects/envs/diasekovaltchuk-adv/dev
    env_file=$config_dir/diasekovaltchuk-adv-dev.env
    set -- -f docker-compose.yml
    ;;
  *)
    echo "environment must be prod or dev" >&2
    exit 2
    ;;
esac

test -f "$env_file"
ln -sfn "$env_file" .env
docker compose "$@" up -d --no-build --remove-orphans --wait
