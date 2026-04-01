#!/bin/bash
# Diagnostic commands to run on the server

echo "=== 1. Verificar contenido del archivo welcome.blade.php ==="
docker compose -f docker-compose.prod.yml exec -T php cat /app/resources/views/welcome.blade.php | head -20

echo ""
echo "=== 2. Verificar si el archivo existe y su tamaño ==="
docker compose -f docker-compose.prod.yml exec -T php ls -lh /app/resources/views/welcome.blade.php

echo ""
echo "=== 3. Ver últimos errores en Laravel ==="
docker compose -f docker-compose.prod.yml exec -T php tail -50 /app/storage/logs/laravel.log

echo ""
echo "=== 4. Verificar permisos del directorio views ==="
docker compose -f docker-compose.prod.yml exec -T php ls -la /app/resources/views/ | head -5

echo ""
echo "=== 5. Probar compilación de vistas ==="
docker compose -f docker-compose.prod.yml exec -T php php artisan view:clear
docker compose -f docker-compose.prod.yml exec -T php php artisan view:cache
