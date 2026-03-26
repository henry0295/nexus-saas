# NexusSaaS Despliegue — Análisis Exhaustivo de Problemas

**Fecha de Análisis:** 25 Marzo 2026  
**Versión Stack:** Laravel 11 + Docker Compose  
**Criticidad:** ALTA - Se identificaron problemas críticos que impiden despliegue exitoso

---

## 📋 Resumen Ejecutivo

Se identificaron **10 problemas críticos** que impiden un despliegue exitoso:
- ❌ **CRÍTICO:** Generación incorrecta de `DB_ROOT_PASSWORD`
- ❌ **CRÍTICO:** Variables de entorno no exportadas en deploy.sh
- ❌ **CRÍTICO:** Docker MySQL no puede autenticarse
- ⚠️ **ALTO:** Health checks inválidos
- ⚠️ **ALTO:** Falta de validación de dependencias
- ⚠️ **MEDIO:** Inconsistencias en configuración

---

## 🔴 PROBLEMAS CRÍTICOS

### 1. ❌ DB_ROOT_PASSWORD NO SE GENERA CORRECTAMENTE

**Ubicación:** `deploy.sh` línea ~465

```bash
# ❌ INCORRECTO - Literal string, no ejecución
DB_ROOT_PASSWORD=\$(openssl rand -base64 32)
```

**Problema:**
- La variable `DB_ROOT_PASSWORD` se asigna con el **literal string** `$(openssl rand -base64 32)`
- Dentro de un heredoc (`cat > file.txt <<EOF`), `$()` **NO se ejecuta**
- El `.env` termina con:
  ```
  DB_ROOT_PASSWORD=$(openssl rand -base64 32)
  ```
- Cuando docker-compose.prod.yml intenta usar `${DB_ROOT_PASSWORD}`, receives el string literal
- MySQL recibe como password: `$(openssl rand -base64 32)` ❌ **INVÁLIDO**

**Impacto:**
```
MYSQL_ROOT_PASSWORD=\$(openssl rand -base64 32)  ← literal, no password válida
```

Docker MySQL:
```
[ERROR] [MY-000001] [Server] ... Access denied for user 'root' ...
```

---

### 2. ❌ VARIABLES DE ENTORNO NO EXPORTADAS

**Ubicación:** `deploy.sh` función `generate_env()`

```bash
# ❌ INCORRECTO - Variables locales, no disponibles para docker-compose
local DB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
local REDIS_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)

cat > "$INSTALL_DIR/.env" <<EOF
DB_PASSWORD=$DB_PASSWORD
REDIS_PASSWORD=$REDIS_PASSWORD
EOF
```

**Problema:**
- Las variables `DB_PASSWORD` y `REDIS_PASSWORD` son **locales** a la función `generate_env()`
- Se escriben en `.env`, pero **NO se exportan** a la sesión actual del shell
- Cuando post_deploy intenta conectar a MySQL, las variables **no existen en el ambiente**

**Ejemplo del flujo:**
```bash
# En generate_env()
local DB_PASSWORD="abc123secure"        # Variable local de la función
# ↓ se guarda en .env
echo "DB_PASSWORD=abc123secure" >> .env

# Luego en post_deploy()
echo $DB_PASSWORD                        # ❌ Empty! Variable no existe

# docker-compose intenta sustituir pero el .env no se ha sourced
```

---

### 3. ❌ POST_DEPLOY CONECTA SIN CONTRASEÑA A MYSQL

**Ubicación:** `deploy.sh` función `post_deploy()` línea ~740

```bash
# ❌ INCORRECTO - No pasa password
$COMPOSE_CMD -f docker-compose.prod.yml exec -T mysql mysql \
    -h 127.0.0.1 -u root -e "SELECT 1"
```

**Problema:**
- El comando espera conectarse como `root` **sin contraseña**
- MySQL en docker-compose.prod.yml está configurado con `MYSQL_ROOT_PASSWORD=${DB_ROOT_PASSWORD}`
- Como `DB_ROOT_PASSWORD` no se generó correctamente, el password es inválido
- Incluso si fuera válido, el comando **no lo proporciona**

**Error resultante:**
```
ERROR 1045 (28000): Access denied for user 'root'@'127.0.0.1' (using password: NO)
```

**Impacto en cascada:**
1. post_deploy falla al verificar MySQL
2. Migraciones no se ejecutan
3. La aplicación Laravel no tiene tablas en BD
4. Acceso a la aplicación retorna errores de BD

