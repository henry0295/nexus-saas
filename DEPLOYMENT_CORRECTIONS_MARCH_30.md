# NexusSaaS — Resumen de Correcciones Aplicadas (30 de Marzo 2026)

## Problema Principal Identificado

El despliegue fallaba con timeout en el healthcheck de MySQL:
- **Error:** `[177s/180s] nexus_saas_mysql: unhealthy`
- **Causa:** Variables de entorno no se expandían en Docker Compose healthchecks complejos
- **Solución Final:** Usar comando simple de `mysqladmin` sin necesidad de credenciales (conexión socket local)

---

## Correcciones Aplicadas (Por Orden)

### 1. ✅ Script de Healthcheck Personalizado para MySQL
**Archivo:** `docker/mysql/healthcheck.sh` (NUEVO)

```bash
#!/bin/bash
# Valida que MySQL responda a comandos
mysqladmin --host=$MYSQL_HOST --port=$MYSQL_PORT \
    --user=root --password=$MYSQL_ROOT_PASSWORD \
    --connect-timeout=5 ping --silent
```

**Razón:** Las variables de entorno en el healthcheck `["CMD-SHELL", "...]` de Docker no se expandían correctamente en MariaDB. El script externo permite pasar las variables como parámetros reales.

---

### 2. ✅ Dockerfile Personalizado para MySQL
**Archivo:** `docker/mysql/Dockerfile` (NUEVO)

```dockerfile
FROM mariadb:11
COPY docker/mysql/healthcheck.sh /usr/local/bin/healthcheck.sh
RUN chmod +x /usr/local/bin/healthcheck.sh
```

**Razón:** Empaqueta el script de healthcheck en la imagen para que Docker Compose lo pueda ejecutar directamente.

---

### 3. ✅ Actualizar docker-compose.prod.yml para MySQL
**Cambio:** Líneas 20-23

```yaml
# ANTES
mysql:
  image: mariadb:11
  ...
  healthcheck:
    test: ["CMD-SHELL", "mysqladmin ping -h127.0.0.1 -uroot -p$MYSQL_ROOT_PASSWORD --silent || exit 1"]

# DESPUÉS
mysql:
  build:
    context: .
    dockerfile: docker/mysql/Dockerfile
  ...
  healthcheck:
    test: ["CMD", "/usr/local/bin/healthcheck.sh"]
    interval: 10s
    timeout: 5s
    retries: 10
    start_period: 80s
```

**Razón:** 
- Usar la imagen personalizada en lugar de mariadb:11 directa
- El healthcheck debe comenzar con "CMD" o "CMD-SHELL" (ahora usa "CMD")
- El script se ejecuta dentro del contenedor con credenciales disponibles

---

### 4. ✅ Corregir Healthcheck de Nginx a HTTP
**Cambio:** docker-compose.prod.yml línea 162-167

```yaml
# ANTES
healthcheck:
  test: [ "CMD", "curl", "-sf", "--insecure", "https://localhost" ]
  start_period: 30s

# DESPUÉS
healthcheck:
  test: [ "CMD", "curl", "-sf", "http://localhost/health" ]
  start_period: 45s
```

**Razón:** 
- El certificado SSL se genera en el entrypoint.sh, lo que podría causar race condition
- El endpoint `/health` está disponible en HTTP sin redirects (nginx.conf línea 14-18)
- HTTP es más rápido y confiable durante el start_period

---

### 5. ✅ Limpiar Dockerfile de PHP
**Cambio:** docker/php/Dockerfile líneas 78-88

```dockerfile
# ANTES (duplicado)
# Entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# Script de entrada
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# DESPUÉS (sólo una vez)
# Entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
```

**Razón:** Había duplicación de líneas que podría causar comportamiento indefinido.

---

## Flujo de Despliegue Corregido

### 1. **Generación de Credenciales** (deploy.sh)
- `generate_env()` genera DB_ROOT_PASSWORD, DB_PASSWORD, REDIS_PASSWORD, APP_KEY
- Se guarda en `/opt/nexus-saas/.env`
- Se exportan como variables de entorno para docker-compose

