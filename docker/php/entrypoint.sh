#!/bin/sh
set -e

echo "Iniciando NexusSaaS..."

# Crear directorios necesarios si no existen
mkdir -p storage/logs bootstrap/cache storage/app storage/framework/cache storage/framework/views

# Si vendor no existe, ejecutar composer install
if [ ! -d "vendor" ]; then
    echo "Instalando dependencias de Composer..."
    composer install --no-interaction --no-progress --no-dev --optimize-autoloader --no-scripts
fi

# Asegurar permisos correctos para laravel
chown -R laravel:laravel /app

# Validar variables requeridas
if [ -z "$DB_HOST" ] || [ -z "$DB_DATABASE" ]; then
    echo "ERROR: Faltan variables de entorno DB_HOST o DB_DATABASE"
    exit 1
fi

# Esperar a MySQL
echo "Esperando a MySQL en $DB_HOST..."
timeout 180 sh -c "while ! nc -z $DB_HOST 3306; do sleep 1; done" || exit 1
echo "MySQL disponible"

# Ejecutar migraciones SOLO si está explícitamente habilitado
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Ejecutando migraciones..."
    su -s /bin/sh laravel -c "php artisan migrate --force" || exit 1
fi

# Ejecutar seeders
if [ "$RUN_SEEDERS" = "true" ]; then
    echo "Ejecutando seeders..."
    su -s /bin/sh laravel -c "php artisan db:seed --force" || true
fi

# Optimizar aplicación
echo "Optimizando aplicación..."
# DESACTIVADO: config:cache causa problemas al cachear configuración
# Dejar que Laravel cargue la config sin cachear en tiempo real
# su -s /bin/sh laravel -c "php artisan config:cache" || true
su -s /bin/sh laravel -c "php artisan route:cache" || true
su -s /bin/sh laravel -c "php artisan view:cache" || true
echo "NexusSaaS listo"

# Ejecutar comando pasado (php-fpm)
exec "$@"