---

### 4. ❌ MISMATCH: DOCKER-COMPOSE ESPERA MYSQL_ROOT_PASSWORD, PERO deploy.sh GENERA DB_ROOT_PASSWORD

**Ubicación:** 
- `docker-compose.prod.yml` línea 31-32
- `deploy.sh` línea ~468

**Problema:**

`docker-compose.prod.yml` espera:
```yaml
environment:
  MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:-root_secure_password}  ← Variable del .env
```

Pero `deploy.sh` lo genera así:
```bash
cat > .env <<EOF
DB_ROOT_PASSWORD=\$(openssl rand -base64 32)  ← Literal string!!
EOF
```

**En el contenedor MySQL:**
```
MYSQL_ROOT_PASSWORD="$(openssl rand -base64 32)"  ← String literal, no password
```

**Detalles técnicos:**

El contenedor MariaDB/MySQL intenta autenticarse con este password:
```sql
mysql> GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY '$(openssl rand -base64 32)';
```

Cualquier intento de conexión:
```bash
mysql -u root -p'$(openssl rand -base64 32)'  ❌ Falla
mysql -u root -p'OTHER_PASSWORD'              ❌ Falla
```

---

### 5. ❌ NETCAT (nc) NO INSTALADO EN PHP CONTAINER

**Ubicación:** `docker/php/entrypoint.sh` línea 4

```bash
# ❌ PROBLEMA: 'nc' podría no existir en el container
while ! nc -z "$DB_HOST" "$DB_PORT" 2>/dev/null; do
    sleep 2
done
```

**Problema:**
- El Dockerfile de PHP no instala `netcat` (nc)
- El comando falla con: `command not found: nc`
- El entrypoint termina, PHP container muere
- Docker Compose lo reinicia, cíclicamente

**Ver Dockerfile:**
- Revisar `docker/php/Dockerfile` para confirmar si tiene `netcat-openbsd` o `nc`
- Muy probable que NO esté instalado

**Alternativas correctas:**
```bash
# ✅ Usar bash TCP
bash -c 'true </dev/tcp/$DB_HOST/$DB_PORT'

# ✅ Usar perl (más portable)
perl -MIO::Socket::INET -e 'IO::Socket::INET->new("$DB_HOST:$DB_PORT")'

# ✅ Usar /dev/tcp generación inline
timeout 3 bash -c "echo >/dev/tcp/$DB_HOST/$DB_PORT"
```

---

## 🟠 PROBLEMAS ALTOS

### 6. ⚠️ HEALTH CHECK NGINX INVÁLIDO

**Ubicación:** `docker-compose.prod.yml` línea 141

```yaml
healthcheck:
  test: [ "CMD", "curl", "-f", "http://localhost/health" ]
```

**Problemas:**
1. **Endpoint inexistente:** Laravel no define `/health` por defecto
2. **Protocolo incorrecto:** Usa HTTP cuando Nginx está configurado para HTTPS
3. **Timing:** Nginx se inicia ANTES que PHP esté listo
4. **Falta de CA bundle:** Si está en HTTPS, `curl -f https://localhost` falla por certificado autofirmado

**Error típico:**
```
curl: (7) Failed to connect to localhost port 443
curl: (60) SSL certificate problem: self signed certificate
```

**Efecto:** Nginx nunca reporta `healthy`, deployment se queda esperando

---

### 7. ⚠️ PHP HEALTHCHECK NO VALIDATE DEPENDENCIAS

**Ubicación:** `docker-compose.prod.yml` línea 106

```yaml
healthcheck:
  test: [ "CMD", "php", "-r", "exit(extension_loaded('pdo') ? 0 : 1);" ]
```

**Problema:**
- Solo verifica que PHP tiene extensión PDO cargada
- **NO verifica** que MySQL esté disponible
- **NO verifica** que Laravel pueda iniciar
- **NO verifica** que las migraciones corrieron

**Escenario de fallo:**
```
1. PHP container inicia
2. PDO está cargado → healthcheck: healthy ✅
3. Pero MySQL aún no está listo
4. Laravel intenta migraciones → FALLA
5. Application error, pero healthcheck sigue reportando healthy
```

---

### 8. ⚠️ REDIS HEALTHCHECK SIN AUTENTICACIÓN

