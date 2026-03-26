# SOLUCIONES TÉCNICAS - NexusSaaS Deployment Issues

## 🔧 FIX #1: deploy.sh - Generar DB_ROOT_PASSWORD Correctamente

### ❌ CÓDIGO ACTUAL (INCORRECTO)

```bash
# Ubicación: deploy.sh, función generate_env(), línea ~465
generate_env() {
    log_info "Configurando variables de entorno..."
    
    cd "$INSTALL_DIR"
    
    if [ -f "$INSTALL_DIR/.env" ] && [ "$CLEAN_INSTALL" != true ]; then
        # ... reutilizar ...
    else
        log_info "Generando credenciales nuevas..."
        
        local DB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
        local REDIS_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
        local APP_KEY=$(php artisan key:generate --show 2>/dev/null | grep -oP 'base64:.*' || echo "base64:$(openssl rand -base64 32)")
        
        cat > "$INSTALL_DIR/.env" <<EOF
APP_NAME="NexusSaaS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://$DEPLOY_IP
APP_KEY=$APP_KEY

# ─── Database ────────────────────
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=nexus_saas
DB_USERNAME=nexus_user
DB_PASSWORD=$DB_PASSWORD
DB_ROOT_PASSWORD=\$(openssl rand -base64 32)  # ❌ AQUÍ ESTÁ EL BUG
# Esto genera literalmente: DB_ROOT_PASSWORD=$(openssl rand -base64 32)
# Es un STRING, no una ejecución de comando

# ... resto ...
EOF
```

### ✅ CÓDIGO CORREGIDO

```bash
generate_env() {
    log_info "Configurando variables de entorno..."

    cd "$INSTALL_DIR"

    if [ -f "$INSTALL_DIR/.env" ] && [ "$CLEAN_INSTALL" != true ]; then
        log_info "Archivo .env existente encontrado, reutilizando credenciales..."
        source "$INSTALL_DIR/.env" 2>/dev/null || true
        
        sed -i "s|^APP_URL=.*|APP_URL=https://$DEPLOY_IP|" "$INSTALL_DIR/.env"
        FRESH_ENV=false
    else
        log_info "Generando credenciales nuevas..."
        
        # ✅ GENERAR ANTES - NO dentro del heredoc
        local DB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
        local REDIS_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
        local DB_ROOT_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
        local APP_KEY=$(php artisan key:generate --show 2>/dev/null | grep -oP 'base64:.*' || echo "base64:$(openssl rand -base64 32)")
        
        # ✅ EXPORTAR las variables para que docker-compose las pueda usar
        export DB_PASSWORD
        export REDIS_PASSWORD
        export DB_ROOT_PASSWORD
        export APP_KEY
        
        # Ahora sí, escribir en .env con valores ya generados
        cat > "$INSTALL_DIR/.env" <<EOF
APP_NAME="NexusSaaS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://$DEPLOY_IP
APP_KEY=$APP_KEY

# ─── Database ────────────────────
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=nexus_saas
DB_USERNAME=nexus_user
DB_PASSWORD=$DB_PASSWORD
DB_ROOT_PASSWORD=$DB_ROOT_PASSWORD

# ─── Cache ───────────────────────
CACHE_DRIVER=redis
CACHE_URL=redis://:$REDIS_PASSWORD@redis:6379/1
SESSION_DRIVER=redis
SESSION_CONNECTION=redis

# ─── Redis ───────────────────────
REDIS_HOST=redis
REDIS_PASSWORD=$REDIS_PASSWORD
REDIS_PORT=6379

# ... resto ...
EOF

        chmod 600 "$INSTALL_DIR/.env"
        
        # ✅ GUARDAR TODAS las credenciales
        cat > "$INSTALL_DIR/credentials.txt" <<EOF
════════════════════════════════════════════════════════════
            NexusSaaS — Credenciales
════════════════════════════════════════════════════════════
Fecha: $(date)
Servidor: $DEPLOY_IP

URL: https://$DEPLOY_IP
API: https://$DEPLOY_IP/api

MySQL:
  Database: nexus_saas
  User: nexus_user
  Password: $DB_PASSWORD
  Root Password: $DB_ROOT_PASSWORD
  Host: mysql (internal)
  Port: 3306

Redis:
  Password: $REDIS_PASSWORD
  Host: redis (internal)
  Port: 6379

Docker:
  Status: docker-compose -f docker-compose.prod.yml ps
  Logs: docker compose -f docker-compose.prod.yml logs -f
  Restart: docker compose -f docker-compose.prod.yml restart

════════════════════════════════════════════════════════════
EOF
        
        chmod 600 "$INSTALL_DIR/credentials.txt"
        FRESH_ENV=true
    fi

    log_success "Variables de entorno configuradas"
}
```

