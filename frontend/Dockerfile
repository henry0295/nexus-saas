# Frontend Nuxt 3 - Docker Build

# Stage 1: Build
FROM node:20-alpine as builder

WORKDIR /app

# Copiar package.json
COPY frontend/package*.json ./

# Instalar TODAS las dependencias (incluyendo dev)
RUN npm install

# Copiar código fuente
COPY frontend/ .

# Build Nuxt con verbose output
RUN echo "[BUILD] Iniciando build de Nuxt..." && \
    npm run build && \
    echo "[BUILD] Build completado" && \
    echo "[BUILD] Contenido de .output:" && \
    find .output -type f | head -20 || echo "[ERROR] No se encontró .output"

# Stage 2: Runtime
FROM node:20-alpine

WORKDIR /app

# Instalar curl y netstat para healthcheck diagnostics
RUN apk add --no-cache curl net-tools bash wget

# Copiar healthcheck script
COPY docker/frontend/healthcheck.sh /usr/local/bin/healthcheck.sh
RUN chmod +x /usr/local/bin/healthcheck.sh

# Crear script de startup robusto
RUN cat > /usr/local/bin/startup.sh << 'STARTUP_EOF'
#!/bin/bash
set -e

STARTUP_START=$(date +%s)
echo "[STARTUP] $(date '+%Y-%m-%d %H:%M:%S') Iniciando Nuxt server..."
echo "[STARTUP] Working directory: $(pwd)"
echo "[STARTUP] Node version: $(node --version)"
echo "[STARTUP] NPM version: $(npm --version)"

# Verificar .output
echo "[STARTUP] Verificando .output structure..."
if [ ! -d ".output" ]; then
    echo "[ERROR] .output directorio no existe!"
    ls -la . | head -20
    exit 1
fi

if [ ! -f ".output/server/index.mjs" ]; then
    echo "[ERROR] .output/server/index.mjs no existe!"
    echo "[INFO] Contenido de .output:"
    find .output -type f | head -30 || true
    exit 1
fi

echo "[INFO] .output/server/index.mjs encontrado ✓"
echo "[INFO] Tamaño: $(stat -f%z .output/server/index.mjs 2>/dev/null || stat -c%s .output/server/index.mjs 2>/dev/null) bytes"

# Verificar node_modules
if [ ! -d "node_modules" ]; then
    echo "[ERROR] node_modules no existe!"
    exit 1
fi

echo "[INFO] node_modules presente ✓"
echo "[INFO] Total packages: $(ls node_modules | wc -l)"

# Configurar environment
export HOST=0.0.0.0
export PORT=3000
export NODE_ENV=production

echo "[STARTUP] Configuración:"
echo "  HOST=$HOST"
echo "  PORT=$PORT"
echo "  NODE_ENV=$NODE_ENV"
echo ""
echo "[STARTUP] Iniciando: node .output/server/index.mjs"
echo "[STARTUP] Esto puede tomar 10-30 segundos en la primera ejecución..."
echo ""

# Start with better error handling
if ! node .output/server/index.mjs; then
    echo "[ERROR] Proceso node terminó con error"
    exit 1
fi
STARTUP_EOF

chmod +x /usr/local/bin/startup.sh

# Copiar node_modules y output del build desde builder
COPY --from=builder /app/node_modules ./node_modules
COPY --from=builder /app/.output ./.output

# Validar que .output fue copiado
echo "[VALIDATE] Checking .output..." && \
    ls -la .output/server/ && \
    echo "[VALIDATE] ✓ .output is present" || \
    (echo "[ERROR] .output validation failed!" && exit 1)

# Exponer puerto
EXPOSE 3000

# Variables de ambiente
ENV HOST=0.0.0.0 PORT=3000 NODE_ENV=production

# Start command con script mejorado
CMD ["/usr/local/bin/startup.sh"]