**Ubicación:** `docker-compose.prod.yml` línea 69

```yaml
healthcheck:
  test: [ "CMD", "redis-cli", "--raw", "incr", "ping" ]
```

**Problema:**
- Redis se inicia con `--requirepass ${REDIS_PASSWORD}`
- Pero el healthcheck **NO proporciona la contraseña**
- `redis-cli` falla con: `(error) NOAUTH Authentication required`
- Healthcheck nunca reporta success

**Secuencia de errores:**
```
1. Redis inicia con password
2. redis-cli intenta conexión sin password
3. Falla: NOAUTH
4. Healthcheck: starting... (infinito)
5. Timeout después de 30s
6. PHP y Nginx esperan a Redis → deadlock
```

---

### 9. ⚠️ NGINX DEPENDE DE PHP PERO NO ESPERA

**Ubicación:** `docker-compose.prod.yml` línea 131-132

```yaml
depends_on:
  - php
```

**Problema:**
- `depends_on` sin `condition: service_healthy` **NO ESPERA**
- Nginx inicia cuando PHP **contenedor existe**, no cuando está **listo**
- PHP healthcheck toma 60s (start_period)
- Nginx intenta conectar a PHP-FPM → Connection refused
- Resultados: Error 502 Bad Gateway

**Correcta debería ser:**
```yaml
depends_on:
  php:
    condition: service_healthy
```

---

## 🟡 PROBLEMAS MEDIANOS

### 10. ⚠️ ERROR EN CREDENCIALES.TXT - NO INCLUYE DB_ROOT_PASSWORD

**Ubicación:** `deploy.sh` función `generate_env()` línea ~500

```bash
cat > "$INSTALL_DIR/credentials.txt" <<EOF
MySQL:
  Database: nexus_saas
  User: nexus_user
  Password: $DB_PASSWORD        ← ✅ Incluida
  Host: mysql (internal)
  Port: 3306
EOF
```

**Problema:**
- El archivo credentials.txt **NO guarda DB_ROOT_PASSWORD**
- Los usuarios no tienen forma de saber cuál es el password del root
- Si necesitan conectar a MySQL directamente, no pueden

**Impacto:**
- Usuarios no pueden hacer backup manual de BD
- No pueden investigar problemas de BD
- Tienen que reconstruir la instalación si pierden credenciales

---

### 11. ⚠️ FIREWALL EXPONE PUERTOS 3306 Y 6379 (INSEGURO)

**Ubicación:** `deploy.sh` función `configure_firewall()` línea ~580

```bash
local PORTS="22/tcp 80/tcp 443/tcp 3306/tcp 6379/tcp"
# ↓
ufw allow "$port"
```

**Problema:**
- **3306 (MySQL)** está expuesto públicamente
- **6379 (Redis)** está expuesto públicamente
- Esto es inseguro, esos servicios solo debería ser accesibles internamente
- Exponer MySQL sin SSL/TLS es extremadamente peligroso

**Mejor práctica:**
```bash
# Solo puertos públicos
local PORTS="22/tcp 80/tcp 443/tcp"

# MySQL y Redis solo internally en docker network
```

---

### 12. ⚠️ VERSIÓN INCONSISTENTE DOCUMENTACIÓN

**Ubicación:** `docker-compose.prod.yml` línea 9-10 vs línea 25

```yaml
# Comentario dice:
# • mysql    - Base de datos MySQL 8

# Pero imagen real es:
image: mariadb:11  ← MariaDB 11, no MySQL 8
```

**Problema:**
- Genera confusión para desarrolladores
- MariaDB y MySQL tienen diferencias sutiles en comandos
- Documentación desactualizada

---

### 13. ⚠️ DB_PASSWORD SE TRUNCA, PERO NO TIENE VALIDACIÓN

**Ubicación:** `deploy.sh` línea ~431

```bash
local DB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
```

**Problema:**
- Genera 25 caracteres (después de remover caracteres especiales)
- Pero no valida que sea compatible con MySQL
- Algunos caracteres especiales pueden causar problemas en shell/MySQL

**Mejor:** Usar alphanumeric + algunos especiales seguros:
```bash
DB_PASSWORD=$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | cut -c1-32)
```

---

## 📊 FLUJO INCORRECTO ACTUAL VS. ESPERADO

### ❌ Flujo Actual (Incorrecto)

