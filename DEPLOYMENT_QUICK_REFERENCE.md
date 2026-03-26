# 🔴 NexusSaaS Deployment Issues — Quick Reference Matrix

## 📊 Matriz de Problemas

| # | Severidad | Componente | Problema | Archivo | Línea | Síntoma | Fix |
|----|-----------|-----------|----------|---------|-------|---------|-----|
| 1️⃣ | 🔴 CRÍTICO | deploy.sh | `DB_ROOT_PASSWORD` es string literal | deploy.sh | 468 | MySQL: "Access denied" | Generar en var antes del heredoc |
| 2️⃣ | 🔴 CRÍTICO | deploy.sh | Variables no exportadas | deploy.sh | 431 | docker-compose sin variables | Agregar `export` |
| 3️⃣ | 🔴 CRÍTICO | deploy.sh | post_deploy sin contraseña | deploy.sh | 740 | Migraciones fallan | Agregar `-p"${DB_ROOT_PASSWORD}"` |
| 4️⃣ | 🔴 CRÍTICO | PHP | `nc` no instalado | docker/php/entrypoint.sh | 4 | PHP container muere cíclicamente | Reemplazar con `bash -c '>/dev/tcp/'` |
| 5️⃣ | 🟠 ALTO | Nginx | Healthcheck endpoint inválido | docker-compose.prod.yml | 141 | Nginx nunca "healthy" | Cambiar a verificación TCP |
| 6️⃣ | 🟠 ALTO | Redis | Healthcheck sin auth | docker-compose.prod.yml | 69 | Redis: "NOAUTH required" | Agregar `-a "${REDIS_PASSWORD}"` |
| 7️⃣ | 🟠 ALTO | Docker | depends_on sin condition | docker-compose.prod.yml | 131 | Nginx conecta a PHP no listo | Agregar `condition: service_healthy` |
| 8️⃣ | 🟡 MEDIO | Firewall | Puertos internos expuestos | deploy.sh | 580 | MySQL/Redis accesibles públicamente | Remover 3306, 6379 |
| 9️⃣ | 🟡 MEDIO | deploy.sh | DB_ROOT_PASSWORD no guardado | deploy.sh | 500 | No hay credencial para usuarios | Incluir en credentials.txt |
| 🔟 | 🟡 MEDIO | docs | Versión MySQL inconsistente | docker-compose.prod.yml | 9 | Confusión: MySQL 8 vs MariaDB 11 | Actualizar comentario |
| 1️⃣1️⃣ | 🟡 MEDIO | deploy.sh | Contraseña truncada/insegura | deploy.sh | 431 | Caracteres especiales problemáticos | Usar solo alphanumeric |
| 1️⃣2️⃣ | 🟡 MEDIO | deploy.sh | Sin validación de .env | deploy.sh | generate_env | Errores silenciosos | Validar variables críticas |
| 1️⃣3️⃣ | 🟡 MEDIO | PHP | Health check sin validar BD | docker-compose.prod.yml | 106 | PHP "healthy" pero BD inaccesible | Script que verifica conexión MySQL |

---

## 🎯 Problemas Críticos (Requieren Fix INMEDIATO)

### 1. DB_ROOT_PASSWORD Generación

```
┌─────────────────────────────────────────────────────────────┐
│ ❌ INCORRECTO (Actual)                                      │
├─────────────────────────────────────────────────────────────┤
│ local DB_ROOT_PASSWORD=$(openssl rand -base64 32)           │
│ cat > .env <<EOF                                            │
│ DB_ROOT_PASSWORD=$DB_ROOT_PASSWORD  ← En heredoc, $() no ejec│
│ EOF                                                          │
│                                                              │
│ Resultado en .env:                                          │
│ DB_ROOT_PASSWORD=$(openssl rand -base64 32)  ← LITERAL ❌   │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ ✅ CORRECTO (Fixed)                                         │
├─────────────────────────────────────────────────────────────┤
│ DB_ROOT_PASSWORD=$(openssl rand -base64 32)  ← Fuera heredoc│
│ export DB_ROOT_PASSWORD                      ← Exportar    │
│ cat > .env <<EOF                                            │
│ DB_ROOT_PASSWORD=$DB_ROOT_PASSWORD  ← Variables sustituidas │
│ EOF                                                          │
│                                                              │
│ Resultado en .env:                                          │
│ DB_ROOT_PASSWORD=abc123xyz456...  ← VALOR REAL ✅           │
└─────────────────────────────────────────────────────────────┘
```

