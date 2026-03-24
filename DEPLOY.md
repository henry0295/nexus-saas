# 🚀 NexusSaaS — Guía de Despliegue en Producción

**Versión:** 1.0.0  
**Última actualización:** Marzo 2026  
**Stack:** Laravel 11 + Nginx + MySQL 8 + Redis 7 + Docker Compose  
**Arquitectura:** Multitenant (Row-based)

---

## 📋 Tabla de Contenidos

1. [Despliegue Rápido](#despliegue-rápido-recomendado)
2. [Requisitos](#requisitos)
3. [Arquitectura](#arquitectura)
4. [Despliegue Manual](#despliegue-manual)
5. [Migraciones de BD](#migraciones-de-bd)
6. [Comandos Útiles](#comandos-útiles)
7. [Solución de Problemas](#solución-de-problemas)
8. [Actualización & Rollback](#actualización--rollback)

---

## 🚀 Despliegue Rápido (Recomendado)

### Una Línea ⚡

```bash
curl -sL https://raw.githubusercontent.com/tu-org/nexus-saas/main/deploy.sh | sudo bash -s -- X.X.X.X
```

**Reemplaza `X.X.X.X` con la dirección IP de tu servidor.** 

El script hará:
- ✅ Verificar prerequisitos (root, Docker)
- ✅ Preparar el sistema operativo
- ✅ Instalar Docker (auto-detecta distribucion)
- ✅ Clonar el repositorio
- ✅ Generar credenciales seguras
- ✅ Configurar firewall
- ✅ Desplegar contenedores
- ✅ Ejecutar migraciones de BD
- ✅ Optimizar la aplicación

**Tiempo estimado:** 5-10 minutos (según velocidad de internet)

### Resultado ✨

```
╔════════════════════════════════════════════════════════════╗
║   ¡Despliegue de NexusSaaS completado exitosamente!       ║
╚════════════════════════════════════════════════════════════╝

Accede a tu plataforma:
  → https://X.X.X.X

Credenciales guardadas en:
  /opt/nexus-saas/credentials.txt
```

---

## 📋 Requisitos

### Sistema

- **OS:** Linux (Ubuntu 20.04+, Debian 11+, CentOS 8+, Rocky, AlmaLinux)
- **RAM:** Mínimo 2GB (recomendado 4GB+)
- **CPU:** 2 cores (recomendado 4+)
- **Disco:** 20GB mínimo
- **Conexión:** Internet (se descargan imágenes Docker: ~2GB)

### Network

- **Puerto 22:** SSH (acceso remoto)
- **Puerto 80:** HTTP (se redirige a HTTPS)
- **Puerto 443:** HTTPS (aplicación)
- **Puertos internos:** MySQL (3306), Redis (6379)

### Credenciales

- Acceso root o `sudo` sin contraseña
- Git instalado (o se instala automáticamente)

---

## 🏗️ Arquitectura

### Stack de Servicios

```
┌─────────────────────────────────────────────────────┐
│                    NGINX (443/80)                   │
│        (Reverse Proxy + TLS/SSL Autofirmado)        │
│                                                      │
│  /api → PHP-FPM   /static → Cache   /health → OK   │
└─────────────────────────────────────────────────────┘
                          ↓
         ┌────────────────────────────────┐
         │      PHP-FPM 8.3 Container     │
         │      Laravel 11 Application    │
         │   Health: 60s startup period   │
         └────────────────────────────────┘
                 ↙              ↘
        ┌──────────────┐   ┌──────────────┐
        │   MySQL 8.0  │   │   Redis 7.0  │
        │  (Port 3306) │   │ (Port 6379)  │
        │  Volumen: 5GB│   │ Volumen: 1GB │
        └──────────────┘   └──────────────┘

Volúmenes:
  • mysql_data: Base de datos MySQL
  • redis_data: Cache de Redis
  • app_storage: Storage de Laravel (logs, uploads)
  • app_bootstrap: Cache compilado de Laravel
```

### Aislamiento de Red

Todos los servicios se ejecutan en una red interna (`nexus-network`):

```
Internet
   ↓ (HTTPS:443)
─ NGINX Container ─── Internal Network ───
   ↑     ↓                  ↓
 PHP   MySQL               Redis
       (3306 internal)    (6379 internal)
```

- Solo NGINX expone puertos al exterior (80, 443)
- MySQL y Redis solo accesibles desde PHP
- Comunicación interna segura

---

## 📦 Despliegue Manual

Si necesitas control total o si el deploy.sh falla:

### 1. Preparar Servidor

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Instalar Docker Compose (si no viene incluido)
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### 2. Clonar Repositorio

```bash
cd /opt
sudo git clone -b main https://github.com/tu-org/nexus-saas.git
sudo chown -R $(whoami) nexus-saas
cd nexus-saas
```

### 3. Generar Claves

```bash
# Generar APP_KEY
php artisan key:generate --show

# Copiar output y pegarlo en .env
# APP_KEY=base64:xxxxx

# Generar contraseñas seguras
openssl rand -base64 32  # Para DB_PASSWORD
openssl rand -base64 32  # Para REDIS_PASSWORD
```

### 4. Crear .env

```bash
cp .env.production.example .env

# Editar .env con valores reales
nano .env
```

**Valores importantes a actualizar:**

```env
APP_KEY=base64:YOUR_GENERATED_KEY
APP_URL=https://192.168.1.100

DB_PASSWORD=YOUR_SECURE_DB_PASSWORD
REDIS_PASSWORD=YOUR_SECURE_REDIS_PASSWORD

DEPLOY_IP=192.168.1.100
```

### 5. Construir & Desplegar

```bash
# Build de imágenes
docker compose -f docker-compose.prod.yml build

# Iniciar servicios
docker compose -f docker-compose.prod.yml up -d

# Esperar a que MySQL esté listo (~30s)
sleep 30

# Verificar estado
docker compose -f docker-compose.prod.yml ps

# Debería mostrar:
# CONTAINER ID   STATUS
# nexus_saas_mysql    healthy
# nexus_saas_redis    healthy
# nexus_saas_php      healthy (después de 60s)
# nexus_saas_nginx    healthy
```

### 6. Ejecutar Migraciones

```bash
# Las migraciones se ejecutan automáticamente en el primer inicio
# Pero si necesitas ejecutarlas manualmente:

docker compose -f docker-compose.prod.yml exec -T php php artisan migrate --force

# Verificar que se ejecutaron
docker compose -f docker-compose.prod.yml exec -T php php artisan migrate:status
```

### 7. Verificar Instalación

```bash
# Acceder a la aplicación
curl -k https://localhost/health

# Debería retornar:
# HTTP 200 OK

# Desde el navegador:
https://192.168.1.100
```

---

## 🗄️ Migraciones de BD

### Automáticas (Recomendado)

Las migraciones se ejecutan **automáticamente** al desplegar:

```bash
docker compose -f docker-compose.prod.yml up -d
# ↓
# El contenedor PHP detecta SKIP_MIGRATIONS=false
# ↓
# php artisan migrate --force se ejecuta automáticamente
```

**Ventajas:**
- Sin intervención manual
- Rollback automático si falla
- Logs en Docker

### Manuales (Si necesario)

```bash
# Ejecutar migraciones específicas
docker compose -f docker-compose.prod.yml exec -T php php artisan migrate --force

# Ver estado
docker compose -f docker-compose.prod.yml exec -T php php artisan migrate:status

# Rollback (CUIDADO)
docker compose -f docker-compose.prod.yml exec -T php php artisan migrate:rollback --force
```

### Seeders

Los seeders se ejecutan solo si está habilitado (por defecto está deshabilitado):

```bash
# Habilitar seeders: editar .env
RUN_SEEDERS=true

# Reiniciar PHP
docker compose -f docker-compose.prod.yml restart php

# O ejecutar manualmente
docker compose -f docker-compose.prod.yml exec -T php php artisan db:seed --force
```

**Seeders disponibles:**

- `PricingSeeder`: Precios iniciales (SMS, Email, Audio)
- `SuperadminSeeder`: Usuario administrador (superadmin@nexus-saas.com)

---

## 🛠️ Comandos Útiles

### Estado & Logs

```bash
# Ver estado de todos los servicios
docker compose -f docker-compose.prod.yml ps

# Ver logs en vivo (todas los servicios)
docker compose -f docker-compose.prod.yml logs -f

# Logs de un servicio específico
docker compose -f docker-compose.prod.yml logs -f php
docker compose -f docker-compose.prod.yml logs -f mysql
docker compose -f docker-compose.prod.yml logs -f nginx
```

### Gestión de Servicios

```bash
# Reiniciar todos los servicios
docker compose -f docker-compose.prod.yml restart

# Reiniciar un servicio específico
docker compose -f docker-compose.prod.yml restart php

# Detener servicios (sin eliminar)
docker compose -f docker-compose.prod.yml stop

# Eliminar servicios (guarda volúmenes)
docker compose -f docker-compose.prod.yml down

# Eliminar INCLUYENDO volúmenes (CUIDADO - borra BD)
docker compose -f docker-compose.prod.yml down -v
```

### Base de Datos

```bash
# Acceder a MySQL
docker compose -f docker-compose.prod.yml exec mysql mysql -u nexus_user -p nexus_saas

# Ejecutar query directamente
docker compose -f docker-compose.prod.yml exec -T mysql mysql -u nexus_user -pPASSWORD -e "SELECT COUNT(*) FROM users;"

# Backup de base de datos
docker compose -f docker-compose.prod.yml exec -T mysql mysqldump -u nexus_user -pPASSWORD nexus_saas > backup-$(date +%Y%m%d).sql

# Restaurar backup
docker compose -f docker-compose.prod.yml exec -T mysql mysql -u nexus_user -pPASSWORD nexus_saas < backup-20260324.sql
```

### Aplicación Laravel

```bash
# Ejecutar comando artisan
docker compose -f docker-compose.prod.yml exec -T php php artisan <command>

# Ejemplos:
docker compose -f docker-compose.prod.yml exec -T php php artisan cache:clear
docker compose -f docker-compose.prod.yml exec -T php php artisan config:cache
docker compose -f docker-compose.prod.yml exec -T php php artisan tinker

# Ver lista de migraciones
docker compose -f docker-compose.prod.yml exec -T php php artisan migrate:status

# Ver list de seeders
docker compose -f docker-compose.prod.yml exec -T php php artisan seed:list
```

### Limpieza & Mantenimiento

```bash
# Limpiar caché
docker compose -f docker-compose.prod.yml exec -T php php artisan cache:clear

# Optimizar autoloader
docker compose -f docker-compose.prod.yml exec -T php php artisan optimize

# Ver espacio en disco de volúmenes
docker volume ls
docker volume inspect nexus-saas_mysql_data

# Limpiar logs antiguos (>30 días)
find /var/lib/docker/volumes/nexus-saas_app_storage/_data/logs -mtime +30 -delete
```

---

## 🔧 Solución de Problemas

### El deployment es muy lento

```bash
# Revisar logs de construcción
docker compose -f docker-compose.prod.yml logs php

# Si MySQL tarda en inicializar (primera vez):
docker compose -f docker-compose.prod.yml logs mysql

# Esperar un poco más - MySQL crea tablas en el primer inicio (~60s)
```

### PHP no alcanza "healthy"

**Síntoma:** `docker compose ps` muestra PHP con estado `health: starting...` después de 2+ minutos

```bash
# Ver logs detallados
docker compose -f docker-compose.prod.yml logs --tail=50 php

# Errores comunes:
# 1. MySQL no disponible aún:
docker compose -f docker-compose.prod.yml logs mysql

# 2. Error en migraciones:
# Check: DB_PASSWORD, DB_HOST, DB_DATABASE correctas en .env

# 3. Falta de permisos:
docker compose -f docker-compose.prod.yml exec -T php chmod 755 /app/storage

# Solución: Reiniciar todo
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml up -d
sleep 30
docker compose -f docker-compose.prod.yml logs php
```

### MySQL "No such table"

```bash
# Verificar que migraciones se ejecutaron
docker compose -f docker-compose.prod.yml logs php | grep -i "migrat"

# Si no aparecen, ejecutar manualmente
docker compose -f docker-compose.prod.yml exec -T php php artisan migrate --force

# Ver estado
docker compose -f docker-compose.prod.yml exec -T php php artisan migrate:status
```

### Acceso a BD desde afuera

```bash
# Por defecto, MySQL solo es accesible desde dentro del contenedor
# Para acceder desde tu máquina LOCAL:

# 1. Abrir puerto 3306 en firewall
sudo ufw allow 3306/tcp

# 2. Editar docker-compose.prod.yml:
ports:
  - "3306:3306"  # Descomentar esta línea

# 3. Reiniciar MySQL
docker compose -f docker-compose.prod.yml restart mysql

# 4. Conectar desde cliente MySQL
mysql -h 192.168.1.100 -u nexus_user -p nexus_saas
```

### HTTPS - Certificado autofirmado muestra advertencia

**Esto es NORMAL.** El script genera certificados SSL autofirmados.

```bash
# Para producción con dominio real, usar Let's Encrypt:

# 1. Editar docker-compose.prod.yml para exponer puerto 80 públicamente
# 2. Instalar Certbot en el servidor
sudo apt install certbot python3-certbot-nginx -y

# 3. Obtener certificado
sudo certbot certonly --webroot -w /var/www/certbot -d tu-dominio.com

# 4. Copiar certificados a docker/nginx/ssl/
sudo cp /etc/letsencrypt/live/tu-dominio.com/fullchain.pem docker/nginx/ssl/nexus-saas.crt
sudo cp /etc/letsencrypt/live/tu-dominio.com/privkey.pem docker/nginx/ssl/nexus-saas.key

# 5. Reiniciar Nginx
docker compose -f docker-compose.prod.yml restart nginx
```

### Redis no está disponible

```bash
# Verificar si Redis está corriendo
docker compose -f docker-compose.prod.yml ps redis

# Ver logs
docker compose -f docker-compose.prod.yml logs redis

# Reiniciar
docker compose -f docker-compose.prod.yml restart redis

# Conectar y verificar
docker compose -f docker-compose.prod.yml exec redis redis-cli ping
# Debería retornar: PONG
```

---

## 🔄 Actualización & Rollback

### Actualizar a Nueva Versión

```bash
# 1. Hacer backup de base de datos
docker compose -f docker-compose.prod.yml exec -T mysql mysqldump -uroot -p nexus_saas > backup-preupdate.sql

# 2. Traer últimos cambios del repositorio
cd /opt/nexus-saas
git fetch origin
git checkout main  # o tu rama

# 3. Ver cambios
git log --oneline HEAD~5..HEAD

# 4. Reconstruir imágenes
docker compose -f docker-compose.prod.yml build

# 5. Reiniciar servicios
docker compose -f docker-compose.prod.yml up -d

# 6. Las migraciones se ejecutan automáticamente
# Esperar a que PHP alcance "healthy"

# 7. Verificar que todo funciona
curl -k https://localhost/health
```

### Rollback a Versión Anterior

```bash
# 1. Ir a commit anterior
cd /opt/nexus-saas
git log --oneline
git checkout abc123def  # reemplazar con hash del commit

# 2. Reconstruir imágenes
docker compose -f docker-compose.prod.yml build

# 3. Reiniciar (NO ejecuta migraciones nuevas)
docker compose -f docker-compose.prod.yml up -d

# 4. Si se necesita revertir migraciones (EXTREMO CUIDADO):
docker compose -f docker-compose.prod.yml exec -T php php artisan migrate:rollback --force
```

---

## 📊 Monitoreo

### Health Checks Automáticos

Todos los servicios tienen healthchecks:

```bash
# Ver estado de health
docker inspect nexus_saas_php | grep -A 10 "Health"

# Si alguno falla, Docker intenta reiniciar automáticamente
# Ver logs para diagnosticar
```

### Métricas de Uso

```bash
# Espacio en disco
df -h

# Uso de volúmenes Docker
docker volume ls
du -sh /var/lib/docker/volumes/nexus-saas_*/_data

# Memoria RAM
free -h

# CPU
top  # o: docker stats
```

### Logs de Auditoría

Todos los eventos se registran en:

```
/var/lib/docker/volumes/nexus-saas_app_storage/_data/logs/

# Ver logs de aplicación
tail -f docker/app/storage/logs/laravel.log

# Logs de Nginx
docker compose -f docker-compose.prod.yml logs nginx
```

---

## 🔒 Seguridad

### Cambiar Contraseñas Después del Despliegue

```bash
# Credenciales están en:
cat /opt/nexus-saas/credentials.txt

# ⚠️ IMPORTANTE: Cambiar credenciales por defecto

# 1. Cambiar contraseña de MySQL
docker compose -f docker-compose.prod.yml exec mysql mysql -u root -e "ALTER USER 'nexus_user'@'%' IDENTIFIED BY 'nueva_contraseña';"

# 2. Actualizar .env y reiniciar
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=nueva_contraseña/' .env
docker compose -f docker-compose.prod.yml restart php

# 3. Cambiar contraseña de Redis
# (Requiere reconstruir - más complejo)
```

### Firewall Recomendado

```bash
# Permitir solo lo necesario
sudo ufw default deny incoming
sudo ufw default allow outgoing

# SSH (cambiar puerto si está en producción)
sudo ufw allow 22/tcp

# HTTP & HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# NO permitir MySQL/Redis externamente:
# (Solo funcionan dentro de Docker)

# Aplicar
sudo ufw enable
```

### Backups Automáticos

```bash
# Crear script de backup diario
cat > /opt/nexus-saas/backup.sh <<'BACKUP'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE=/opt/nexus-saas/backups/$DATE.sql
mkdir -p /opt/nexus-saas/backups

docker compose -f docker-compose.prod.yml exec -T mysql mysqldump \
  -u nexus_user -p"$DB_PASSWORD" nexus_saas > "$BACKUP_FILE"

# Mantener solo últimos 7 backups
find /opt/nexus-saas/backups -name "*.sql" -mtime +7 -delete

echo "Backup completado: $BACKUP_FILE"
BACKUP

# Hacer ejecutable
chmod +x /opt/nexus-saas/backup.sh

# Agregar a crontab (ejecutar diariamente a las 2am)
crontab -e
# 0 2 * * * /opt/nexus-saas/backup.sh >> /opt/nexus-saas/backups/cron.log 2>&1
```

---

## 📞 Soporte & Documentación

- **Docs API:** https://tu-dominio.com/api/docs
- **Repo:** https://github.com/tu-org/nexus-saas
- **Issues:** https://github.com/tu-org/nexus-saas/issues
- **Email:** support@nexus-saas.com

---

## 📝 Cambios Registrados

### v1.0.0 (Marzo 2026)

- ✅ dockerized deployment completo
- ✅ Auto-migraciones en despliegue
- ✅ Certificados SSL autofirmados
- ✅ deploy.sh automatizado
- ✅ Soporte para múltiples distribuciones Linux
- ✅ Guía de troubleshooting

---

**¡Feliz despliegue! 🚀**