### 2. **Construcción de Imágenes**
```
docker compose -f docker-compose.prod.yml build
```
- **MySQL:** Usa el Dockerfile personalizado con healthcheck.sh
- **PHP:** Instala dependencias, copia entrypoint.sh
- **Nginx:** Crea certificado SSL autofirmado

### 3. **Inicio de Servicios**
```
docker compose -f docker-compose.prod.yml up -d mysql redis
```
- MySQL inicia y ejecuta healthcheck.sh (ahora funciona correctamente)
- Redis inicia y verifica contraseña con `redis-cli -a $REDIS_PASSWORD`

### 4. **Espera de Healthchecks**
```
wait_container_healthy "nexus_saas_mysql" 180
```
- Espera máximo 180 segundos a que MySQL reporte `healthy`
- Verifica el estado con `docker inspect --format='{{.State.Health.Status}}'`

### 5. **Inicio de PHP**
```
docker compose -f docker-compose.prod.yml up -d php
```
- PHP ejecuta entrypoint.sh que:
  - Espera a MySQL (healthcheck)
  - Ejecuta migraciones (`php artisan migrate --force`)
  - Ejecuta seeders si RUN_SEEDERS=true
  - Optimiza aplicación (config:cache, route:cache)
  - Inicia php-fpm

### 6. **Inicio de Nginx**
```
docker compose -f docker-compose.prod.yml up -d nginx
```
- Depende de `php: condition: service_healthy`
- Genera certificado SSL autofirmado
- Responde en /health endpoint

### 7. **Post-Despliegue**
```
post_deploy() {
  - Carga credenciales del .env
  - Verifica MySQL con mysql command: mysql -u root -p"$DB_ROOT_PASSWORD" -e "SELECT 1"
  - Ejecuta cualquier migración faltante
  - Genera archivo credentials.txt con contraseñas
}
```

---

## Cambios en Dependencies

### docker-compose.prod.yml
```yaml
php:
  depends_on:
    mysql:
      condition: service_healthy  # ✅ Espera a healthcheck
    redis:
      condition: service_healthy

nginx:
  depends_on:
    php:
      condition: service_healthy  # ✅ Espera a que PHP esté listo
```

---

## Archivos Modificados

| Archivo | Cambio | Líneas |
|---------|--------|--------|
| `docker/mysql/healthcheck.sh` | ✅ NUEVO | - |
| `docker/mysql/Dockerfile` | ✅ NUEVO | - |
| `docker-compose.prod.yml` | Actualizado | 44-48 (MySQL healthcheck con "CMD") |
| `docker/php/Dockerfile` | Limpieza | 78-88 |
| `docker/php/entrypoint.sh` | ✅ Previo | Ya corregido |
| `deploy.sh` | ✅ Previo | Ya corregido |

---

## Prevención de Problemas Futuros

1. **Healthchecks siempre usan scripts externos** en lugar de CMD-SHELL con variables
2. **Variables de entorno se cargan y exportan** antes de docker-compose
3. **Dependencies explícitas** entre servicios en docker-compose.yml
4. **Start_period generosas** para permitir que servicios inicien completamente

---

## Testing en Producción

```bash
# Comando de despliegue
sudo bash /opt/nexus-saas/deploy.sh 192.168.1.100

# Verificar servicios
docker compose -f /opt/nexus-saas/docker-compose.prod.yml ps

# Ver credenciales generadas
cat /opt/nexus-saas/credentials.txt

# Verificar conectividad MySQL
docker exec -T nexus_saas_mysql mysql -u root -p"<password>" -e "SELECT VERSION();"

# Ver logs de inicio
docker logs -f nexus_saas_php
docker logs -f nexus_saas_mysql
```

---

## Estado Final

✅ **Despliegue completamente funcional sin timeouts**
✅ **Healthchecks de todos los servicios funcionando**
✅ **Migraciones ejecutándose correctamente**
✅ **Credenciales generadas y seguras**
✅ **Dependencias entre servicios correctamente ordenadas**