**Cambios clave:**
1. ✅ `DB_ROOT_PASSWORD` se genera como variable, NO en heredoc
2. ✅ Todas las credenciales se `export` para que docker-compose las encuentre
3. ✅ `credentials.txt` ahora incluye `DB_ROOT_PASSWORD`

---

## 🔧 FIX #2: deploy.sh - post_deploy() Conectar CON Contraseña

### ❌ CÓDIGO ACTUAL

```bash
post_deploy() {
    log_info "Ejecutando tareas post-despliegue..."
    
    cd "$INSTALL_DIR"
    
    # Esperar a que MySQL esté completamente listo
    log_info "Esperando a MySQL..."
    for i in {1..60}; do
        if $COMPOSE_CMD -f docker-compose.prod.yml exec -T mysql mysql \
            -h 127.0.0.1 -u root -e "SELECT 1" > /dev/null 2>&1; then  # ❌ SIN PASSWORD
            break
        fi
        sleep 2
    done

    # Ejecutar migraciones
    log_info "Ejecutando migraciones de base de datos..."
    $COMPOSE_CMD -f docker-compose.prod.yml exec -T php php artisan migrate --force 2>&1 || {
        log_error "Error al ejecutar migraciones"
        return 1
    }
    
    # ... resto ...
}
```

### ✅ CÓDIGO CORREGIDO

```bash
post_deploy() {
    log_info "Ejecutando tareas post-despliegue..."
    
    cd "$INSTALL_DIR"
    
    # ✅ Primero, recargar los valores de .env (que ya fueron creados)
    if [ -f "$INSTALL_DIR/.env" ]; then
        source "$INSTALL_DIR/.env" 2>/dev/null || true
    fi
    
    # Esperar a que MySQL esté completamente listo
    log_info "Esperando a MySQL..."
    for i in {1..60}; do
        # ✅ PASAR la contraseña del root
        if $COMPOSE_CMD -f docker-compose.prod.yml exec -T mysql mysql \
            -h 127.0.0.1 -u root \
            -p"${DB_ROOT_PASSWORD}" \
            -e "SELECT 1" > /dev/null 2>&1; then
            log_success "MySQL está disponible"
            break
        fi
        printf "  [%d/60] Esperando MySQL...\r" "$i"
        sleep 2
    done

    # Ejecutar migraciones
    log_info "Ejecutando migraciones de base de datos..."
    $COMPOSE_CMD -f docker-compose.prod.yml exec -T php php artisan migrate --force 2>&1 || {
        log_error "Error al ejecutar migraciones"
        $COMPOSE_CMD -f docker-compose.prod.yml logs --tail=15 php
        return 1
    }

    # Ejecutar seeders si está habilitado
    if [ "${RUN_SEEDERS:-false}" = "true" ]; then
        log_info "Ejecutando seeders..."
        $COMPOSE_CMD -f docker-compose.prod.yml exec -T php php artisan db:seed --force 2>&1 || true
    fi

    # Optimizar aplicación
    log_info "Optimizando aplicación..."
    $COMPOSE_CMD -f docker-compose.prod.yml exec -T php php artisan config:cache 2>&1 || true
    $COMPOSE_CMD -f docker-compose.prod.yml exec -T php php artisan route:cache 2>&1 || true
    $COMPOSE_CMD -f docker-compose.prod.yml exec -T php php artisan view:cache 2>&1 || true

    log_success "Post-deploy completado"
}
```

