#!/bin/bash

# Script para agregar DB_ROOT_PASSWORD a .env existente
# Ejecutar en: cd /opt/nexus-saas && bash fix-env.sh

ENV_FILE="/opt/nexus-saas/.env"

if [ ! -f "$ENV_FILE" ]; then
    echo "❌ ERROR: No se encontró $ENV_FILE"
    exit 1
fi

# Verificar si ya existe
if grep -q "^DB_ROOT_PASSWORD=" "$ENV_FILE"; then
    echo "✓ DB_ROOT_PASSWORD ya existe en .env"
    exit 0
fi

# Generar contraseña
DB_ROOT_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)

# Agregar al .env después de DB_PASSWORD
sed -i "/^DB_PASSWORD=/a DB_ROOT_PASSWORD=$DB_ROOT_PASSWORD" "$ENV_FILE"

echo "✓ DB_ROOT_PASSWORD agregada al .env"
echo "  Valor: $DB_ROOT_PASSWORD"

# Guardar en credentials.txt también
if [ -f "/opt/nexus-saas/credentials.txt" ]; then
    sed -i "/^  Password:/a \\  Root Password: $DB_ROOT_PASSWORD" "/opt/nexus-saas/credentials.txt"
    echo "✓ credentials.txt actualizado"
fi

# Exportar para este shell
export DB_ROOT_PASSWORD

echo ""
echo "✓ Listo. Ahora reinicia Docker:"
echo "  docker compose -f docker-compose.prod.yml down"
echo "  docker compose -f docker-compose.prod.yml up -d"
