# Frontend Nuxt 3 - Docker Build

# Stage 1: Build
FROM node:18-alpine as builder

WORKDIR /app

# Copiar package.json
COPY frontend/package*.json ./

# Instalar dependencias
RUN npm ci

# Copiar código fuente
COPY frontend/ .

# Build
RUN npm run build

# Stage 2: Runtime
FROM node:18-alpine

WORKDIR /app

# Instalar dependencias de producción
COPY frontend/package*.json ./
RUN npm ci --production

# Copiar output del build
COPY --from=builder /app/.output ./.output

# Exponer puerto
EXPOSE 3000

# Variables de ambiente
ENV HOST=0.0.0.0 PORT=3000

# Start command
CMD ["node", ".output/server/index.mjs"]
