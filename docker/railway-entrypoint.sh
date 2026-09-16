#!/bin/bash
set -euo pipefail

PORT="${PORT:-8080}"

sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p var/cache var/log var/share
chown -R www-data:www-data var

# Prefer Railway MySQL plugin vars over a localhost DATABASE_URL baked in the image / .env
if [[ -n "${MYSQLHOST:-}" && -n "${MYSQLUSER:-}" && -n "${MYSQLPASSWORD:-}" && -n "${MYSQLDATABASE:-}" ]]; then
  MYSQLPORT="${MYSQLPORT:-3306}"
  export DATABASE_URL="mysql://${MYSQLUSER}:${MYSQLPASSWORD}@${MYSQLHOST}:${MYSQLPORT}/${MYSQLDATABASE}?serverVersion=8.0&charset=utf8mb4"
elif [[ -n "${MYSQL_URL:-}" ]]; then
  export DATABASE_URL="${MYSQL_URL}"
fi

# Ensure Doctrine knows it is MySQL 8+
if [[ -n "${DATABASE_URL:-}" && "${DATABASE_URL}" != *"serverVersion="* ]]; then
  if [[ "${DATABASE_URL}" == *"?"* ]]; then
    export DATABASE_URL="${DATABASE_URL}&serverVersion=8.0&charset=utf8mb4"
  else
    export DATABASE_URL="${DATABASE_URL}?serverVersion=8.0&charset=utf8mb4"
  fi
fi

echo "Database host: $(php -r 'echo parse_url(getenv("DATABASE_URL") ?: "", PHP_URL_HOST) ?: "(empty)";')"

php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod

# Wait for MySQL (Railway private network can take a few seconds)
for i in $(seq 1 30); do
  if php bin/console doctrine:query:sql "SELECT 1" --env=prod >/dev/null 2>&1; then
    echo "Database is ready."
    break
  fi
  echo "Waiting for database... (${i}/30)"
  sleep 2
  if [[ "${i}" -eq 30 ]]; then
    echo "Database still unreachable. Check DATABASE_URL / MySQL variables on Railway."
    exit 1
  fi
done

php bin/console doctrine:migrations:migrate --no-interaction --env=prod

exec apache2-foreground
