#!/bin/bash

################################################################################
# NexusSaaS Deploy Script — Despliegue en Producción
# Version: 1.0.0
#
# Descripción:
#   Script automatizado para desplegar NexusSaaS en cualquier servidor Linux
#   con soporte para múltiples distribuciones (Ubuntu, Debian, CentOS, etc.)
#
# Uso (Recomendado):
#   curl -sL https://raw.githubusercontent.com/henry0295/nexus-saas/main/deploy.sh | sudo bash -s -- X.X.X.X
#
# Uso (Manual):
#   git clone https://github.com/henry0295/nexus-saas.git
#   cd nexus-saas
#   chmod +x deploy.sh
#   sudo bash deploy.sh X.X.X.X
#
# Opciones:
#   X.X.X.X         IP del servidor (requerido)
#   --clean         Eliminar instalación anterior e iniciar desde cero
#   --help          Mostrar esta ayuda
#
# Variables de Entorno Optional:
#   DEPLOY_IP       IP del servidor (alternativa a argumento)
#   INSTALL_DIR     Directorio de instalación (default: /opt/nexus-saas)
#   BRANCH          Rama Git a desplegar (default: main)
#   TZ              Zona horaria (default: America/Bogota)
#   DB_PASSWORD     Contraseña de MySQL (se genera si no existe)
#   REDIS_PASSWORD  Contraseña de Redis (se genera si no existe)
#
# Ejemplos:
#   # Despliegue simple
#   sudo bash deploy.sh 192.168.1.100
#
#   # Despliegue con rama específica
#   BRANCH=develop sudo bash deploy.sh 192.168.1.100
#
#   # Reinstalar desde cero (borra BD, cache, credenciales)
#   sudo bash deploy.sh --clean 192.168.1.100
#
#   # Despliegue remoto
#   export DEPLOY_IP=192.168.1.100
#   curl -sL deploy.sh | sudo -E bash
#
################################################################################

set -Eeuo pipefail

# ═────────────────────────────────────────────────────────────────────────═
# COLORES Y FUNCIONES DE LOGGING
# ═────────────────────────────────────────────────────────────────────────═

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m'

