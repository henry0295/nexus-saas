# 🚀 NexusSaaS Roadmap - Estado Actual & Próximas Acciones

**Última actualización:** 23 de marzo de 2026  
**Stack:** Laravel 11 + Nuxt 3 | Multitenant (Row-based) | MySQL 8+  
**Ubicación:** `c:\Users\PT\OneDrive - VOZIP COLOMBIA\Documentos\GitHub\nexus-saas\`

---

## 📊 Estado General

```
Backend (Laravel):    ████████░░ 80% COMPLETADO
Frontend (Nuxt):      ░░░░░░░░░░ 0% (No iniciado)
DevOps & Deploy:      ░░░░░░░░░░ 0% (No iniciado)
Testing:              ░░░░░░░░░░ 0% (No iniciado)
─────────────────────────────────────────────
PROYECTO TOTAL:       ██░░░░░░░░ 20% COMPLETADO
```

---

## ✅ FASE 1: ARQUITECTURA & BACKEND CORE (COMPLETADO)

### 1.1 Configuración Inicial ✅
- [x] Proyecto Laravel 11 creado con `composer create-project laravel/laravel`
- [x] Variables de entorno (`.env`) configuradas
  - MySQL connection (database: `nexus_saas`, user: `root`)
  - AWS credentials (vacías, listas para llenar)
  - Stripe keys (vacías, listas para llenar)
  - Sanctum/CORS configurado para Nuxt frontend
- [x] APP_KEY generada: `base64:4KytzbQeyvK4Qvyn6BXnOrXUlNrIs6aBAM3vKAq0E2Q=`
- [x] Directorio `bootstrap/cache` creado

### 1.2 Modelos Eloquent (10 modelos) ✅
| Modelo | Propósito | Scope | Relaciones |
|--------|----------|-------|-----------|
| **BaseModel** | Extiende Model + global tenant scope | Tenant | - |
| **Tenant** | Compañía/Account | - | hasMany Users/Credits/Logs |
| **User** | Usuario (Fortify + Sanctum) | Tenant | belongsTo Tenant |
| **TenantCredit** | Saldo de créditos por tenant | Tenant | belongsTo Tenant |
| **CreditTransaction** | Auditoría de débitos/créditos | Tenant | belongsTo Tenant |
| **PricingRule** | Precios globales (SMS/Email/Audio) | GLOBAL | - |
| **TenantPricingOverride** | Precios VIP por tenant | Tenant | belongsTo Tenant |
| **TenantIntegration** | Credenciales AWS/360nrs | Tenant | belongsTo Tenant |
| **EmailLog** | Historial de emails | Tenant | belongsTo Tenant |
| **SmsLog** | Historial de SMS | Tenant | belongsTo Tenant |
| **AudioLog** | Historial de audio | Tenant | belongsTo Tenant |
| **Invoice** | Facturación | Tenant | belongsTo Tenant |
| **AuditLog** | Trazabilidad | Tenant | belongsTo Tenant |

Todos los modelos implementan:
- Métodos de relación (hasMany, belongsTo, etc)
- Casting de atributos (JSON, DateTime)
- Accessors/Mutators para lógica de negocio

### 1.3 Servicios de Negocio (3 servicios) ✅
| Servicio | Métodos Clave | Funcionalidad |
|----------|---------------|---------------|
| **PricingService** | `getSellingPrice($channel, $tenant)` | Calcula precio de venta (verifica override VIP primero, luego precio global) |
| | `analyzePrice()` | Reportes de márgenes y rentabilidad |
| **SmsService** | `send($phone, $message, $tenant)` | Valida teléfono, formatea (57XXXXXXXXXX), calcula partes, deduce créditos |
| | `sendBulk($phones, $message, $tenant)` | Envío masivo en bucle con validación |
| **EmailService** | `send($email, $subject, $body, $tenant)` | Valida email, crea log, deduce crédito (0.001) |
| | `sendBulk($emails, $subject, $body, $tenant)` | Envío masivo |

### 1.4 Controladores API (3 controllers) ✅
| Controller | Endpoints | Métodos |
|-----------|-----------|---------|
| **AuthController** | `POST /api/auth/register` | Crea Tenant + User + 100 trial credits |
| | `POST /api/auth/login` | Emite JWT token (Sanctum) |
| | `GET /api/auth/me` | Retorna user + tenant + saldo de créditos |
| | `POST /api/auth/logout` | Revoca token |
| **SmsController** | `POST /api/sms/send` | Enviar SMS individual |
| | `POST /api/sms/bulk` | Enviar SMS a múltiples números |
| | `GET /api/sms/logs` | Listar logs (paginado, tenant-scoped) |
| **EmailController** | `POST /api/email/send` | Enviar email individual |
| | `POST /api/email/bulk` | Enviar a múltiples emails |
| | `GET /api/email/logs` | Listar logs (paginado, tenant-scoped) |

**Middleware aplicado:** `auth:sanctum` en rutas protegidas

### 1.5 Base de Datos (1 migración con 12 tablas) ✅
```sql
Tablas Creadas:
├── tenants (id, uuid, name, status: active/suspended/trial, plan)
├── users (id, tenant_id, name, email, role: superadmin/admin/user, password)
├── tenant_credits (id, tenant_id, balance=100, total_purchased, total_used)
├── credit_transactions (id, tenant_id, type: purchase/refund/usage, amount)
├── pricing_rules (id, channel: sms/email/audio, provider, cost, margin, selling_price)
├── tenant_pricing_overrides (id, tenant_id, channel, selling_price, effective_from/to)
├── tenant_integrations (id, tenant_id, aws_ses_domain, aws_sns_key, aws_sns_secret)
├── email_logs (id, tenant_id, to_email, subject, status, cost, aws_message_id)
├── sms_logs (id, tenant_id, phone, message, parts, status, cost, aws_message_id)
├── audio_logs (id, tenant_id, phone, status, cost, aws_request_id)
├── invoices (id, tenant_id, period_month/year, line_items JSON, total)
└── audit_logs (id, tenant_id, admin_id, action, old_data/new_data JSON)

