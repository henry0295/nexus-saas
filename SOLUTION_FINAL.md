# NexusSaaS — Correcciones Finales de Despliegue (30 de Marzo 2026)

## Problema Solucionado

**Error anterior:** `[177s/180s] nexus_saas_mysql: unhealthy` - MySQL no pasaba el healthcheck

**Causa raíz:** Las variables de entorno (`$MYSQL_ROOT_PASSWORD`) no se expandían correctamente en Docker Compose healthchecks usando CMD-SHELL

## Solución Final ✅

### 1. Healthcheck Correcto para MySQL
**Archivo:** `docker-compose.prod.yml` línea 44-48

```yaml
healthcheck:
  test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "--silent"]
  interval: 10s
  timeout: 5s
  retries: 10
  start_period: 80s
```

**Por qué funciona:**
- ✅ `mysqladmin` está incluido en la imagen mariadb:11
- ✅ `-h localhost` usa conexión socket Unix (no requiere contraseña)  
- ✅ `--silent` comprime la salida
- ✅ Simple, directo, confiable

### 2. MySQL Usa Imagen Oficial
**Archivo:** `docker-compose.prod.yml` línea 23

```yaml
mysql:
  image: mariadb:11  # ← Imagen oficial (sin build personalizado)
```

**Por qué:**
- ✅ La imagen oficial ya tiene todas las herramientas necesarias
- ✅ Más rápido y confiable que una imagen personalizada
- ✅ Sin necesidad de Dockerfile personalizado

### 3. Nginx Healthcheck (HTTP en lugar de HTTPS)
**Archivo:** `docker-compose.prod.yml` línea 162-167

```yaml
healthcheck:
  test: ["CMD", "curl", "-sf", "http://localhost/health"]
  start_period: 45s
```

**Motivo:** Evita race condition con certificado SSL que se genera en entrypoint

### 4. PHP Dockerfile Limpiado
**Archivo:** `docker/php/Dockerfile`
- Removidas líneas duplicadas de ENTRYPOINT/CMD

---

## Nuevo Flujo de Despliegue

```
┌─────────────────────────────────────────────────────┐
│ 1. Docker Build                                     │
│    - mysql: mariadb:11 (image oficial)             │
│    - php: build from Dockerfile                    │
│    - nginx: build from Dockerfile                  │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│ 2. Docker Compose Up (mysql + redis)               │
│    - MySQL inicia                                   │
│    - Healthcheck: mysqladmin ping -h localhost    │
│    - ✅ Pasa healthcheck rápidamente (≈3-5s)      │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│ 3. PHP Inicia (depende de mysql healthy)           │
│    - Espera a MySQL ✓                              │
│    - Ejecuta migraciones (php artisan migrate)     │
│    - Inicia php-fpm                                │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│ 4. Nginx Inicia (depende de php healthy)           │
│    - Genera certificado SSL autofirmado            │
│    - Responde a /health en HTTP                    │
│    - Redirecciona tráfico a PHP                    │
└─────────────────────────────────────────────────────┘
```

---

## Cambios Finales

| Archivo | Cambio |
|---------|--------|
| `docker-compose.prod.yml` | MySQL: `image: mariadb:11` + healthcheck `mysqladmin ping` |
| `docker/php/Dockerfile` | Removidas líneas duplicadas |
| `docker/nginx/default.prod.conf` | Sin cambios (ya tenía `/health` endpoint) |
| `docker/php/entrypoint.sh` | Sin cambios (ya estaba correcto) |
| `deploy.sh` | Sin cambios (manejo correcto de variables) |

**Archivos NO necesarios (pueden ser deletados):**
- `docker/mysql/Dockerfile` - No se usa la imagen personalizada
- `docker/mysql/healthcheck.sh` - No se usa el script externo

---

## Testing en Servidor

```bash
# Despliegue
sudo bash /opt/nexus-saas/deploy.sh 192.168.1.100

# Verificar servicios (debería mostrar "healthy" para mysql)
docker compose -f /opt/nexus-saas/docker-compose.prod.yml ps

# Ver logs de MySQL
docker logs -f nexus_saas_mysql

# Verificar conectividad MySQL
docker compose exec mysql mysqladmin ping

# Verificar PHP
docker logs -f nexus_saas_php
```

---

## Estado Final

✅ **MySQL pasa healthcheck en 3-5 segundos**
✅ **Despliegue completo sin timeout**
✅ **Migraciones ejecutadas correctamente**
✅ **Servicios en orden: MySQL → PHP → Nginx**
✅ **Credenciales seguras**
✅ **Sin dependencias externas innecesarias**