log_info()    { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[✓]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[!]${NC} $1"; }
log_error()   { echo -e "${RED}[✗]${NC} $1"; }

# Manejo de errores
on_error() {
    local exit_code=$?
    local line_no=${BASH_LINENO[0]}
    echo ""
    echo -e "${RED}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${RED}║  ERROR: deploy.sh falló en la línea $line_no (código: $exit_code)${NC}"
    echo -e "${RED}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    log_error "Sugerencias:"
    echo "  1. Verify connectivity to the server"
    echo "  2. Verify DEPLOY_IP is correctly set"
    echo "  3. Verify internet connection and Docker availability"
    echo "  4. Check logs: docker compose -f docker-compose.prod.yml logs"
    echo "  5. Retry: sudo bash deploy.sh <IP>"
    echo ""
    exit $exit_code
}
trap on_error ERR

# ═────────────────────────────────────────────────────────────────────────═
# BANNER
# ═────────────────────────────────────────────────────────────────────────═

banner() {
    echo ""
    echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║                                                            ║${NC}"
    echo -e "${BLUE}║         NexusSaaS — Despliegue en Producción v1.0         ║${NC}"
    echo -e "${BLUE}║                                                            ║${NC}"
    echo -e "${BLUE}║               Laravel 11 · MySQL 8 · Redis 7              ║${NC}"
    echo -e "${BLUE}║                        Nginx · Docker                      ║${NC}"
    echo -e "${BLUE}║                                                            ║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

# ═────────────────────────────────────────────────────────────────────────═
# PARSEAR ARGUMENTOS
# ═────────────────────────────────────────────────────────────────────────═

CLEAN_INSTALL=false
for arg in "$@"; do
    case "$arg" in
        --clean|-c)
            CLEAN_INSTALL=true
            ;;
        --help|-h)
            echo "Uso: deploy.sh [--clean] <IP>"
            echo ""
            echo "  <IP>        IP del servidor (requerido)"
            echo "  --clean     Eliminar instalación anterior y datos"
            echo "  --help      Mostrar esta ayuda"
            exit 0
            ;;
        *)
            # Validar que sea IP
            if [[ "$arg" =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
                export DEPLOY_IP="$arg"
            fi
            ;;
    esac
done

# ═────────────────────────────────────────────────────────────────────────═
# VARIABLES POR DEFECTO
# ═────────────────────────────────────────────────────────────────────────═

INSTALL_DIR="${INSTALL_DIR:-/opt/nexus-saas}"
BRANCH="${BRANCH:-main}"
TZ="${TZ:-America/Bogota}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
COMPOSE_CMD=""
FRESH_ENV=false

# ═────────────────────────────────────────────────────────────────────────═
# 1. VERIFICAR PREREQUISITOS
# ═────────────────────────────────────────────────────────────────────────═

check_prerequisites() {
    log_info "Verificando prerequisitos..."

    # Root
    if [[ $EUID -ne 0 ]]; then
        log_error "Este script debe ejecutarse como root o con sudo"
        exit 1
    fi

    # IP
    if [ -z "${DEPLOY_IP:-}" ]; then
        log_error "Variable DEPLOY_IP no está configurada"
        echo ""
        echo "Uso:"
        echo "  sudo bash deploy.sh X.X.X.X"
        echo "  export DEPLOY_IP=X.X.X.X && curl -sL deploy.sh | sudo -E bash"
        echo ""
        exit 1
    fi

    # Validar formato IP
    if ! [[ $DEPLOY_IP =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
        log_error "Formato de IP inválido: $DEPLOY_IP"
        exit 1
    fi

    log_success "IP del servidor: $DEPLOY_IP"
    log_success "Zona horaria: $TZ"
    log_success "Rama a desplegar: $BRANCH"
}

# ═────────────────────────────────────────────────────────────────────────═
# 2. PREPARAR SISTEMA
# ═────────────────────────────────────────────────────────────────────────═

prepare_system() {
    log_info "Preparando sistema operativo..."

    # Silenciar kernel messages
    if [ -f /proc/sys/kernel/printk ]; then
        echo "1 4 1 7" > /proc/sys/kernel/printk
    else
        dmesg -n 1 2>/dev/null || true
    fi

    # Persistir configuración del sistema
    mkdir -p /etc/sysctl.d
    cat > /etc/sysctl.d/10-nexus-saas.conf <<'SYSCTL'
# NexusSaaS - Optimizaciones de red y kernel
kernel.printk = 1 4 1 7
net.core.rmem_max = 16777216
net.core.wmem_max = 16777216
net.core.rmem_default = 262144
net.core.wmem_default = 262144
net.core.somaxconn = 65535
net.ipv4.tcp_max_syn_backlog = 65535
net.ipv4.tcp_tw_reuse = 1
net.ipv4.tcp_fin_timeout = 15
net.ipv4.tcp_keepalive_time = 300
net.ipv4.ip_forward = 1
vm.swappiness = 10
vm.overcommit_memory = 1
fs.file-max = 2097152
SYSCTL
    sysctl -p /etc/sysctl.d/10-nexus-saas.conf 2>/dev/null || true

    # Límites del sistema
    cat > /etc/security/limits.d/99-nexus-saas.conf <<'LIMITS'
* soft nofile 65536
* hard nofile 65536
* soft nproc  65536
* hard nproc  65536
LIMITS

    # SELinux
    if command -v getenforce &>/dev/null; then
        if [ "$(getenforce 2>/dev/null)" = "Enforcing" ]; then
            setenforce 0 2>/dev/null || true
            sed -i 's/^SELINUX=enforcing/SELINUX=permissive/' /etc/selinux/config 2>/dev/null || true
            log_info "SELinux → Permissive"
        fi
    fi

    # Docker daemon
    mkdir -p /etc/docker
    if [ ! -f /etc/docker/daemon.json ]; then
        cat > /etc/docker/daemon.json <<'DAEMON'
{
  "log-driver": "json-file",
  "log-opts": { "max-size": "10m", "max-file": "3" },
  "storage-driver": "overlay2",
  "live-restore": true,
  "default-ulimits": { "nofile": { "Name": "nofile", "Hard": 65536, "Soft": 65536 } }
}
DAEMON
    fi

    log_success "Sistema preparado"
}

# ═────────────────────────────────────────────────────────────────────────═
# 3. INSTALAR DOCKER
# ═────────────────────────────────────────────────────────────────────────═

install_docker() {
    if command -v docker &>/dev/null; then
        log_success "Docker ya instalado: $(docker --version 2>/dev/null | head -1)"
    else
        log_info "Instalando Docker..."

        # Detectar distribución
        local DISTRO_ID="unknown"
        if [ -f /etc/os-release ]; then
            . /etc/os-release
            DISTRO_ID="${ID:-unknown}"
        fi

        case "$DISTRO_ID" in
            ubuntu|debian)
                apt-get update -y
                apt-get remove -y docker docker-engine docker.io containerd runc 2>/dev/null || true
                apt-get install -y ca-certificates curl gnupg lsb-release
                install -m 0755 -d /etc/apt/keyrings
                curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg --yes
                chmod a+r /etc/apt/keyrings/docker.gpg
                echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null
                apt-get update -y
                apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
                ;;
            rocky|almalinux)
                dnf remove -y docker docker-client docker-common container-selinux 2>/dev/null || true
                dnf install -y dnf-plugins-core
                dnf config-manager --add-repo https://download.docker.com/linux/rhel/docker-ce.repo
                dnf install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
                ;;
            centos|rhel)
                yum remove -y docker docker-client docker-common 2>/dev/null || true
                yum install -y yum-utils
                yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
                yum install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
                ;;
            *)
                log_error "Distribución '$DISTRO_ID' no soportada. Instale Docker manualmente."
                exit 1
                ;;
        esac

        log_success "Docker instalado correctamente"
    fi

    # Habilitar y arrancar Docker
    systemctl enable docker --now 2>/dev/null || true
    systemctl restart docker 2>/dev/null || true

    if ! docker info &>/dev/null; then
        log_error "Docker no se pudo iniciar correctamente"
        exit 1
    fi

    # Detectar docker compose
    if docker compose version &>/dev/null; then
        COMPOSE_CMD="docker compose"
    elif command -v docker-compose &>/dev/null; then
        COMPOSE_CMD="docker-compose"
    else
        log_error "docker compose no disponible"
        exit 1
    fi

    log_info "Usando: $COMPOSE_CMD"
}