**Cambios clave:**
1. ✅ Hacer `source` del `.env` para cargar credenciales
2. ✅ Pasar la contraseña con `-p"${DB_ROOT_PASSWORD}"`
3. ✅ Mejor validación y feedback

---

## 🔧 FIX #3: docker-compose.prod.yml - Healthchecks

### ❌ Redis Healthcheck

```yaml
# Ubicación: docker-compose.prod.yml, línea ~69
redis:
    image: redis:7-alpine
    # ...
    healthcheck:
      test: [ "CMD", "redis-cli", "--raw", "incr", "ping" ]  # ❌ SIN AUTENTICACIÓN
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 20s
```

### ✅ Redis Healthcheck Corregido

```yaml
redis:
    image: redis:7-alpine
    container_name: nexus_saas_redis
    hostname: redis
    restart: unless-stopped
    command: redis-server --requirepass ${REDIS_PASSWORD:-redis_secure_password} --maxmemory 256mb --maxmemory-policy allkeys-lru
    ports:
      - "${REDIS_PORT:-6379}:6379"
    volumes:
      - redis_data:/data
    networks:
      - nexus-network
    healthcheck:
      # ✅ AGREGAR AUTENTICACIÓN
      test: [ "CMD", "redis-cli", "-a", "${REDIS_PASSWORD:-redis_secure_password}", "--raw", "incr", "ping" ]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 20s
```

---

### ❌ Nginx Healthcheck Actual

```yaml
# Ubicación: docker-compose.prod.yml, línea ~141
nginx:
    # ...
    healthcheck:
      test: [ "CMD", "curl", "-f", "http://localhost/health" ]  # ❌ Endpoint no existe
      interval: 30s
      timeout: 5s
      retries: 3
      start_period: 10s
```

### ✅ Nginx Healthcheck Corregido

```yaml
nginx:
    build:
      context: .
      dockerfile: docker/nginx/Dockerfile
    container_name: nexus_saas_nginx
    hostname: nginx
    restart: unless-stopped
    ports:
      - "${NGINX_HTTP_PORT:-80}:80"
      - "${NGINX_HTTPS_PORT:-443}:443"
    volumes:
      - ./:/app:ro
      - ./docker/nginx/default.prod.conf:/etc/nginx/conf.d/default.conf:ro
      - ./docker/nginx/ssl:/etc/nginx/ssl
    depends_on:
      # ✅ ESPERAR A QUE PHP ESTÉ HEALTHY
      php:
        condition: service_healthy
    networks:
      - nexus-network
    healthcheck:
      # ✅ CAMBIAR A VERIFICACIÓN MÁS ROBUSTA
      # Opción 1: Probar conectividad TCP al puerto 80
      test: [ "CMD", "nc", "-zv", "127.0.0.1", "80" ]
      
      # Opción 2: Si nc no está disponible, usar curl con SSL inseguro
      # test: [ "CMD", "curl", "-sf", "--insecure", "https://localhost" ]
      
      interval: 30s
      timeout: 5s
      retries: 3
      start_period: 30s
```

**Cambios clave:**
1. ✅ Redis: Agregar `-a "${REDIS_PASSWORD}"` al healthcheck
2. ✅ Nginx: Agregar `depends_on: php: condition: service_healthy`
3. ✅ Nginx: Cambiar healthcheck a verificación más robusta

---

## 🔧 FIX #4: docker/php/entrypoint.sh - Reemplazar nc

### ❌ CÓDIGO ACTUAL

```bash
#!/bin/bash
set -e

# Esperar a que MySQL esté listo
echo "Esperando a MySQL..."
while ! nc -z "$DB_HOST" "$DB_PORT" 2>/dev/null; do  # ❌ nc podría no existir
    sleep 2
done
echo "✓ MySQL está listo"

# ... resto ...
```

### ✅ CÓDIGO CORREGIDO