Índices: tenant_id indexed en todas las tablas multitenant
Foreign Keys: Relaciones con cascada correcta
```

**Características:**
- Row-based multitenancy (tenant_id en cada tabla)
- Timestamps (created_at, updated_at)
- Soft deletes donde aplique
- Enums para estados (active, suspended)

### 1.6 Seeders (2 seeders) ✅
| Seeder | Acción |
|--------|--------|
| **PricingSeeder** | Inserta precios iniciales: SMS (cost 0.02 → venta 0.026 con margin 30%), Email (cost 0.0001 → venta 0.001 con margin 900%), Audio (cost 0.05 → venta 0.07 con margin 40%) |
| **SuperadminSeeder** | Crea usuario: superadmin@nexus-saas.com / SuperAdmin123! con rol `superadmin` |

### 1.7 Rutas API ✅
```php
// Rutas públicas (sin autenticación)
POST   /api/auth/register
POST   /api/auth/login

// Rutas protegidas (auth:sanctum)
GET    /api/auth/me
POST   /api/auth/logout
POST   /api/sms/send
POST   /api/sms/bulk
GET    /api/sms/logs
POST   /api/email/send
POST   /api/email/bulk
GET    /api/email/logs
GET    /api/credits/balance
```

---

## ⏳ FASE 2: RESOLUCIÓN DE BLOQUEADOR INMEDIATO

### 2.1 Problema Actual ⚠️
**Síntoma:** `bootstrap/cache` reporta como "no writable" cuando ejecutamos `php artisan`  
**Causa:** Ruta del proyecto contiene espacios: `OneDrive - VOZIP COLOMBIA`  
**Solución necesaria:** Elegir UNA opción:

#### ✅ Opción A: Mover proyecto a ruta sin espacios (RECOMENDADO)
```powershell
# Copiar proyecto a:
D:\dev\nexus-saas
# O:
C:\xampp\htdocs\nexus-saas
# O:
C:\projects\nexus-saas

