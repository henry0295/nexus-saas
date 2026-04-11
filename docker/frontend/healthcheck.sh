#!/bin/sh
# Healthcheck script for Nuxt frontend

set -e

# Log the check
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
echo "[$TIMESTAMP] [HEALTH] Checking frontend health on http://localhost:3000"

# Try to connect
if curl -sf http://localhost:3000 > /dev/null 2>&1; then
    echo "[$TIMESTAMP] [HEALTH] ✓ Frontend is healthy"
    exit 0
else
    echo "[$TIMESTAMP] [HEALTH] ✗ Frontend is not responding"
    
    # Additional diagnostics
    echo "[$TIMESTAMP] [DIAG] Node processes:"
    ps aux | grep node | grep -v grep || echo "[$TIMESTAMP] [DIAG] Node process not found"
    
    echo "[$TIMESTAMP] [DIAG] Listening ports:"
    netstat -tulpn 2>/dev/null | grep LISTEN || echo "[$TIMESTAMP] [DIAG] netstat not available"
    
    echo "[$TIMESTAMP] [DIAG] Port 3000 status:"
    netstat -tulpn 2>/dev/null | grep 3000 || echo "[$TIMESTAMP] [DIAG] Port 3000 not listening"
    
    echo "[$TIMESTAMP] [DIAG] Attempting direct connection to port 3000:"
    (echo >/dev/tcp/localhost/3000) 2>/dev/null && \
        echo "[$TIMESTAMP] [DIAG] Port 3000 TCP connection OK" || \
        echo "[$TIMESTAMP] [DIAG] Port 3000 TCP connection FAILED"
    
    exit 1
fi
