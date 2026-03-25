FROM nginx:1.26-alpine

# Instalar herramientas útiles
RUN apk add --no-cache \
    curl \
    jq \
    bash \
    openssl

# Crear directorio para SSL
RUN mkdir -p /etc/nginx/ssl

# Copiar configuración
COPY docker/nginx/default.prod.conf /etc/nginx/conf.d/default.conf

# Crear usuario www-data para Nginx (si no existe)
RUN set -e && \
    getent group www-data > /dev/null 2>&1 || addgroup -g 1000 www-data && \
    getent passwd www-data > /dev/null 2>&1 || adduser -D -u 1000 -G www-data www-data || true

# Script de entrada
COPY docker/nginx/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Healthcheck
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

EXPOSE 80 443

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["nginx", "-g", "daemon off;"]