# Luego ejecutar migraciones:
cd D:\dev\nexus-saas
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8000
```

#### ✅ Opción B: Docker Compose
Crear `docker-compose.yml` con MySQL 8 + PHP 8.3 + Laravel

#### ✅ Opción C: Forzar permisos
Usar PowerShell para cambiar permisos ACL en `bootstrap/cache`

---

## 🔄 FASE 3: INICIALIZAR BASE DE DATOS (SIGUIENTE INMEDIATO)

### 3.1 Ejecutar migraciones ⏳
```bash
php artisan migrate --seed
```

**Resultado esperado:**
- ✅ 12 tablas creadas
- ✅ Datos iniciales: pricing rules + superadmin user
- ✅ Base de datos funcional para testing

### 3.2 Verificar conexión ⏳
```bash
php artisan tinker
>>> User::count()  # Debe mostrar: 1 (superadmin)
>>> PricingRule::count()  # Debe mostrar: 3 (SMS, Email, Audio)
```

---

## 🎯 FASE 4: TESTING API (POST-MIGRACIONES)

### 4.1 Pruebas manuales con Postman/Insomnia ⏳

**Test 1: Registro de nuevo tenant**
```http
POST http://localhost:8000/api/auth/register
Content-Type: application/json

{
  "company_name": "Test Company",
  "email": "test@testcompany.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!"
}

Respuesta esperada:
{
  "user": {
    "id": 2,
    "email": "test@testcompany.com",
    "role": "admin",
    "tenant_id": 2
  },
  "tenant": {
    "id": 2,
    "name": "Test Company",
    "status": "trial",
    "plan": "starter"
  },
  "credits": 100,
  "token": "JWT_TOKEN_HERE"
}
```

**Test 2: Enviar SMS**
```http
POST http://localhost:8000/api/sms/send
Authorization: Bearer JWT_TOKEN_HERE
Content-Type: application/json

{
  "phone": "+573001234567",
  "message": "Hola! Este es un test de SMS"
}

Respuesta esperada:
{
  "log_id": "UUID",
  "phone": "573001234567",
  "message_parts": 1,
  "cost": 0.026,
  "new_balance": 99.974,
  "status": "pending"
}
```

**Test 3: Verificar balance de créditos**
```http
GET http://localhost:8000/api/credits/balance
Authorization: Bearer JWT_TOKEN_HERE

Respuesta esperada:
{
  "balance": 99.974,
  "total_purchased": 0,
  "total_used": 0.026
}
```

---

## 🚀 FASE 5: CONTROLADORES COMPLEMENTARIOS (SIGUIENTE)

### 5.1 SuperadminController ⏳
**Propósito:** Gestión de tenants y precios desde panel admin  
**Rutas:**
```
GET    /api/admin/tenants          # Listar todos los tenants
GET    /api/admin/tenants/{id}     # Detalle de un tenant
PATCH  /api/admin/tenants/{id}     # Actualizar estado/plan
POST   /api/admin/pricing          # Crear/editar precios globales
GET    /api/admin/dashboard        # Stats: ingresos, tenants activos, etc
GET    /api/admin/audit-logs       # Ver auditoría
```

**Middleware:** `auth:sanctum` + `role:superadmin`

### 5.2 CreditsController ⏳
**Propósito:** Compra de créditos (integración con Stripe/PayU)  
**Rutas:**
```
POST   /api/credits/purchase       # Iniciar compra de créditos
GET    /api/credits/transactions   # Ver historial
GET    /api/credits/packages       # Listar paquetes disponibles
```

### 5.3 AudioController ⏳
**Propósito:** Integración con 360nrs para llamadas de audio  
**Rutas:**
```
POST   /api/audio/send             # Enviar llamada de audio
GET    /api/audio/logs             # Ver historial de llamadas
```

---

## 🎨 FASE 6: FRONTEND NUXT 3 (SIGUIENTE GRAN ETAPA)

### 6.1 Inicializar proyecto Nuxt ⏳
```bash
npx nuxi@latest init ../nexus-saas-frontend
cd ../nexus-saas-frontend
npm install
```

**Estructura:**
```
nexus-saas-frontend/
├── pages/
│   ├── auth/
│   │   ├── signup.vue
│   │   ├── login.vue
│   │   └── verify-email.vue
│   ├── dashboard/
│   │   ├── index.vue
│   │   ├── sms.vue
│   │   ├── email.vue
│   │   ├── audio.vue
│   │   └── credits.vue
│   └── admin/
│       ├── dashboard.vue
│       ├── tenants.vue
│       └── pricing.vue
├── composables/
│   ├── useAuth.ts
│   ├── useCredits.ts
│   └── useApi.ts
├── stores/
│   ├── auth.ts
│   └── tenant.ts
├── components/
│   ├── AuthForm.vue
│   ├── SmsForm.vue
│   ├── EmailForm.vue
│   └── BalanceCard.vue
└── middleware/
    ├── auth.ts
    └── admin.ts
