#!/bin/bash
# ═════════════════════════════════════════════════════════════════════════════
# Script de Healthcheck para MySQL/MariaDB
# Usado por Docker Compose para verificar que el servicio está listo
# ═════════════════════════════════════════════════════════════════════════════

set -e

# Variables
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-root_secure_password}"
MYSQL_HOST="${MYSQL_HOST:-127.0.0.1}"
MYSQL_PORT="${MYSQL_PORT:-3306}"
TIMEOUT="${TIMEOUT:-5}"

# Intentar conectar a MySQL
mysqladmin \
    --host="${MYSQL_HOST}" \
    --port="${MYSQL_PORT}" \
    --user=root \
    --password="${MYSQL_ROOT_PASSWORD}" \
    --connect-timeout="${TIMEOUT}" \
    ping --silent

if [ $? -eq 0 ]; then
    exit 0
else
    exit 1
fi
