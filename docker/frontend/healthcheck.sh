#!/bin/sh
# Healthcheck script for Nuxt frontend

set -e

# Log the check
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
echo "[$TIMESTAMP] [HEALTH] Checking frontend health on http://localhost:3000"

# Método 1: Probar conexión TCP (más confiable)
if (echo >/dev/tcp/localhost/3000) 2>/dev/null; then
    echo "[$TIMESTAMP] [HEALTH] ✓ Frontend port 3000 is accepting connections"
    
    # Método 2: Intentar curl pero aceptar cualquier status (no solo 200)
    http_response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:3000 2>/dev/null || echo "000")
    
    if [ "$http_response" != "000" ]; then
        echo "[$TIMESTAMP] [HEALTH] ✓ HTTP response received (status: $http_response)"
        echo "[$TIMESTAMP] [HEALTH] ✓ Frontend is healthy"
        exit 0
    else
        # Si curl falla pero TCP funciona, aceptar como healthy
        echo "[$TIMESTAMP] [HEALTH] ⚠ curl request failed, but TCP connection works - accepting as healthy"
        exit 0
    fi
else
    echo "[$TIMESTAMP] [HEALTH] ✗ Frontend port 3000 is NOT accepting connections"
    
    # Diagnostics
    echo "[$TIMESTAMP] [DIAG] Node processes:"
    ps aux | grep node | grep -v grep || echo "[$TIMESTAMP] [DIAG] Node process not found"
    
    echo "[$TIMESTAMP] [DIAG] Listening ports:"
    netstat -tulpn 2>/dev/null | grep LISTEN || echo "[$TIMESTAMP] [DIAG] netstat not available"
    
    exit 1
fi
