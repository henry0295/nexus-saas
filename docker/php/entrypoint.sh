#!/bin/bash
set -e

# Esperar a que MySQL esté listo
echo "Esperando a MySQL..."
while ! nc -z "$DB_HOST" "$DB_PORT" 2>/dev/null; do
    sleep 2
done
echo "✓ MySQL está listo"

# Ejecutar migraciones (solo en primer deploy o si hay cambios)
if [ "$SKIP_MIGRATIONS" != "true" ]; then
    echo "Ejecutando migraciones..."
    php artisan migrate --force
    echo "✓ Migraciones completadas"
fi

# Ejecutar seeders solo si está habilitado
if [ "$RUN_SEEDERS" = "true" ]; then
    echo "Ejecutando seeders..."
    php artisan db:seed --force
    echo "✓ Seeders completados"
fi

# Optimizar aplicación
echo "Optimizando aplicación..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✓ Aplicación optimizada"

# Limpiar caché
php artisan cache:clear
php artisan queue:flush

echo "✓ NexusSaaS listo para recibir solicitudes"

# Ejecutar comando pasado como argumento o php-fpm
exec "$@"