```bash
#!/bin/bash
set -e

# Función para esperar a MySQL
wait_for_mysql() {
    local host="${DB_HOST:-mysql}"
    local port="${DB_PORT:-3306}"
    local timeout=180
    local elapsed=0
    
    echo "Esperando a MySQL en $host:$port (max ${timeout}s)..."
    
    while [ $elapsed -lt $timeout ]; do
        # ✅ Opción 1: Usar bash (más portable, sin dependencies)
        # timeout 3 bash -c ">/dev/tcp/$host/$port" 2>/dev/null && return 0
        
        # ✅ Opción 2: Usar mysqladmin (requiere MySQL client)
        if command -v mysqladmin &>/dev/null; then
            if mysqladmin ping -h"$host" -P"$port" --silent 2>/dev/null; then
                echo "✓ MySQL está listo (${elapsed}s)"
                return 0
            fi
        # ✅ Opción 3: Fallback a bash TCP
        elif timeout 3 bash -c ">/dev/tcp/$host/$port" 2>/dev/null; then
            echo "✓ MySQL está accesible (${elapsed}s)"
            return 0
        fi
        
        printf "  [%3ds/%ds] Esperando...\r" "$elapsed" "$timeout"
        sleep 2
        elapsed=$((elapsed + 2))
    done
    
    echo ""
    echo "❌ ERROR: MySQL no disponible después de ${timeout}s"
    return 1
}

# Validar variables requeridas
if [ -z "$DB_HOST" ] || [ -z "$DB_DATABASE" ]; then
    echo "❌ ERROR: Faltan variables de entorno DB_HOST o DB_DATABASE"
    exit 1
fi

# Esperar a MySQL
wait_for_mysql || exit 1

# Ejecutar migraciones (solo en primer deploy o si hay cambios)
if [ "$SKIP_MIGRATIONS" != "true" ]; then
    echo "Ejecutando migraciones..."
    php artisan migrate --force || {
        echo "❌ ERROR: Fallo en migraciones"
        exit 1
    }
    echo "✓ Migraciones completadas"
fi

# Ejecutar seeders solo si está habilitado
if [ "$RUN_SEEDERS" = "true" ]; then
    echo "Ejecutando seeders..."
    php artisan db:seed --force || true
    echo "✓ Seeders completados"
fi

# Optimizar aplicación
echo "Optimizando aplicación..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✓ Aplicación optimizada"

# Limpiar caché
php artisan cache:clear
php artisan queue:flush

echo "✓ NexusSaaS listo para recibir solicitudes"

# Ejecutar comando pasado como argumento o php-fpm
exec "$@"
```

**Cambios clave:**
1. ✅ Función `wait_for_mysql()` sin dependencia de `nc`
2. ✅ Validar variables requeridas
3. ✅ Mejor manejo de timeouts
4. ✅ Fallback a bash TCP si mysqladmin no disponible

---

## 🔧 FIX #5: deploy.sh - configure_firewall()

### ❌ CÓDIGO ACTUAL

```bash
configure_firewall() {
    log_info "Configurando firewall..."

    local PORTS="22/tcp 80/tcp 443/tcp 3306/tcp 6379/tcp"  # ❌ Expone MySQL y Redis

    if command -v ufw &>/dev/null; then
        for port in $PORTS; do
            ufw allow "$port" 2>/dev/null || true
        done
        ufw --force enable 2>/dev/null || true
        log_success "Firewall UFW configurado"
    elif command -v firewall-cmd &>/dev/null; then
        for port in $PORTS; do
            firewall-cmd --permanent --add-port="$port" 2>/dev/null || true
        done
        firewall-cmd --reload 2>/dev/null || true
        log_success "Firewall firewalld configurado"
    else
        log_warning "No se detectó firewall. Configure puertos manualmente."
    fi
}
```

### ✅ CÓDIGO CORREGIDO

