#!/bin/sh
set -e

# Si no existe certificado SSL, crear uno autofirmado
if [ ! -f /etc/nginx/ssl/nexus-saas.crt ] || [ ! -f /etc/nginx/ssl/nexus-saas.key ]; then
    echo "[Nginx] Generando certificado SSL autofirmado..."
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout /etc/nginx/ssl/nexus-saas.key \
        -out /etc/nginx/ssl/nexus-saas.crt \
        -subj "/C=CO/ST=Bogota/L=Bogota/O=NexusSaaS/OU=IT/CN=nexus-saas.local" \
        -addext "subjectAltName=DNS:localhost,DNS:nexus-saas.local,IP:127.0.0.1" 2>/dev/null || true
    echo "[Nginx] ✓ Certificado SSL creado"
fi

# Validar configuración de Nginx
echo "[Nginx] Validando configuración..."
nginx -t

echo "[Nginx] ✓ Iniciando Nginx..."
exec "$@"