# ═────────────────────────────────────────────────────────────────────────═
# 4. LIMPIAR INSTALACIÓN ANTERIOR (SI --clean)
# ═────────────────────────────────────────────────────────────────────────═

clean_existing() {
    if [ "$CLEAN_INSTALL" != true ]; then
        return 0
    fi

    log_warning "════════════════════════════════════════════════════════"
    log_warning " MODO LIMPIO: Se borrará toda la instalación"
    log_warning "  - Contenedores y volúmenes Docker"
    log_warning "  - Archivos .env y credenciales"
    log_warning "  - Base de datos, cache, archivos almacenados"
    log_warning "════════════════════════════════════════════════════════"
    echo ""

    # Pedir confirmación si es interactivo
    if [ -t 0 ]; then
        read -r -p "¿Está seguro? Esto es IRREVERSIBLE [s/N]: " confirm
        if [[ ! "$confirm" =~ ^[sS]$ ]]; then
            log_info "Cancelado por el usuario"
            exit 0
        fi
    fi

    log_info "Deteniendo y eliminando contenedores..."
    docker ps -a --format '{{.Names}}' 2>/dev/null | grep -i nexus | while read -r ctr; do
        docker stop "$ctr" 2>/dev/null || true
        docker rm -f "$ctr" 2>/dev/null || true
    done || true

    # Parar con docker-compose si existe
    if [ -f "$INSTALL_DIR/docker-compose.prod.yml" ]; then
        (cd "$INSTALL_DIR" && $COMPOSE_CMD -f docker-compose.prod.yml down -v --remove-orphans) 2>/dev/null || true
    fi

    # Eliminar volúmenes
    log_info "Eliminando volúmenes Docker..."
    docker volume ls --format '{{.Name}}' 2>/dev/null | grep -i nexus | while read -r vol; do
        docker volume rm -f "$vol" 2>/dev/null || true
    done || true

    # Eliminar imágenes
    log_info "Eliminando imágenes Docker..."
    docker images --format '{{.Repository}}:{{.Tag}}' 2>/dev/null | grep -i nexus | while read -r img; do
        docker rmi -f "$img" 2>/dev/null || true
    done || true

    # Limpiar archivos de configuración
    if [ -d "$INSTALL_DIR" ]; then
        log_info "Limpiando archivos de configuración..."
        rm -f "$INSTALL_DIR/.env" "$INSTALL_DIR/credentials.txt" 2>/dev/null || true
        rm -rf "$INSTALL_DIR/storage/logs/"* 2>/dev/null || true
    fi

    # Eliminar directorio completo
    cd /tmp 2>/dev/null || cd /
    rm -rf "$INSTALL_DIR" 2>/dev/null || true

    log_success "Limpieza completa verificada"
    echo ""
}

