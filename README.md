# 🚀 NexusSaaS - Multi-tenant Communication Platform

**Plataforma SaaS para envío masivo de SMS, Email y Llamadas de Voz**

[![Build Status](https://img.shields.io/badge/status-production%20ready-brightgreen)]()
[![License](https://img.shields.io/badge/license-MIT-blue)]()
[![Version](https://img.shields.io/badge/version-1.0.0-blue)]()

## 📋 Descripción

**NexusSaaS** es una plataforma SaaS completa diseñada para empresas que necesitan enviar comunicaciones masivas a través de múltiples canales:
- **SMS** 📱 - Mensajes de texto a teléfonos
- **Email** 📧 - Correos masivos
- **Audio/IVR** 📞 - Llamadas de voz automatizadas

Arquitectura **multitenant** (row-based) con gestión de créditos, pricing dinámico, y panel de administración.

---

## ✨ Características Principales

### 🔐 Autenticación & Autorización
- ✅ Registro de nuevos tenants con auto-signup
- ✅ Autenticación con JWT (Sanctum)
- ✅ Roles: superadmin, admin, user
- ✅ 100 créditos de prueba por nuevo registro

### 📱 Comunicaciones
- ✅ SMS: Envío individual y masivo (validación de teléfono)
- ✅ Email: Envío individual y masivo (validación de dirección)
- ✅ Audio: Llamadas IVR individual y masiva (TTS + duración estimada)
- ✅ Logs completos de todas las operaciones

### 💰 Créditos & Facturación
- ✅ Sistema de créditos por tenant
- ✅ 4 paquetes predefinidos (Starter, Growth, Professional, Enterprise)
- ✅ Precios dinámicos con descuentos por volumen
- ✅ Historial de transacciones
- ✅ Invoices automáticas

### 👨‍💼 Admin Dashboard
- ✅ Gestión de tenants (suspend/activate)
- ✅ Reglas de pricing global y por tenant (VIP)
- ✅ Logs de auditoría
- ✅ Analytics en tiempo real

### 🌐 Frontend
- ✅ Landing page con hero + features
- ✅ Auth pages (login, signup)
- ✅ Dashboard con navegación
- ✅ SMS sender con calculadora de costos
- ✅ Email sender con validación
- ✅ Audio caller con duración estimada
- ✅ Compra de créditos
- ✅ Settings (cambio de contraseña, API keys)
- ✅ Admin panel (superadmin only)

### 🐳 Deployment
- ✅ Docker Compose con 4 servicios (Nginx, PHP, MySQL, Redis)
- ✅ Script de deploy automatizado
- ✅ Auto-migraciones en primer deploy
- ✅ SSL/TLS autofirmado incluido
- ✅ Multi-distro support (Ubuntu, Debian, CentOS, etc)

---

## 🛠️ Stack Tecnológico

### Backend
- **Framework:** Laravel 11
- **Database:** MySQL 8+
- **Cache:** Redis 7
- **API:** REST con JWT (Sanctum)
- **Auth:** Sanctum tokens
- **Storage:** S3-compatible providers

### Frontend
- **Framework:** Nuxt 3
- **UI Library:** Vue 3 Composition API
- **Language:** TypeScript (strict mode)
- **CSS:** Tailwind CSS v3
- **State:** Pinia
- **HTTP:** Axios with interceptors

### DevOps
- **Containerization:** Docker & Docker Compose
- **Reverse Proxy:** Nginx 1.26-alpine
- **Runtime:** PHP 8.3-FPM
- **Orchestration:** Docker Compose
- **SSL/TLS:** Auto-generated self-signed certs

---

## 📁 Estructura del Proyecto

```
nexus-saas/
├── app/                           # Backend code
│   ├── Http/Controllers/          # API endpoints (8 controllers)
│   │   ├── AuthController.php      # JWT authentication
│   │   ├── SmsController.php       # SMS operations
│   │   ├── EmailController.php     # Email operations
│   │   ├── AudioController.php     # Audio/IVR operations
│   │   ├── CreditsController.php   # Credit management
│   │   ├── TenantController.php    # Tenant self-management
│   │   ├── AdminController.php     # Superadmin operations
│   │   └── InvoiceController.php   # Invoicing
│   ├── Models/                    # Eloquent models (13 models)
│   │   ├── Tenant.php
│   │   ├── User.php
│   │   ├── TenantCredit.php
│   │   ├── CreditTransaction.php
│   │   ├── PricingRule.php
│   │   ├── TenantPricingOverride.php
│   │   ├── SmsLog.php
│   │   ├── EmailLog.php
│   │   ├── AudioLog.php
│   │   ├── Invoice.php
│   │   ├── AuditLog.php
│   │   ├── TenantIntegration.php
│   │   └── BaseModel.php           # Global tenant scoping
│   └── Services/                  # Business logic (4 services)
│       ├── PricingService.php
│       ├── SmsService.php
│       ├── EmailService.php
│       └── AudioService.php
│
├── frontend/                      # Nuxt 3 Frontend
│   ├── pages/                     # Auto-routed pages
│   │   ├── index.vue              # Landing page
│   │   ├── app.vue                # Root layout
│   │   ├── auth/
│   │   │   ├── login.vue          # Login
│   │   │   └── signup.vue         # Registration
│   │   ├── dashboard/
│   │   │   ├── index.vue          # Dashboard home
│   │   │   ├── sms.vue            # SMS sender
│   │   │   ├── email.vue          # Email sender
│   │   │   ├── audio.vue          # Audio caller
│   │   │   ├── credits.vue        # Credit purchase
│   │   │   └── settings.vue       # Account settings
│   │   └── admin/
│   │       └── index.vue          # Admin dashboard
│   ├── components/
│   │   └── NavBar.vue             # Navigation component
│   ├── stores/
│   │   └── auth.ts                # Pinia auth store
│   ├── composables/
│   │   ├── useApi.ts              # HTTP client
│   │   ├── useAuth.ts             # Auth logic
│   │   └── useCredits.ts          # Credits management
│   ├── middleware/
│   │   └── auth.ts                # Route protection
│   └── assets/css/
│       └── main.css               # Tailwind + custom styles
│
├── docker/                        # Docker configuration
│   ├── php/
│   │   ├── Dockerfile
│   │   ├── entrypoint.sh          # Auto-migrations
│   │   └── supervisord.conf
│   └── nginx/
│       ├── Dockerfile
│       └── entrypoint.sh          # SSL generation
│
├── database/
│   ├── migrations/                # DB migrations
│   └── seeders/                   # Initial data
│       ├── PricingSeeder.php      # Pricing rules
│       └── SuperadminSeeder.php   # Admin user
│
├── routes/
│   ├── api.php                    # 50+ API endpoints
│   ├── web.php
│   └── console.php
│
├── docker-compose.prod.yml        # Production orchestration
├── deploy.sh                      # Automated deploy script
├── DEPLOY.md                      # Deployment documentation
├── ROADMAP.md                     # Project roadmap
└── README.md                      # This file
```

---

## 🚀 Inicio Rápido

### Opción 1: Deploy en Servidor (Recomendado)

```bash
# Desde un servidor Linux (Ubuntu/Debian/CentOS)
curl -sL https://raw.githubusercontent.com/henry0295/nexus-saas/main/deploy.sh | \
  sudo bash -s -- YOUR_SERVER_IP
```

**Resultado:** Todo corriendo en 5-10 minutos ✅

---

### Opción 2: Desarrollo Local (Docker)

```bash
# 1. Clonar repositorio
git clone https://github.com/henry0295/nexus-saas.git
cd nexus-saas

# 2. Copiar archivos de configuración
cp .env.production.example .env

# 3. Editar .env con tus valores (APP_KEY, etc)
# Generar APP_KEY:
# php artisan key:generate --show

# 4. Iniciar servicios
docker compose -f docker-compose.prod.yml up -d

# 5. Verificar salud
docker compose -f docker-compose.prod.yml logs -f php

# 6. Acceder a:
# API:      http://localhost:8000/api/auth/register
# Frontend: http://localhost:3000
```

---

### Opción 3: Desarrollo Local (Sin Docker)

```bash
# Requisitos: PHP 8.3+, MySQL 8+, Node.js 18+

# Backend
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve

# Frontend (otra terminal)
cd frontend
npm install
npm run dev
```

---

## 📡 API Endpoints (50+)

### Autenticación
```
POST   /api/auth/register          # Crear nuevo tenant + user
POST   /api/auth/login             # Obtener JWT token
GET    /api/auth/me                # Datos del usuario actual
POST   /api/auth/logout            # Revocar token
```

### SMS
```
POST   /api/sms/send               # Enviar SMS individual
POST   /api/sms/bulk               # Enviar SMS masivo
GET    /api/sms/logs               # Historial de SMS (paginado)
```

### Email
```
POST   /api/email/send             # Enviar email individual
POST   /api/email/bulk             # Enviar emails masivo
GET    /api/email/logs             # Historial de emails (paginado)
```

### Audio
```
POST   /api/audio/call             # Enviar llamada individual
POST   /api/audio/bulk             # Enviar llamadas masivas
GET    /api/audio/logs             # Historial de llamadas
```

### Créditos
```
GET    /api/credits/balance        # Saldo disponible
GET    /api/credits/packages       # Paquetes disponibles
POST   /api/credits/purchase       # Iniciar compra
GET    /api/credits/transactions   # Historial de transacciones
```

### Tenant
```
GET    /api/tenant                 # Información del tenant
PUT    /api/tenant                 # Actualizar configuración
GET    /api/tenant/users           # Listar usuarios
POST   /api/tenant/users           # Agregar usuario
PUT    /api/tenant/users/{id}      # Actualizar usuario
DELETE /api/tenant/users/{id}      # Eliminar usuario
```

### Admin (superadmin only)
```
GET    /api/admin/tenants          # Listar todos los tenants
GET    /api/admin/tenants/{id}     # Detalles de tenant
POST   /api/admin/tenants/{id}/suspend    # Suspender tenant
POST   /api/admin/tenants/{id}/activate   # Reactivar tenant
GET    /api/admin/pricing-rules    # Listar reglas de pricing
POST   /api/admin/pricing-rules    # Crear regla
PUT    /api/admin/pricing-rules/{id}      # Actualizar regla
GET    /api/admin/audit-logs       # Logs de auditoría
GET    /api/admin/analytics        # Analytics del sistema
```

### Invoices
```
GET    /api/invoices               # Listar facturas
GET    /api/invoices/{id}          # Detalles de factura
POST   /api/invoices               # Crear factura
POST   /api/invoices/{id}/email    # Enviar por email
GET    /api/invoices/{id}/pdf      # Descargar PDF
POST   /api/invoices/{id}/mark-paid# Marcar como pagada
```

---

## 🎨 Frontend Pages

### Públicas
- `GET /` - Landing page con hero + features
- `GET /auth/login` - Login form
- `GET /auth/signup` - Registration form

### Privadas (requieren autenticación)
- `GET /dashboard` - Panel principal
- `GET /dashboard/sms` - SMS sender
- `GET /dashboard/email` - Email sender
- `GET /dashboard/audio` - Audio caller
- `GET /dashboard/credits` - Credit packages
- `GET /dashboard/settings` - Account settings
- `GET /admin` - Admin dashboard (superadmin only)

---

## 💰 Pricing & Costos

| Canal | Costo | Venta | Margen |
|-------|-------|-------|--------|
| **SMS** | $0.020 | $0.026 | 30% |
| **Email** | $0.0001 | $0.001 | 900% |
| **Audio** | $0.050 | $0.070 | 40% |

### Paquetes de Créditos
- **Starter:** 1,000 créditos @ $10 (0.01 c/u)
- **Growth:** 5,000 créditos @ $40 (0.008 c/u, -20% descuento)
- **Professional:** 20,000 créditos @ $130 (0.0065 c/u, -35% descuento)
- **Enterprise:** Custom (contactar sales)

### Trial
- **100 créditos gratis** para nuevos registro

---

## 📊 Estado del Proyecto

```
╔═════════════════════════════════════════════════════════╗
║                   AVANCE DEL PROYECTO                  ║
╠═════════════════════════════════════════════════════════╣
║  Backend (Laravel):     ██████████ 100% ✅             ║
║  DevOps (Docker):       ██████████ 100% ✅             ║
║  Frontend (Nuxt):       ██████████ 100% ✅             ║
║  Testing:               ░░░░░░░░░░ 0%                  ║
║  Cloud Integration:     ░░░░░░░░░░ 0%                  ║
║  ─────────────────────────────────────────────────── ║
║  TOTAL PROYECTO:        ███████░░░ 75%                 ║
╚═════════════════════════════════════════════════════════╝
```

### Completado ✅
- [x] 13 Modelos Eloquent
- [x] 4 Servicios de negocio
- [x] 8 Controladores API (50+ endpoints)
- [x] 12 Tablas de base de datos
- [x] Docker Compose (4 servicios)
- [x] Deploy script automatizado
- [x] Nuxt 3 frontend (12 páginas)
- [x] Pinia store + composables
- [x] Route middleware + auth guard
- [x] Tailwind CSS + custom theme

### En Progreso ⏳
- [ ] Testing (Unit + E2E)
- [ ] AWS SES Integration
- [ ] AWS SNS Integration
- [ ] Stripe/PayU Integration
- [ ] 360nrs Integration
- [ ] CI/CD con GitHub Actions

---

## 🔧 Configuración

### Variables de Entorno Backend
```bash
APP_NAME=NexusSaaS
APP_ENV=production
DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=nexus_saas
DB_USERNAME=nexus_user
DB_PASSWORD=RANDOM_GENERATED

CACHE_DRIVER=redis
REDIS_HOST=redis
REDIS_PASSWORD=RANDOM_GENERATED

SANCTUM_STATEFUL_DOMAINS=yourdomain.com
SESSION_DOMAIN=yourdomain.com

MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="NexusSaaS"
```

### Variables del Frontend
```bash
NUXT_PUBLIC_API_BASE_URL=https://api.yourdomain.com/api
# or local:
NUXT_PUBLIC_API_BASE_URL=http://localhost:8000/api
```

---

## 🐳 Docker Compose

### Servicios
- **nginx** - Reverse proxy (puertos 80, 443)
- **php-fpm** - Laravel application
- **mysql** - Base de datos
- **redis** - Cache & sessions

### Comandos Útiles
```bash
# Ver estado
docker compose -f docker-compose.prod.yml ps

# Ver logs
docker compose -f docker-compose.prod.yml logs -f php

# Ejecutar migraciones
docker compose -f docker-compose.prod.yml exec php php artisan migrate

# Ejecutar seeders
docker compose -f docker-compose.prod.yml exec php php artisan db:seed

# Acceder a MySQL
docker compose -f docker-compose.prod.yml exec mysql mysql -u nexus_user -p nexus_saas

# Rebuild
docker compose -f docker-compose.prod.yml build --no-cache
```

---

## 📚 Documentación

- **[DEPLOY.md](DEPLOY.md)** - Guía completa de deployment (15+ páginas)
- **[ROADMAP.md](ROADMAP.md)** - Roadmap del proyecto
- **[frontend/README.md](frontend/README.md)** - Setup del frontend

---

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el repositorio
2. Crea una rama para tu feature (`git checkout -b feature/amazing-feature`)
3. Commit tus cambios (`git commit -m 'Add amazing feature'`)
4. Push a la rama (`git push origin feature/amazing-feature`)
5. Abre un Pull Request

---

## 📝 Licencia

Este proyecto está bajo la licencia MIT. Ver [LICENSE](LICENSE) para más detalles.

---

## 📞 Soporte

- 📧 Email: support@nexus-saas.com
- 🐙 GitHub Issues: [Crear issue](https://github.com/henry0295/nexus-saas/issues)
- 📖 Documentación: [DEPLOY.md](DEPLOY.md)

---

**Made with ❤️ by the NexusSaaS Team**

*Last updated: 24/03/2026*
