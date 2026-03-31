#!/bin/bash
# ═════════════════════════════════════════════════════════════════════════════
# Script de Healthcheck para MySQL/MariaDB
# Verifica que el servidor está listo aceptando conexiones
# ═════════════════════════════════════════════════════════════════════════════

# Usar socket local para evitar problemas de contraseña en healthcheck
if mysqladmin ping -u root --socket=/var/run/mysqld/mysqld.sock 2>/dev/null | grep -q "mysqld is alive"; then
    exit 0
else
    exit 1
fi
