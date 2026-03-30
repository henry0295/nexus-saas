# ═════════════════════════════════════════════════════════════════════════════
# Dockerfile - MariaDB Personalizado
# Agrega script de healthcheck para verificación de servicio
# ═════════════════════════════════════════════════════════════════════════════

FROM mariadb:11

# Copiar script de healthcheck
COPY docker/mysql/healthcheck.sh /usr/local/bin/healthcheck.sh

# Hacer script ejecutable
RUN chmod +x /usr/local/bin/healthcheck.sh

# Usar MariaDB default como entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["mysqld"]
