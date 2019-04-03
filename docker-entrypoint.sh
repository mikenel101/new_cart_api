#!/bin/bash
set -e

# Create Docker Stdout & Stderr for Docker Logs
source /opt/docker/bin/config.sh
createDockerStdoutStderr

APP_ENV=${ENV_APP_ENV:-'local'}
RANDOM_KEY=`< /dev/urandom tr -dc A-Za-z0-9 | head -c${1:-32};echo;`

if [ $APP_ENV == 'local' ]; then
  APP_DEBUG=true
else
  APP_DEBUG=false
fi

if [ "$1" = 'lint' ]; then
  gosu "${APPLICATION_USER}" /app/vendor/bin/phpcs --standard=PSR2 app/
  exit 0
fi

if [ "$1" = 'test' ]; then
  if [ ! -f ".env.testing" ]; then
    sed -e "s/DB_HOST=localhost/DB_HOST=${TEST_DB_HOST}/g" \
      -e "s/DB_PORT=3306/DB_PORT=${TEST_DB_PORT}/g" \
      -e "s/DB_DATABASE=homestead/DB_DATABASE=${TEST_DB_DATABASE}/g" \
      -e "s/DB_USERNAME=homestead/DB_USERNAME=${TEST_DB_USER}/g" \
      -e "s/DB_PASSWORD=secret/DB_PASSWORD=${TEST_DB_PASSWORD}/g" \
      -e "s/APP_ENV=local/APP_ENV=testing/g" \
      -e "s/APP_DEBUG=true/APP_DEBUG=false/g" \
      -e "s/APP_KEY=SomeRandomKey!!!/APP_KEY=${RANDOM_KEY}/g" \
      < .env.example \
      > .env.testing
  fi

  ## Currently no need for cart_api
  # /usr/local/bin/dockerize --wait tcp://${TEST_DB_HOST}:${TEST_DB_PORT} -timeout 60s
  gosu "${APPLICATION_USER}" /app/vendor/bin/phpunit "${@:2:($#-1)}"
  exit 0
fi

if [ ! -f ".env" ]; then
  sed -e "s/DB_HOST=localhost/DB_HOST=${ENV_DB_HOST}/g" \
    -e "s/DB_PORT=3306/DB_PORT=${ENV_DB_PORT}/g" \
    -e "s/DB_DATABASE=homestead/DB_DATABASE=${ENV_DB_DATABASE}/g" \
    -e "s/DB_USERNAME=homestead/DB_USERNAME=${ENV_DB_USER}/g" \
    -e "s/DB_PASSWORD=secret/DB_PASSWORD=${ENV_DB_PASSWORD}/g" \
    -e "s/APP_ENV=local/APP_ENV=${ENV_APP_ENV}/g" \
    -e "s/APP_DEBUG=true/APP_DEBUG=${APP_DEBUG}/g" \
    -e "s/APP_KEY=SomeRandomKey!!!/APP_KEY=${RANDOM_KEY}/g" \
    -e "s/CACHE_DRIVER=file/CACHE_DRIVER=${ENV_CACHE_DRIVER}/g" \
    < .env.example \
    > .env
fi

if [ "$1" = 'artisan' ]; then
  gosu "${APPLICATION_USER}" php artisan "${@:2:($#-1)}"
  exit 0
fi

## Check migrations
## Currently no need for cart_api
# /usr/local/bin/dockerize --wait tcp://${ENV_DB_HOST}:${ENV_DB_PORT} -timeout 60s
# gosu "${APPLICATION_USER}" php artisan migrate --force

## Start services
exec /opt/docker/bin/service.d/supervisor.sh
