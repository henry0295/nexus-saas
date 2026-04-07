#!/bin/sh
# Healthcheck script for Nuxt frontend

set -e

# Log the check
echo "[$(date)] Checking frontend health on http://localhost:3000"

# Try to connect
if curl -sf http://localhost:3000 > /dev/null 2>&1; then
    echo "[$(date)] ✓ Frontend is healthy"
    exit 0
else
    echo "[$(date)] ✗ Frontend is not responding"
    
    # Additional diagnostics
    ps aux | grep node || echo "Node process not found"
    netstat -tulpn | grep 3000 || echo "Port 3000 not listening"
    
    exit 1
fi