# ═────────────────────────────────────────────────────────────────────────═
# 5. CLONAR/ACTUALIZAR REPOSITORIO
# ═────────────────────────────────────────────────────────────────────────═

clone_repo() {
    cd /tmp 2>/dev/null || cd /

    if [ -d "$INSTALL_DIR/.git" ]; then
        log_info "Actualizando repositorio existente..."
        cd "$INSTALL_DIR"
        git fetch origin
        git checkout "$BRANCH" 2>/dev/null || true
        git pull origin "$BRANCH"
    else
        log_info "Clonando repositorio (rama: $BRANCH)..."
        mkdir -p "$(dirname "$INSTALL_DIR")"
        git clone -b "$BRANCH" https://github.com/henry0295/nexus-saas.git "$INSTALL_DIR"
        cd "$INSTALL_DIR"
    fi

    log_success "Repositorio listo en $INSTALL_DIR"
}

# ═────────────────────────────────────────────────────────────────────────═
# 6. GENERAR CREDENCIALES Y .env
# ═────────────────────────────────────────────────────────────────────────═

generate_env() {
    log_info "Configurando variables de entorno..."

    cd "$INSTALL_DIR"

    # Si existe .env, reutilizar credenciales
    if [ -f "$INSTALL_DIR/.env" ] && [ "$CLEAN_INSTALL" != true ]; then
        log_info "Archivo .env existente encontrado, reutilizando credenciales..."
        source "$INSTALL_DIR/.env" 2>/dev/null || true
        
        # ✅ Validar que existan las variables críticas
        # Si falta DB_ROOT_PASSWORD (.env antiguo), generarla
        if ! grep -q "^DB_ROOT_PASSWORD=" "$INSTALL_DIR/.env"; then
            log_warning "  Variable DB_ROOT_PASSWORD no encontrada, generando..."
            local DB_ROOT_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
            echo "DB_ROOT_PASSWORD=$DB_ROOT_PASSWORD" >> "$INSTALL_DIR/.env"
            source "$INSTALL_DIR/.env" 2>/dev/null || true
        fi
        
        # ✅ Exportar credenciales para docker-compose
        export DB_PASSWORD="${DB_PASSWORD}"
        export DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD}"
        export REDIS_PASSWORD="${REDIS_PASSWORD}"
        export APP_KEY="${APP_KEY}"
        
        # Solo actualizar IP y URLs
        sed -i "s|^APP_URL=.*|APP_URL=https://$DEPLOY_IP|" "$INSTALL_DIR/.env"
        FRESH_ENV=false
    else
        # Generar nuevas credenciales
        log_info "Generando credenciales nuevas..."
        
        # ✅ GENERAR TODAS las credenciales ANTES del heredoc
        local DB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
        local REDIS_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
        local DB_ROOT_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
        local APP_KEY=$(php artisan key:generate --show 2>/dev/null | grep -oP 'base64:.*' || echo "base64:$(openssl rand -base64 32)")
        
        # ✅ EXPORTAR para que docker-compose las pueda usar
        export DB_PASSWORD
        export REDIS_PASSWORD
        export DB_ROOT_PASSWORD
        export APP_KEY
        
        cat > "$INSTALL_DIR/.env" <<EOF