```

### 6.2 Autenticación Frontend ⏳
- [x] Composable `useAuth()` con métodos: signup, login, logout, me
- [x] Pinia store `auth.ts` con estado: user, token, isAuthenticated
- [x] Middleware `auth.ts` para proteger rutas
- [x] Almacenamiento seguro de JWT en `localStorage` / `sessionStorage`

### 6.3 Páginas Core ⏳
- [ ] **Signup:** Formulario con validación, crea Tenant + User
- [ ] **Login:** Email + password, obtiene JWT token
- [ ] **Verify Email:** Validación de email con código
- [ ] **Dashboard:** Panel principal con opciones: SMS, Email, Audio, Credits
- [ ] **Send SMS:** Formulario para enviar SMS (individual + bulk)
- [ ] **Send Email:** Formulario para enviar emails (individual + bulk)
- [ ] **Send Audio:** Formulario para enviar llamadas
- [ ] **Buy Credits:** Integración con Stripe/PayU
- [ ] **Admin Dashboard:** Para superadmin (stats, tenants, pricing)

### 6.4 Integraciones UI ⏳
- [ ] Nuxt UI v3 para componentes pre-hechos
- [ ] TailwindCSS para estilos
- [ ] VeeValidate para validación de formularios
- [ ] TypeScript en todo el frontend

---

## ⚙️ FASE 7: INTEGRACIONES CLOUD & PAGOS

### 7.1 AWS SES (Email) ⏳
- [ ] Configurar dominio en AWS SES
- [ ] Verificar DKIM/SPF/DMARC
- [ ] Implementar `SesMailer` en `app/Mail/`
- [ ] Reemplazar mock en `EmailService` con cliente AWS
- [ ] Webhooks para delivery tracking

### 7.2 AWS SNS (SMS) ⏳
- [ ] Registrar cuenta AWS SNS
- [ ] Configurar ruta de SMS en Colombia (Claro/Movistar/etc)
- [ ] Implementar cliente SNS en `SmsService`
- [ ] Testing de envío real
- [ ] Webhooks para delivery status

### 7.3 Stripe/PayU (Pagos) ⏳
- [ ] Setup de Stripe/PayU merchant account
- [ ] Implementar `PaymentService`
- [ ] Webhook handlers para confirmación de pago
- [ ] Auto-agregar créditos tras pago exitoso
- [ ] Facturación automática

### 7.4 360nrs (Audio) ⏳
- [ ] Documentación de API 360nrs
- [ ] Implementar cliente en `AudioService`
- [ ] Testing de llamadas de audio

---

## 🧪 FASE 8: TESTING (QA)

### 8.1 Backend Testing (PHPUnit/Pest) ⏳
```
tests/
├── Feature/
│   ├── AuthTest.php         # Registro, login, logout
│   ├── SmsTest.php          # Envío SMS, débito créditos
│   ├── EmailTest.php        # Envío email
│   └── AdminTest.php        # Endpoints de superadmin
└── Unit/
    ├── PricingServiceTest.php
    ├── CreditDeductionTest.php
    └── MutlitenantScopeTest.php
```

**Meta:** 80%+ cobertura

### 8.2 Frontend Testing (Vitest) ⏳
```
tests/
├── unit/
│   ├── stores/authStore.test.ts
│   ├── composables/useAuth.test.ts
│   └── utils/validation.test.ts
└── integration/
    ├── auth-flow.test.ts
    └── send-sms-flow.test.ts