**Impacto:** Sin este fix, TODOS los despliegues fallan en MySQL.

---

### 2. Variables de Credenciales No Exportadas

```
┌─────────────────────────────────────────────────────────────┐
│ ❌ INCORRECTO (Actual)                                      │
├─────────────────────────────────────────────────────────────┤
│ generate_env() {                                            │
│     local DB_PASSWORD="abc123"     ← Variable LOCAL         │
│     echo "DB_PASSWORD=$DB_PASSWORD" >> .env                 │
│ }                                                            │
│                                                              │
│ post_deploy() {                                             │
│     echo $DB_PASSWORD  ← ❌ Empty! (variable no existe)     │
│ }                                                            │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ ✅ CORRECTO (Fixed)                                         │
├─────────────────────────────────────────────────────────────┤
│ generate_env() {                                            │
│     local DB_PASSWORD="abc123"                              │
│     export DB_PASSWORD         ← EXPORTAR a shell           │
│     echo "DB_PASSWORD=$DB_PASSWORD" >> .env                 │
│ }                                                            │
│                                                              │
│ post_deploy() {                                             │
│     source .env  ← Recargar .env                            │
│     echo $DB_PASSWORD  ← ✅ Tiene valor                     │
│ }                                                            │
└─────────────────────────────────────────────────────────────┘
```

**Impacto:** Las credenciales no se pasan a docker-compose ni a post_deploy.

---

### 3. post_deploy() Sin Autenticación MySQL

```
┌─────────────────────────────────────────────────────────────┐
│ ❌ INCORRECTO (Actual)                                      │
├─────────────────────────────────────────────────────────────┤
│ mysql -h 127.0.0.1 -u root -e "SELECT 1"                   │
│         ↑            ↑          ↑                            │
│      Host      User (root)   SIN PASSWORD ❌                │
│                                                              │
│ Error: Access denied for user 'root'@'127.0.0.1'            │
│        (using password: NO) ← El problema                   │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ ✅ CORRECTO (Fixed)                                         │
├─────────────────────────────────────────────────────────────┤
│ mysql -h 127.0.0.1 -u root \                                │
│        -p"${DB_ROOT_PASSWORD}" \                            │
│        -e "SELECT 1"                                        │
│         ↑            ↑              ↑                        │
│      Host      User (root)   CON PASSWORD ✅                │
│                                                              │
│ Success: MySQL connection OK                                │
│ Migraciones: ✅ Se ejecutan                                 │
│ BD: ✅ Tablas creadas                                       │
└─────────────────────────────────────────────────────────────┘
```

**Impacto:** Sin este fix, las migraciones no se ejecutan y la BD queda vacía.

---

### 4. netcat (nc) No Instalado en PHP

```
┌─────────────────────────────────────────────────────────────┐
│ ❌ INCORRECTO (Actual)                                      │
├─────────────────────────────────────────────────────────────┤
│ while ! nc -z "$DB_HOST" "$DB_PORT"; do                     │
│         ↑     ↑                                              │
│    Requiere  Comando que                                    │
│    'nc'      podría no existir                              │
│              en Dockerfile                                   │
│                                                              │
│ Si nc no existe:                                            │
│ /bin/bash: nc: command not found                            │
│ La línea `set -e` causa que el script termine               │
│ PHP container muere → Docker lo reinicia → Loop infinito    │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ ✅ CORRECTO (Fixed) - Opción 1: Bash TCP                    │
├─────────────────────────────────────────────────────────────┤
│ timeout 3 bash -c ">/dev/tcp/$DB_HOST/$DB_PORT" && {        │
│   echo "MySQL disponible"                                   │
│ }                                                            │
│                                                              │
│ Ventajas:                                                   │
│ • No requiere instalar nc                                   │
│ • Bash is siempre disponible en Alpine Linux                │
│ • Portable a diferentes OSes                                │
│ • Built-in timeout (3 segundo)                              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ ✅ CORRECTO (Opción 2: mysqladmin)                          │
├─────────────────────────────────────────────────────────────┤
│ mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" --silent          │
│                                                              │
│ Ventajas:                                                   │
│ • Valida MySQL realmente (no solo TCP)                      │
│ • MySQL client ya está en Dockerfile                        │
│ • Más confiable que conexión TCP                            │
└─────────────────────────────────────────────────────────────┘
```