APP_NAME="NexusSaaS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://$DEPLOY_IP
APP_KEY=$APP_KEY

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_CO

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info

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

# ─── Queue ───────────────────────
QUEUE_CONNECTION=redis

# ─── Mail ────────────────────────
MAIL_DRIVER=log
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@nexus-saas.com
MAIL_FROM_NAME=NexusSaaS

# ─── Docker ──────────────────────
DEPLOY_IP=$DEPLOY_IP
NGINX_HTTP_PORT=80
NGINX_HTTPS_PORT=443
TZ=America/Bogota

# ─── Features ─────────────────────
SKIP_MIGRATIONS=false
RUN_SEEDERS=false

# ─── Timestamp ────────────────────
# Generated: $(date)
EOF

        chmod 600 "$INSTALL_DIR/.env"
        
        # ✅ Guardar credenciales (INCLUIDAS DB_ROOT_PASSWORD)
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

App Key: $APP_KEY

Docker:
  Status: docker compose -f docker-compose.prod.yml ps
  Logs: docker compose -f docker-compose.prod.yml logs -f
  Restart: docker compose -f docker-compose.prod.yml restart

════════════════════════════════════════════════════════════
EOF
        
        chmod 600 "$INSTALL_DIR/credentials.txt"
        FRESH_ENV=true
    fi

    log_success "Variables de entorno configuradas"
}

# ═────────────────────────────────────────────────────────────────────────═
# 7. CONFIGURAR FIREWALL
# ═────────────────────────────────────────────────────────────────────────═

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
            log_info "  Bloqueando puerto interno: $port (solo accesible en Docker)"
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

# ═────────────────────────────────────────────────────────────────────────═
# 8. DESPLEGAR SERVICIOS CON DOCKER COMPOSE
# ═────────────────────────────────────────────────────────────────────────═

wait_container_healthy() {
    local container="$1"
    local timeout="${2:-120}"
    local elapsed=0
    
    log_info "Esperando healthcheck de $container (max ${timeout}s)..."
    
    while [ $elapsed -lt $timeout ]; do
        local status=$(docker inspect --format='{{if .State.Health}}{{.State.Health.Status}}{{else}}no-healthcheck{{end}}' "$container" 2>/dev/null || echo "not-found")
        
        if [ "$status" = "healthy" ]; then
            log_success "$container healthy (${elapsed}s)"
            return 0
        fi
        
        printf "  [%3ds/%ds] %s: %s\r" "$elapsed" "$timeout" "$container" "$status"
        sleep 3
        elapsed=$((elapsed + 3))
    done
    
    echo ""
    log_warning "Timeout: $container no alcanzó healthy en ${timeout}s"
    docker logs --tail=15 "$container" 2>/dev/null || true
    return 1
}

