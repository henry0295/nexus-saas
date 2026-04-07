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

# Build Nuxt
RUN npm run build

# Stage 2: Runtime
FROM node:20-alpine

WORKDIR /app

# Instalar curl para healthcheck
RUN apk add --no-cache curl

# Copiar output del build desde builder
COPY --from=builder /app/.output ./.output

# Exponer puerto
EXPOSE 3000

# Variables de ambiente
ENV HOST=0.0.0.0 PORT=3000

# Start command
CMD ["node", ".output/server/index.mjs"]
