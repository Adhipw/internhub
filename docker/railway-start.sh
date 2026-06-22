#!/usr/bin/env sh
set -eu

PORT="${PORT:-8080}"

if [ -n "${DATABASE_URL:-}" ] && [ -z "${DB_URL:-}" ]; then
    export DB_URL="${DATABASE_URL}"
fi

if [ -z "${DB_URL:-}" ] && [ -z "${DATABASE_URL:-}" ] && [ -n "${PGHOST:-}" ]; then
    export DB_CONNECTION="${DB_CONNECTION:-pgsql}"
    export DB_HOST="${PGHOST}"
    export DB_PORT="${PGPORT:-5432}"
    export DB_DATABASE="${PGDATABASE:-${POSTGRES_DB:-}}"
    export DB_USERNAME="${PGUSER:-${POSTGRES_USER:-}}"
    export DB_PASSWORD="${PGPASSWORD:-${POSTGRES_PASSWORD:-}}"
fi

echo "Starting InternHub on port ${PORT}"
echo "Database driver: ${DB_CONNECTION:-not-set}"
if [ -n "${DB_URL:-}" ] || [ -n "${DATABASE_URL:-}" ]; then
    echo "Database URL: configured"
else
    echo "Database host: ${DB_HOST:-not-set}:${DB_PORT:-not-set}"
fi

if [ "${APP_ENV:-}" = "production" ]; then
    if [ -z "${APP_KEY:-}" ]; then
        echo "APP_KEY is missing. Set a stable APP_KEY in Railway before deploying."
        exit 1
    fi

    if [ -z "${APP_URL:-}" ]; then
        echo "APP_URL is missing. Set it to the exact Railway/custom HTTPS domain."
        exit 1
    fi

    if [ "${APP_URL#https://}" = "${APP_URL}" ]; then
        echo "APP_URL must use https:// in production; current value is ${APP_URL}."
        exit 1
    fi

    if [ "${DB_CONNECTION:-}" != "pgsql" ]; then
        echo "DB_CONNECTION should be pgsql on Railway; current value is ${DB_CONNECTION:-not-set}."
        exit 1
    fi
fi

mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

php artisan config:clear || true
php artisan view:clear || true
php artisan storage:link || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    migration_attempt=1
    until php artisan migrate --force; do
        if [ "$migration_attempt" -ge 5 ]; then
            echo "Migration failed after ${migration_attempt} attempts; check Railway database variables."
            exit 1
        fi

        echo "Migration attempt ${migration_attempt} failed; retrying in 5 seconds..."
        migration_attempt=$((migration_attempt + 1))
        sleep 5
    done
fi

php artisan db:show --counts --no-interaction || true
php artisan db:seed --class=RolesAndPermissionsSeeder --force || true

if [ "${SEED_BASELINE_DATA:-false}" = "true" ]; then
    php artisan db:seed --class=ProductionBaselineSeeder --force
fi

php artisan config:cache || true

php-fpm -D
exec nginx -g 'daemon off;'
