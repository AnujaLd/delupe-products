#!/bin/sh
set -e

# Make this script tolerant of Windows CRLF line endings when copied into the image
# (we also run a sanitization step in the Dockerfile but keep this here as a fallback)
if command -v sed >/dev/null 2>&1; then
  sed -i 's/\r$//' "$0" || true
fi

# Default DB host to 'db' if not provided (works with docker-compose service name)
: ${DB_HOST:=db}
: ${DB_PORT:=5432}

MAX_WAIT=${DB_WAIT_SECONDS:-60}

echo "Entrypoint: waiting up to ${MAX_WAIT}s for database ${DB_HOST}:${DB_PORT}..."
elapsed=0
until php -r "try { new PDO('pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . (getenv('DB_DATABASE')?:''), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); echo 'ok'; } catch (Exception \$e) { }" >/dev/null 2>&1; do
  sleep 1
  elapsed=$((elapsed+1))
  if [ "$elapsed" -ge "$MAX_WAIT" ]; then
    echo "Timed out waiting for database after ${MAX_WAIT}s. Continuing startup; migrations may fail." >&2
    break
  fi
done

cd /var/www || cd /var/www/html || true

# Optionally run composer install on container start. Set DO_COMPOSER=0 to skip.
: ${DO_COMPOSER:=1}
if [ "$DO_COMPOSER" = "1" ] && command -v composer >/dev/null 2>&1; then
  echo "Running composer install..."
  # prefer-dist for faster installs and avoid dev when in production
  composer install --no-interaction --prefer-dist --optimize-autoloader || true
fi

if [ -f artisan ]; then
  echo "Running Laravel app setup tasks..."

  # ensure permissions
  if [ -d storage ]; then
    chmod -R 775 storage bootstrap/cache || true
    chown -R www-data:www-data storage bootstrap/cache || true
  fi

  # generate app key if not set
  if [ -z "${APP_KEY:-}" ]; then
    php artisan key:generate --force || true
  fi

  # run migrations and seed (if available)
  php artisan migrate --force || true
  php artisan db:seed --force || true

  # import sample products.json if present at project root
  if [ -f /var/www/products.json ] || [ -f /var/www/html/products.json ]; then
    SRC_FILE="/var/www/products.json"
    [ -f /var/www/html/products.json ] && SRC_FILE="/var/www/html/products.json"
    echo "Importing products from ${SRC_FILE}..."
    php artisan app:import-products "${SRC_FILE}" || true
  fi
fi

echo "Entrypoint: setup complete; starting container command."

exec "$@"