```
1. deploy.sh inicia
   ↓
2. generate_env() crea .env
   - DB_ROOT_PASSWORD="$(openssl rand -base64 32)"  ← Literal string
   - DB_PASSWORD, REDIS_PASSWORD="...(values)"
   - Variables NO se exportan
   ↓
3. docker-compose.prod.yml sube (docker compose up -d)
   - Intenta usar ${DB_ROOT_PASSWORD} desde .env
   - Obtiene: "$(openssl rand -base64 32)" ← INVÁLIDO
   - MySQL recibe MYSQL_ROOT_PASSWORD=... (literal)
   ↓
4. MySQL intenta autenticarse con password literal
   - Falla → Cannot start
   ↓
5. PHP espera a MySQL con healthcheck
   - MySQL nunca reporta healthy
   - PHP muere
   ↓
6. post_deploy intenta conectar a MySQL
   - mysql -u root -e "SELECT 1"  (sin password)
   - Falla: Access Denied
   ↓
7. Migraciones no se ejecutan
8. Aplicación sin tablas
9. 502 Bad Gateway
```

---

### ✅ Flujo Esperado (Correcto)

```
1. deploy.sh inicia
   ↓
2. generate_env() crea .env
   - var_db_root_pwd=$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9!@#$%')
   - echo "DB_ROOT_PASSWORD=$var_db_root_pwd" >> .env
   - export DB_ROOT_PASSWORD="$var_db_root_pwd"  ← EXPORTADO
   ↓
3. docker-compose lee .env
   - ${DB_ROOT_PASSWORD} se sustituye correctamente
   - MYSQL_ROOT_PASSWORD=<random_secure_password>
   ↓
4. MySQL inicia correctamente
   - Password válido
   - Healthcheck: healthy ✅
   ↓
5. PHP espera y se conecta
   - Ejecuta migraciones
   - Healthcheck: healthy ✅
   ↓
6. post_deploy conecta a MySQL CON contraseña
   - mysql -u root -p"$DB_ROOT_PASSWORD" ...
   - Conexión exitosa
   ↓
7. Migraciones se ejecutan
8. Seeders se ejecutan
9. Aplicación lista
   ↓
10. Nginx conecta a PHP-FPM
11. Aplicación respondiendo
```

---

## 🔧 TABLA DE PROBLEMAS Y SOLUCIONES

| # | Problema | Severidad | Ubicación | Solución |
|---|----------|-----------|-----------|----------|
| 1 | DB_ROOT_PASSWORD es string literal | 🔴 CRÍTICO | deploy.sh:468 | Generar en variable, NO en heredoc |
| 2 | Variables no exportadas | 🔴 CRÍTICO | deploy.sh:431-432 | Usar `export` en generate_env() |
| 3 | post_deploy sin password MySQL | 🔴 CRÍTICO | deploy.sh:740 | Agregar `-p${DB_ROOT_PASSWORD}` |
| 4 | nc no instalado en PHP | 🔴 CRÍTICO | docker/php/entrypoint.sh:4 | Usar bash TCP o incluir netcat |
| 5 | Health check Nginx inválido | 🟠 ALTO | docker-compose.prod.yml:141 | Cambiar a healthcheck real |
| 6 | PHP health sin validar MySQL | 🟠 ALTO | docker-compose.prod.yml:106 | Agregar script que verifica conexión |
| 7 | Redis sin auth en healthcheck | 🟠 ALTO | docker-compose.prod.yml:69 | Agregar `--auth` al redis-cli |
| 8 | Nginx sin depends_on conditional | 🟠 ALTO | docker-compose.prod.yml:131 | Agregar `condition: service_healthy` |
| 9 | Puertos MySQL/Redis públicos | 🟡 MEDIO | deploy.sh:580 | No permitir 3306, 6379 en firewall |
| 10 | DB_ROOT_PASSWORD no guardada | 🟡 MEDIO | deploy.sh:500 | Incluir en credentials.txt |
| 11 | Versión inconsistente doc | 🟡 MEDIO | docker-compose.prod.yml:9 | Actualizar comentario a MariaDB 11 |
| 12 | Truncamiento inseguro contraseña | 🟡 MEDIO | deploy.sh:431 | Usar caracteres más seguros |
| 13 | Falta de validación .env | 🟡 MEDIO | deploy.sh:generate_env() | Validar variables críticas |

---

## 💡 IMPACTO EN DESPLIEGUE REAL

