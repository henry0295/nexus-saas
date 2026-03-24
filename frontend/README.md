# NexusSaaS Frontend - Nuxt 3

Stack moderno para la plataforma NexusSaaS usando Nuxt 3, Vue 3, Tailwind CSS y TypeScript.

## 🚀 Stack

- **Nuxt 3** - Framework Vue con SSR
- **Vue 3** - Framework UI
- **TypeScript** - Tipo estático
- **Tailwind CSS** - Utilidades CSS
- **Pinia** - State management
- **Axios** - HTTP client

## 📦 Estructura del Proyecto

```
frontend/
├── pages/                    # Nuxt pages (rutas automáticas)
│   ├── index.vue            # Landing page
│   ├── auth/
│   │   ├── login.vue        # Login
│   │   └── signup.vue       # Registro
│   ├── dashboard/
│   │   ├── index.vue        # Dashboard principal
│   │   ├── sms.vue          # Envío de SMS
│   │   ├── email.vue        # Envío de emails
│   │   ├── audio.vue        # Envío de llamadas
│   │   ├── credits.vue      # Compra de créditos
│   │   └── settings.vue     # Configuración
│   └── admin/
│       └── index.vue        # Admin dashboard
├── components/              # Componentes Vue reutilizables
│   └── NavBar.vue          # Navbar principal
├── composables/            # Vue composables (hooks)
│   ├── useApi.ts           # HTTP API client
│   ├── useAuth.ts          # Autenticación
│   └── useCredits.ts       # Gestión de créditos
├── stores/                 # Pinia stores
│   └── auth.ts             # Auth store
├── middleware/             # Route middleware
│   └── auth.ts             # Auth guard
├── assets/                 # Static assets
│   └── css/
│       └── main.css        # Tailwind + custom styles
├── app.vue                 # Layout root
├── nuxt.config.ts          # Configuración Nuxt
├── tailwind.config.js      # Configuración Tailwind
├── tsconfig.json           # TypeScript config
└── package.json            # Dependencies
```

## 🛠️ Setup Local

### Requisitos
- Node.js v18+
- npm o yarn o pnpm

### Instalación

```bash
# Instalar dependencias
npm install

# Crear archivo .env
cp .env.example .env

# Actualizar NUXT_PUBLIC_API_BASE_URL si es necesario
# Por defecto apunta a http://localhost:8000/api
```

### Desarrollo

```bash
# Iniciar servidor de desarrollo
npm run dev

# Acceder a http://localhost:3000
```

### Build para Producción

```bash
# Build estático
npm run generate

# Build para Nitro
npm run build

# Preview build local
npm run preview
```

## 🔐 Autenticación

El frontend usa:
1. **Composable `useAuth`** - Manejo de login/logout/signup
2. **Pinia Store `useAuthStore`** - Almacena user, tenant, token
3. **Middleware `auth`** - Protege rutas privadas
4. **localStorage** - Persiste token entre sesiones

### Flujo de Login

```
Login → API /auth/login → Recibe token + user + tenant
→ Guarda en Pinia store + localStorage → Redirige a /dashboard
```

## 📡 API Client

Usar el composable `useApi()`:

```typescript
const { register, login, logout } = useAuth()

// O acceso directo a axios:
const api = useApi()
const response = await api.get('/credits/balance')
```

### Interceptors

- Request: Agrega `Authorization: Bearer TOKEN`
- Response: En error 401, hace logout automático

## 🏗️ Páginas Principales

| Ruta | Descripción | Autenticada |
|------|-------------|-------------|
| `/` | Landing page | No |
| `/auth/login` | Login | No |
| `/auth/signup` | Registro | No |
| `/dashboard` | Dashboard principal | Sí |
| `/dashboard/sms` | Envío de SMS | Sí |
| `/dashboard/email` | Envío de emails | Sí |
| `/dashboard/audio` | Envío de audio | Sí |
| `/dashboard/credits` | Compra de créditos | Sí |
| `/admin` | Admin panel | Sí (solo admin) |

## 🎨 Tailwind CSS

Clases personalizadas disponibles:
- `.btn-primary` - Botón principal (azul)
- `.btn-secondary` - Botón secundario (gris)
- `.card` - Tarjeta con shadow
- `.input-field` - Campo de entrada
- `.input-error` - Campo con error (rojo)

## 📝 Notas

- Frontend usa SSR (Server-Side Rendering)
- API base URL configurable via `NUXT_PUBLIC_API_BASE_URL`
- TypeScript strict mode activado
- ESLint configurado

## 🚀 Deployment

### Docker

Ver `Dockerfile` en root del proyecto.

### Vercel / Netlify

```bash
# Build genera carpeta `.output/` lista para deploy
npm run build
```

## 📚 Recursos

- [Nuxt 3 Docs](https://nuxt.com)
- [Vue 3 Docs](https://vuejs.org)
- [Tailwind CSS](https://tailwindcss.com)
- [Pinia](https://pinia.vuejs.org)