**Impacto:** PHP container muere, Docker lo reinicia, loop infinito.

---

### 5. Nginx Healthcheck Endpoint Inválido

```
┌─────────────────────────────────────────────────────────────┐
│ ❌ INCORRECTO (Actual)                                      │
├─────────────────────────────────────────────────────────────┤
│ healthcheck:                                                │
│   test: [ "CMD", "curl", "-f", "http://localhost/health" ]  │
│                                                              │
│ Problemas:                                                  │
│ 1. Endpoint '/health' NO EXISTE en Laravel                  │
│    → curl: (22) HTTP 404 Not Found                          │
│                                                              │
│ 2. Usa HTTP cuando Nginx está en HTTPS                      │
│    → curl: (7) Failed to connect                            │
│                                                              │
│ 3. Certificado autofirmado causa warnings                   │
│    → curl: (60) SSL certificate problem                     │
│                                                              │
│ Resultado: healthcheck NUNCA reporta success                │
│ Status: (unhealthy) después 120s                            │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ ✅ CORRECTO (Fixed)                                         │
├─────────────────────────────────────────────────────────────┤
│ healthcheck:                                                │
│   test: [ "CMD", "nc", "-zv", "127.0.0.1", "80" ]          │
│   OR                                                         │
│   test: [ "CMD", "curl", "-sf", "--insecure", \            │
│           "https://localhost" ]                             │
│                                                              │
│ Opción 1: TCP connectivity check (robusto)                  │
│ • Solo verifica que puerto 80/443 esté abierto             │
│ • No depende del endpoint específico                        │
│ • No falla por certificados                                │
│                                                              │
│ Opción 2: curl con insecure (validar respuesta)             │
│ • Verifica que Nginx está respondiendo                      │
│ • --insecure omite error de certificado autofirmado         │
│ • Más informativo que solo TCP                              │
│                                                              │
│ Resultado: healthcheck → healthy después 30s ✅             │
└─────────────────────────────────────────────────────────────┘
```

**Impacto:** Nginx nunca reporta healthy, deployment timeout.

---

## 🔗 Dependencias Entre Problemas

```
Problema #1 (DB_ROOT_PASSWORD literal)
        ↓
MySQL no puede autenticarse
        ↓
Problema #3 (post_deploy sin contraseña)
        ↓
Migraciones no se ejecutan
        ↓
BD queda vacía
        ↓
        │
        ├─→ Problema #2 (Variables no exportadas)
        │   ↓
        │   docker-compose no obtiene valores
        │   ↓
        │   Sustitución ${VAR} falla
        │
        ├─→ Problema #4 (nc no instalado)
        │   ↓
        │   PHP no espera a MySQL correctamente
        │   ↓
        │   PHP container reinicia
        │
        └─→ Problema #5 (Nginx healthcheck)
            ↓
            Nginx no reporta healthy
            ↓
            Aplicación nunca arranca
            ↓
            502 Bad Gateway para usuarios
```

---

## 📊 Matriz de Dependencias de Servicios

```
                    ┌─────────────────┐
                    │   Nginx (443)   │
                    │  Reverse Proxy  │
                    └────────┬────────┘
                             │ depends_on
                      condition = healthy?
                             ↓
                    ┌─────────────────┐
                    │   PHP (9000)    │
                    │   FastCGI FPM   │
                    └────────┬────────┘
                             │ depends_on
             ┌───────────────┼───────────────┐
             │ condition =   │ condition =   │
             │ healthy?      │ healthy?      │
             ↓               ↓               ↓
    ┌──────────────┐  ┌──────────────┐
    │ MySQL (3306) │  │ Redis (6379) │
    │  MariaDB 11  │  │   Cache DB   │
    └──────────────┘  └──────────────┘

Problema actual: Nginx NO espera (depends_on sin condition)
        → Nginx inicia mientras PHP aún no está listo
        → Connection refused
        → 502 Bad Gateway

✅ Fix: Agregar condition: service_healthy a Nginx
```

