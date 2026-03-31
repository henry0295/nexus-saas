#!/bin/bash
# ═════════════════════════════════════════════════════════════════════════════
# Script de Healthcheck para MySQL/MariaDB
# Verifica que el servidor está listo aceptando conexiones
# ═════════════════════════════════════════════════════════════════════════════

# Método 1: Intentar ejecutar un comando MySQL simple
mariadb -u root -e "SELECT 1" >/dev/null 2>&1 && exit 0

# Método 2: Fallback - verificar que el puerto 3306 responde
timeout 2 bash -c "echo > /dev/tcp/localhost/3306" >/dev/null 2>&1 && exit 0

# Si ambos fallan
exit 1