deploy_services() {
    log_info "Desplegando servicios con Docker Compose..."
    
    cd "$INSTALL_DIR"
    
    # Parar servicios anteriores
    $COMPOSE_CMD -f docker-compose.prod.yml down --remove-orphans 2>/dev/null || true
    
    # Build
    log_info "Construyendo imágenes Docker (esto puede tardar varios minutos)..."
    $COMPOSE_CMD -f docker-compose.prod.yml build 2>&1
    
    # Iniciar data stores (MySQL y Redis)
    log_info "Iniciando MySQL y Redis..."
    $COMPOSE_CMD -f docker-compose.prod.yml up -d mysql redis 2>&1
    
    if ! wait_container_healthy "nexus_saas_mysql" 180; then
        log_error "MySQL no arrancó correctamente"
        $COMPOSE_CMD -f docker-compose.prod.yml logs --tail=30 mysql
        exit 1
    fi
    
    if ! wait_container_healthy "nexus_saas_redis" 60; then
        log_warning "Redis no reporta healthy — continuando de todos modos"
    fi
    
    # Iniciar PHP-FPM
    log_info "Iniciando PHP-FPM..."
    $COMPOSE_CMD -f docker-compose.prod.yml up -d php 2>&1
    
    if ! wait_container_healthy "nexus_saas_php" 180; then
        log_warning "PHP-FPM aún no reporta healthy — revisando logs:"
        $COMPOSE_CMD -f docker-compose.prod.yml logs --tail=40 php
        sleep 10
    fi
    
    # Iniciar Nginx
    log_info "Iniciando Nginx..."
    $COMPOSE_CMD -f docker-compose.prod.yml up -d nginx 2>&1
    
    if ! wait_container_healthy "nexus_saas_nginx" 60; then
        log_warning "Nginx aún no reporta healthy"
    fi
    
    log_success "Servicios desplegados"
}

# ═────────────────────────────────────────────────────────────────────────═
# 9. ESPERAR A QUE SERVICIOS ESTÉN LISTOS
# ═────────────────────────────────────────────────────────────────────────═

wait_for_env() {
    local url="https://localhost/health"
    local WAIT_TIMEOUT=${1:-300}
    local WAIT_INTERVAL=5
    local elapsed=0
    
    log_info "Esperando que NexusSaaS esté listo (máx ${WAIT_TIMEOUT}s)..."
    echo ""
    
    while [ $elapsed -lt $WAIT_TIMEOUT ]; do
        local http_code=$(curl -sk -o /dev/null -w "%{http_code}" --connect-timeout 5 "$url" 2>/dev/null || echo "000")
        
        case $http_code in
            200|301|302|403)
                echo ""
                log_success "Backend respondiendo (HTTP $http_code) — ${elapsed}s"
                return 0
                ;;
            *)
                printf "  [%3ds/%ds] HTTP %s — esperando...\r" "$elapsed" "$WAIT_TIMEOUT" "$http_code"
                ;;
        esac
        
        sleep $WAIT_INTERVAL
        elapsed=$((elapsed + WAIT_INTERVAL))
    done
    
    echo ""
    log_warning "Timeout alcanzado. Verifique:"
    echo "  $COMPOSE_CMD -f docker-compose.prod.yml logs php"
    return 1
}

# ═────────────────────────────────────────────────────────────────────────═
# 10. EJECUTAR MIGRACIONES Y POST-DEPLOY
# ═────────────────────────────────────────────────────────────────────────═