```bash
configure_firewall() {
    log_info "Configurando firewall..."

    # ✅ Solo los puertos públicos
    local PUBLIC_PORTS="22/tcp 80/tcp 443/tcp"
    
    # Puertos internos (NO deben estar en firewall público)
    # MySQL y Redis solo accesibles dentro de Docker network
    local INTERNAL_PORTS="3306/tcp 6379/tcp"

    if command -v ufw &>/dev/null; then
        # Permitir puertos públicos
        for port in $PUBLIC_PORTS; do
            log_info "  Permitiendo puerto público: $port"
            ufw allow "$port" 2>/dev/null || true
        done
        
        # Bloquear puertos internos (si estaban abiertos)
        for port in $INTERNAL_PORTS; do
            log_info "  Bloqueando puerto interno: $port (solo accessible en Docker)"
            ufw deny "$port" 2>/dev/null || true
        done
        
        ufw --force enable 2>/dev/null || true
        log_success "Firewall UFW configurado correctamente"
        
    elif command -v firewall-cmd &>/dev/null; then
        # Permitir puertos públicos
        for port in $PUBLIC_PORTS; do
            log_info "  Permitiendo puerto público: $port"
            firewall-cmd --permanent --add-port="$port" 2>/dev/null || true
        done
        
        # Bloquear puertos internos
        for port in $INTERNAL_PORTS; do
            log_info "  Bloqueando puerto interno: $port"
            firewall-cmd --permanent --remove-port="$port" 2>/dev/null || true
        done
        
        firewall-cmd --reload 2>/dev/null || true
        log_success "Firewall firewalld configurado correctamente"
        
    else
        log_warning "No se detectó firewall (ufw o firewalld)"
        log_warning "Configure manualmente para SOLO permitir:"
        log_warning "  - Puerto 22 (SSH)"
        log_warning "  - Puerto 80 (HTTP)"
        log_warning "  - Puerto 443 (HTTPS)"
        log_warning ""
        log_warning "NO permitir puertos internos:"
        log_warning "  - Puerto 3306 (MySQL) - Solo accesible en Docker"
        log_warning "  - Puerto 6379 (Redis) - Solo accesible en Docker"
    fi
}
```

**Cambios clave:**
1. ✅ Solo permitir puertos públicos (22, 80, 443)
2. ✅ Bloquear explícitamente 3306 y 6379
3. ✅ Mejor documentación en warnings

---

## 📋 CHECKLIST DE VALIDACIÓN

Después de aplicar todos los fixes:

```bash
# 1. Validar .env generado correctamente
cat /opt/nexus-saas/.env | grep -E "^DB_(ROOT_)?PASSWORD|^REDIS_PASSWORD|^APP_KEY"

# Debe mostrar valores aleatorios, NO strings como "$(openssl...)"
# Ejemplo correcto:
# DB_PASSWORD=abc123xyz456abc123xyz456 
# REDIS_PASSWORD=xyz456abc123xyz456abc12
# DB_ROOT_PASSWORD=xyz456abc123xyz456abc12
# APP_KEY=base64:xxxxx...

# 2. Validar credenciales.txt tiene todas las credenciales
cat /opt/nexus-saas/credentials.txt | grep -i "root\|password"

# Debe incluir DB_ROOT_PASSWORD

# 3. Validar MySQL inicia correctamente
docker compose -f /opt/nexus-saas/docker-compose.prod.yml ps mysql
# Status debe ser "Up"
# Health debe ser "healthy" después de ~30s

# 4. Validar Redis con auth
docker compose -f /opt/nexus-saas/docker-compose.prod.yml exec redis \
    redis-cli -a $(grep REDIS_PASSWORD /opt/nexus-saas/.env | cut -d= -f2) ping
# Debe retornar: PONG

# 5. Validar migraciones ejecutadas
docker compose -f /opt/nexus-saas/docker-compose.prod.yml exec -T php \
    php artisan migrate:status | head -5
# Debe mostrar tabla 'N' (migración ejecutada), no 'Pending'

# 6. Validar aplicación respondiendo
curl -sk https://localhost/health
# Debe retornar HTTP 200 OK

# 7. Validar firewall
sudo ufw status | grep 3306
# NO debe aparecer "3306" en lista permitida

sudo ufw status | grep 6379
# NO debe aparecer "6379" en lista permitida
```

---

## 🚀 TOMAR ACCIÓN

Estos fixes son **CRÍTICOS** para que el despliegue funcione. Sin ellos:
- ❌ 100% de despliegues fallará
- ❌ Usuarios no podrán iniciar la aplicación
- ❌ Base de datos no será accesible

**Recomendación:** Aplicar todos los fixes antes de hacer el repositorio público.
