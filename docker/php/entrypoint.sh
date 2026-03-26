#!/bin/bash
set -e

# Función para esperar a MySQL
wait_for_mysql() {
    local host="${DB_HOST:-mysql}"
    local port="${DB_PORT:-3306}"
    local timeout=180
    local elapsed=0
    
    echo "Esperando a MySQL en $host:$port (max ${timeout}s)..."
    
    while [ $elapsed -lt $timeout ]; do
        # Opción 1: Usar bash TCP (sin dependencies)
        if timeout 3 bash -c ">/dev/tcp/$host/$port" 2>/dev/null; then
            echo "✓ MySQL está listo (${elapsed}s)"
            return 0
        fi
        
        printf "  [%3ds/%ds] Esperando...\r" "$elapsed" "$timeout"
        sleep 2
        elapsed=$((elapsed + 2))
    done
    
    echo ""
    echo "❌ ERROR: MySQL no disponible después de ${timeout}s"
    return 1
}

# Validar variables requeridas
if [ -z "$DB_HOST" ] || [ -z "$DB_DATABASE" ]; then
    echo "❌ ERROR: Faltan variables de entorno DB_HOST o DB_DATABASE"
    exit 1
fi

# Esperar a MySQL
wait_for_mysql || exit 1

# Ejecutar migraciones (solo en primer deploy o si hay cambios)
if [ "$SKIP_MIGRATIONS" != "true" ]; then
    echo "Ejecutando migraciones..."
    php artisan migrate --force || {
        echo "❌ ERROR: Fallo en migraciones"
        exit 1
    }
    echo "✓ Migraciones completadas"
fi

# Ejecutar seeders solo si está habilitado
if [ "$RUN_SEEDERS" = "true" ]; then
    echo "Ejecutando seeders..."
    php artisan db:seed --force || true
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