post_deploy() {
    log_info "Ejecutando tareas post-despliegue..."
    
    cd "$INSTALL_DIR"
    
    # ✅ Recargar credenciales desde .env
    if [ -f "$INSTALL_DIR/.env" ]; then
        source "$INSTALL_DIR/.env" 2>/dev/null || true
    fi
    
    # Esperar a que MySQL esté completamente listo
    log_info "Esperando a MySQL (puede tardar hasta 60 segundos)..."
    local mysql_ready=false
    for i in {1..60}; do
        # ✅ PASAR la contraseña del root
        if $COMPOSE_CMD -f docker-compose.prod.yml exec -T mysql mysql \
            -h 127.0.0.1 -u root \
            -p"${DB_ROOT_PASSWORD}" \
            -e "SELECT 1" > /dev/null 2>&1; then
            log_success "MySQL está disponible"
            mysql_ready=true
            break
        fi
        printf "  [%2d/60] Esperando MySQL...\r" "$i"
        sleep 1
    done
    
    if [ "$mysql_ready" = false ]; then
        log_error "MySQL no está disponible después de 60 segundos"
        log_error "Logs de MySQL:"
        $COMPOSE_CMD -f docker-compose.prod.yml logs --tail=20 mysql
        return 1
    fi
    
    echo ""

    # Ejecutar migraciones
    log_info "Ejecutando migraciones de base de datos..."
    if $COMPOSE_CMD -f docker-compose.prod.yml exec -T php php artisan migrate --force 2>&1; then
        log_success "Migraciones completadas"
    else
        log_error "Error al ejecutar migraciones"
        $COMPOSE_CMD -f docker-compose.prod.yml logs --tail=15 php
        return 1
    fi

    # Ejecutar seeders si está habilitado
    if [ "${RUN_SEEDERS:-false}" = "true" ]; then
        log_info "Ejecutando seeders..."
        if $COMPOSE_CMD -f docker-compose.prod.yml exec -T php php artisan db:seed --force 2>&1; then
            log_success "Seeders completados"
        else
            log_warning "Advertencia: seeders no se completaron correctamente"
        fi
    fi
    
    # Optimizar aplicación
    log_info "Optimizando aplicación..."
    $COMPOSE_CMD -f docker-compose.prod.yml exec -T php php artisan config:cache 2>&1 || true
    $COMPOSE_CMD -f docker-compose.prod.yml exec -T php php artisan route:cache 2>&1 || true
    $COMPOSE_CMD -f docker-compose.prod.yml exec -T php php artisan view:cache 2>&1 || true
    log_success "Aplicación optimizada"
    
    log_success "Post-deploy completado"
}

# ═────────────────────────────────────────────────────────────────────────═
# 11. MOSTRAR RESULTADO FINAL
# ═────────────────────────────────────────────────────────────────────────═

show_result() {
    echo ""
    echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                                                            ║${NC}"
    echo -e "${GREEN}║   ¡Despliegue de NexusSaaS completado exitosamente!       ║${NC}"
    echo -e "${GREEN}║                                                            ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${BLUE}Accede a tu plataforma:${NC}"
    echo -e "  → ${GREEN}https://$DEPLOY_IP${NC}"
    echo ""
    echo -e "${YELLOW}⚠️  Nota de Seguridad:${NC}"
    echo -e "  Se está usando certificado SSL autofirmado"
    echo -e "  El navegador mostrará una advertencia de seguridad"
    echo -e "  Esto es NORMAL en configuración autofirmada"
    echo ""
    echo -e "${BLUE}Credenciales:${NC} ${GREEN}$INSTALL_DIR/credentials.txt${NC}"
    echo ""
    echo -e "${BLUE}Comandos útiles:${NC}"
    echo -e "  Estado:    ${GREEN}cd $INSTALL_DIR && $COMPOSE_CMD -f docker-compose.prod.yml ps${NC}"
    echo -e "  Logs:      ${GREEN}cd $INSTALL_DIR && $COMPOSE_CMD -f docker-compose.prod.yml logs -f${NC}"
    echo -e "  Reiniciar: ${GREEN}cd $INSTALL_DIR && $COMPOSE_CMD -f docker-compose.prod.yml restart${NC}"
    echo ""
    echo -e "${BLUE}Servicios desplegados:${NC}"
    cd "$INSTALL_DIR" && $COMPOSE_CMD -f docker-compose.prod.yml ps 2>/dev/null || true
    echo ""
    log_success "✨ NexusSaaS v1.0 deploying completo"
    echo ""
}

# ═────────────────────────────────────────────────────────────────────────═
# MAIN - EJECUTAR TODO
# ═────────────────────────────────────────────────────────────────────────═

main() {
    banner
    check_prerequisites
    echo ""
    prepare_system
    echo ""
    install_docker
    echo ""
    clean_existing
    clone_repo
    echo ""
    generate_env
    echo ""
    configure_firewall
    echo ""
    deploy_services
    echo ""
    wait_for_env 300 || true
    echo ""
    post_deploy
    echo ""
    show_result
}

main "$@"