---

## 🧪 Síntomas de Cada Problema

| Problema | Síntoma en Logs | Síntoma Usuario | Timeline |
|----------|-----------------|-----------------|----------|
| #1: DB_ROOT_PASSWORD | `[ERROR] Fatal error: Can't initialize DB` | Deployment timeout | ~30 segundos |
| #2: Variables no exportadas | `ERROR 1045 (28000): Access denied` | MySQL connection fails | Variable |
| #3: No password en post_deploy | `permission denied` en migraciones | 502 Bad Gateway | ~60-90 seg |
| #4: nc no instalado | `command not found: nc` | PHP container restarts | ~10-15 seg |
| #5: Nginx healthcheck | `curl: (22) HTTP 404` | Deployment timeout | ~120+ seg |
| #6: Redis sin auth | `(error) NOAUTH Authentication required` | Redis unavailable | ~30-40 seg |
| #7: depends_on sin condition | `Connection refused` en logs Nginx | 502 Bad Gateway | ~5-10 seg |
| #8: Puertos expuestos | Acceso público a MySQL/Redis | Security breach | Any time |
| #9: Credencial no guardada | File not found en credentials.txt | User can't connect | Immedia |

---

## 🎯 Prioridad de Fixes

```
FASE 1 (Inmediato - 30 minutos)
  1. ✅ deploy.sh: DB_ROOT_PASSWORD generación
  2. ✅ deploy.sh: Variables export
  3. ✅ deploy.sh: post_deploy contraseña
  → Resultado: Deployment empieza a funcionar

FASE 2 (Corto plazo - 30 minutos)
  4. ✅ docker/php/entrypoint.sh: Reemplazar nc
  5. ✅ docker-compose.prod.yml: Nginx healthcheck
  6. ✅ docker-compose.prod.yml: Redis auth
  7. ✅ docker-compose.prod.yml: depends_on condition
  → Resultado: Todos los servicios inician correctamente

FASE 3 (Mediano plazo - 30 minutos)
  8. ✅ deploy.sh: Firewall config
  9. ✅ deploy.sh: Guardar DB_ROOT_PASSWORD
  10. ✅ docker-compose.prod.yml: Documentación versión
  → Resultado: Seguridad y documentación

Total tiempo de fixes: ~90 minutos
```

---

## ✅ Validación Rápida

Después de aplicar los fixes, ejecutar:

```bash
# 1. Deploy test
curl -sL https://raw.githubusercontent.com/user/nexus-saas/main/deploy.sh | \
    sudo bash -s -- 192.168.1.100

# 2. Esperar 5 minutos

# 3. Validar estado
docker compose -f /opt/nexus-saas/docker-compose.prod.yml ps

# Esperado:
# CONTAINER ID    STATUS
# mysql           Up 3 minutes (healthy)
# redis           Up 3 minutes (healthy)
# php             Up 2 minutes (healthy)
# nginx           Up 1 minutes (healthy)

# 4. Validar aplicación
curl -k https://192.168.1.100/health

# Esperado: HTTP 200 OK

# 5. Validar credenciales
cat /opt/nexus-saas/credentials.txt

# Esperado: Contiene DB_ROOT_PASSWORD con valor real
```

---

## 📚 Documentación Generada

```
✓ DEPLOYMENT_EXECUTIVE_SUMMARY.md    ← Resumen del ejecutivo
✓ DEPLOYMENT_ANALYSIS.md             ← Análisis exhaustivo de cada problema
✓ DEPLOYMENT_FIXES.md                ← Código exacto para cada fix
✓ DEPLOYMENT_QUICK_REFERENCE.md      ← Este archivo (matriz visual)
```

**Usar estos documentos como referencia durante la implementación de fixes.**