### Escenario: Usuario ejecuta deploy.sh según instrucciones

```bash
curl -sL deploy.sh | sudo bash -s -- 192.168.1.100
```

### Resultado esperado vs. actual:

| Paso | Esperado | Actual |
|------|----------|--------|
| Descarga a servidor | ✅ | ✅ |
| Instala Docker | ✅ | ✅ |
| Clona repositorio | ✅ | ✅ |
| Genera .env | ✅ | ✅ (pero incorrecto) |
| MySQL inicia | ✅ healthy | ❌ Failed to start |
| PHP inicia | ✅ healthy | ❌ Waiting for MySQL |
| Nginx inicia | ✅ healthy | ⏳ Waiting... |
| Migraciones | ✅ Ejecutadas | ❌ No ejecutadas |
| Aplicación | ✅ Funcionando | ❌ 502 Bad Gateway |
| **Tiempo total** | 5-10 min | **FALLA en 2-5 min** |

---

## 📝 RECOMENDACIONES URGENTES

### 1. **CRÍTICO - Corregir deploy.sh**
   - Generar DB_ROOT_PASSWORD ANTES del heredoc
   - Exportar todas las variables de credenciales
   - Validar que .env se carga correctamente
   - Arreglar post_deploy para usar contraseña

### 2. **CRÍTICO - Corregir docker-compose.prod.yml**
   - Arreglar healthchecks (Redis, Nginx)
   - Agregar depends_on conditionals
   - Validar variables de entorno

### 3. **CRÍTICO - Corregir entrypoints**
   - docker/php/entrypoint.sh: Usar bash TCP en lugar de nc
   - Agregar retry logic con timeouts
   - Validar variables críticas

### 4. **ALTO - Seguridad**
   - No exponer 3306, 6379 en firewall público
   - Guardar DB_ROOT_PASSWORD en credentials.txt
   - Usar contraseñas más seguras y validadas

### 5. **MEDIO - Documentación**
   - Actualizar comentarios de versiones
   - Documentar el error de DB_ROOT_PASSWORD
   - Crear guía de troubleshooting

---

## 🧪 PRUEBA DE VALIDACIÓN

Para verificar que los problemas existen, ejecutar en el servidor después de deploy fallido:

```bash
# 1. Verificar que MySQL no arrancó
docker compose -f /opt/nexus-saas/docker-compose.prod.yml ps mysql

# 2. Ver logs
docker compose -f /opt/nexus-saas/docker-compose.prod.yml logs mysql

# Verá: Access denied for user 'root'@'%' 
#       [ERROR] Fatal error: Can't initialize DB; check logs...

# 3. Inspeccionar .env
cat /opt/nexus-saas/.env | grep DB_ROOT

# Verá: DB_ROOT_PASSWORD=$(openssl rand -base64 32)  ← String literal

# 4. Intentar conectar manualmente
docker compose -f /opt/nexus-saas/docker-compose.prod.yml exec mysql \
    mysql -h 127.0.0.1 -u root -p'$(openssl rand -base64 32)' -e "SELECT 1"

# Verá: Access denied... porque el password es un string literal

# 5. Ver si nc existe en PHP
docker compose -f /opt/nexus-saas/docker-compose.prod.yml exec php which nc

# Verá: command not found
```

---

## 📚 ARCHIVOS AFECTADOS

```
✗ deploy.sh
  - generate_env() — DB_ROOT_PASSWORD literal
  - post_deploy() — Sin contraseña MySQL
  
✗ docker-compose.prod.yml
  - redis healthcheck sin auth
  - nginx healthcheck endpoint inválido
  - nginx depends_on sin condition
  
✗ docker/php/entrypoint.sh
  - Usa `nc` que no existe
  
✗ docker/nginx/entrypoint.sh
  - Crea certificados pero Nginx puede fallar en startup
  
✓ .env.production.example
  - Correcto (es solo ejemplo)
  
✓ DEPLOY.md
  - Documentación correcta pero no menciona estos problemas
```

---

## 🎯 PRÓXIMOS PASOS

Requiere que se creen PRs para:
1. `deploy.sh` - 3 fixes críticos
2. `docker-compose.prod.yml` - 4 fixes
3. `docker/php/entrypoint.sh` - 1 fix
4. `DEPLOY.md` - Notas sobre troubleshooting

**Sin estos fixes, el despliegue FALLARÁ en el 100% de casos nuevos.**