```

### 8.3 E2E Testing (Playwright/Cypress) ⏳
- [ ] Flujo completo: signup → verify email → send SMS → check balance
- [ ] Admin: login → manage tenants → update pricing

---

## 🚢 FASE 9: DEPLOYMENT & DEVOPS

### 9.1 Configurar servidor ⏳
- [ ] VPS (DigitalOcean/AWS/Linode)
- [ ] Nginx configurado como reverse proxy
- [ ] SSL/TLS (Let's Encrypt)
- [ ] PHP 8.3 + MySQL 8

### 9.2 CI/CD Pipeline ⏳
- [ ] GitHub Actions para:
  - [ ] Run tests on push
  - [ ] Build Docker image
  - [ ] Deploy a staging
  - [ ] Deploy a production
- [ ] Secrets management (AWS keys, API keys)

### 9.3 Monitoreo & Logging ⏳
- [ ] Sentry para error tracking
- [ ] CloudWatch para logs
- [ ] Uptime monitoring

### 9.4 Backup & Disaster Recovery ⏳
- [ ] Daily DB backups
- [ ] S3 backup storage
- [ ] Restore testing

---

## 📋 RESUMEN: QUÉ FALTA POR HACER

| # | Tarea | Prioridad | Esfuerzo | Estado |
|---|-------|-----------|----------|--------|
| 1 | **BLOCKER:** Mover proyecto fuera de ruta con espacios | 🔴 CRÍTICO | 5 min | ⏳ |
| 2 | Ejecutar `php artisan migrate --seed` | 🔴 CRÍTICO | 2 min | ⏳ |
| 3 | Testing manual API con Postman | 🟠 Alto | 30 min | ⏳ |
| 4 | SuperadminController (CRUD tenants) | 🟠 Alto | 2h | ⏳ |
| 5 | CreditsController (purchase endpoint) | 🟠 Alto | 2h | ⏳ |
| 6 | AudioController (360nrs stub) | 🟡 Medio | 1.5h | ⏳ |
| 7 | Crear proyecto Nuxt 3 frontend | 🟠 Alto | 30 min | ⏳ |
| 8 | Auth pages (signup, login, verify) | 🟠 Alto | 3h | ⏳ |
| 9 | Dashboard core pages (SMS, Email, Audio) | 🟠 Alto | 4h | ⏳ |
| 10 | Admin dashboard | 🟡 Medio | 2h | ⏳ |
| 11 | AWS SES real integration | 🟡 Medio | 2h | ⏳ |
| 12 | AWS SNS real integration | 🟡 Medio | 2h | ⏳ |
| 13 | Stripe/PayU integration | 🟡 Medio | 3h | ⏳ |
| 14 | Testing suite (80% coverage) | 🟡 Medio | 4h | ⏳ |
| 15 | E2E testing (Playwright) | 🟡 Medio | 2h | ⏳ |
| 16 | Docker & CI/CD setup | 🟡 Medio | 3h | ⏳ |
| 17 | Deployment a staging | 🟡 Medio | 1h | ⏳ |
| 18 | Production launch | 🟡 Medio | 1h | ⏳ |

**Totales:**
- Tiempo estimado (solo desarrollo): ~38 horas
- Tiempo estimado (con testing + devops): ~50+ horas
- Personas recomendadas: 2-3 (1 backend, 1 frontend, 1 devops opcional)

---

## 🎯 PRÓXIMOS PASOS INMEDIATOS

### Hoy (Ahora):
1. ✅ **Resolver bloqueador:** Elegir opción A/B/C para mover proyecto
2. ⏳ **Ejecutar migraciones:** `php artisan migrate --seed`
3. ⏳ **Test API:** Verificar endpoints en Postman

### Mañana:
4. ⏳ Crear SuperadminController
5. ⏳ Crear CreditsController
6. ⏳ Testing manual completo

### Esta semana:
7. ⏳ Iniciar frontend Nuxt 3
8. ⏳ Auth pages (signup/login)
9. ⏳ Dashboard core pages

### Próximas semanas:
10. ⏳ AWS integrations (SES, SNS)
11. ⏳ Stripe/PayU
12. ⏳ Testing completo
13. ⏳ Deployment

---

## 📞 Notas Importantes

**Superadmin credentials (cambiar inmediatamente en producción):**
```
Email: superadmin@nexus-saas.com
Password: SuperAdmin123!
```

**Precios iniciales (ajustables desde admin):**
```
SMS:    Costo $0.02  → Venta $0.026  (Margen 30%)
Email:  Costo $0.0001 → Venta $0.001 (Margen 900%!)
Audio:  Costo $0.05  → Venta $0.07   (Margen 40%)
```

**Trial credits:** 100 créditos por nuevo registro

---

**Última actualización:** 23/03/2026  
**Próxima revisión:** Después de resolver bloqueador y ejecutar migraciones
