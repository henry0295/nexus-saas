#!/bin/sh
set -e

# Validar variables requeridas
if [ -z "$DB_HOST" ] || [ -z "$DB_DATABASE" ]; then
    echo "ERROR: Faltan variables de entorno DB_HOST o DB_DATABASE"
    exit 1
fi

# Esperar a MySQL
echo "Esperando a MySQL en $DB_HOST..."
timeout 180 sh -c "while ! nc -z $DB_HOST 3306; do sleep 1; done" || exit 1
echo "MySQL disponible"

# Ejecutar migraciones
if [ "$SKIP_MIGRATIONS" != "true" ]; then
    echo "Ejecutando migraciones..."
    php artisan migrate --force || true
fi

# Ejecutar seeders
if [ "$RUN_SEEDERS" = "true" ]; then
    echo "Ejecutando seeders..."
    php artisan db:seed --force || true
fi

# Optimizar aplicación
echo "Optimizando aplicación..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true
echo "NexusSaaS listo"

# Ejecutar comando pasado
exec "$@"
