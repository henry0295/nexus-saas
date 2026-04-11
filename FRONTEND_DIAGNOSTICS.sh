#!/bin/bash

# NexusSaaS Frontend Diagnostics Script
# Ejecuta esto cuando el frontend no esté iniciando correctamente

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}╔══════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  NexusSaaS Frontend Diagnostics              ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════╝${NC}"
echo ""

# Check if containers are running
echo -e "${YELLOW}[1/8] Verificando estado de contenedores...${NC}"
docker ps -a | grep -E "nexus_saas_(frontend|nginx|php|mysql|redis)" || echo "No containers found"
echo ""

# Check frontend container status
echo -e "${YELLOW}[2/8] Estado del contenedor frontend:${NC}"
docker inspect nexus_saas_frontend --format='
Name: {{.Name}}
State: {{.State.Running}}
Health Status: {{.State.Health.Status}}
Exit Code: {{.State.ExitCode}}
' 2>/dev/null || echo "Frontend container not found"
echo ""

# Show frontend container logs
echo -e "${YELLOW}[3/8] Últimos 50 logs del frontend:${NC}"
docker logs --tail=50 nexus_saas_frontend 2>/dev/null | tail -50 || echo "No logs available"
echo ""

# Check if .output exists in container
echo -e "${YELLOW}[4/8] Verificando .output en contenedor:${NC}"
docker exec nexus_saas_frontend ls -la /app/.output 2>/dev/null || echo "No .output found or container not running"
echo ""

# Check if node_modules exists
echo -e "${YELLOW}[5/8] Verificando node_modules:${NC}"
docker exec nexus_saas_frontend ls -la /app/node_modules | head -20 2>/dev/null || echo "No node_modules found"
echo ""

# Check if port 3000 is listening
echo -e "${YELLOW}[6/8] Verificando puerto 3000 en contenedor:${NC}"
docker exec nexus_saas_frontend sh -c 'netstat -tulpn 2>/dev/null | grep 3000 || echo "Port 3000 not listening"' 2>/dev/null || echo "Cannot check port"
echo ""

# Check Node process
echo -e "${YELLOW}[7/8] Procesos Node en el contenedor:${NC}"
docker exec nexus_saas_frontend ps aux | grep node || echo "No node process found"
echo ""

# Try to manually test healthcheck
echo -e "${YELLOW}[8/8] Ejecutando healthcheck manualmente:${NC}"
docker exec nexus_saas_frontend /usr/local/bin/healthcheck.sh || echo "Healthcheck failed"
echo ""

# Summary
echo -e "${BLUE}╔══════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  OPCIONES DE SOLUCIÓN                        ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${GREEN}Opción 1: Rebuild y restart del frontend${NC}"
echo "  docker compose -f docker-compose.prod.yml down"
echo "  docker volume rm nexus-saas_mysql_data nexus-saas_redis_data 2>/dev/null || true"
echo "  cd /opt/nexus-saas && sudo bash deploy.sh --clean 192.168.101.99"
echo ""
echo -e "${GREEN}Opción 2: Rebuild solo frontend${NC}"
echo "  cd /opt/nexus-saas"
echo "  docker compose -f docker-compose.prod.yml build frontend"
echo "  docker compose -f docker-compose.prod.yml up frontend"
echo ""
echo -e "${GREEN}Opción 3: Ver logs en tiempo real${NC}"
echo "  docker compose -f docker-compose.prod.yml logs -f frontend"
echo ""
echo -e "${GREEN}Opción 4: Ejecutar frontend interactivamente${NC}"
echo "  docker compose -f docker-compose.prod.yml run --rm frontend node .output/server/index.mjs"
echo ""
