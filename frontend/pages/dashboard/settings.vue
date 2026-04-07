<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Configuración</h1>

    <div class="space-y-6">
      <!-- Account Info -->
      <div class="card">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Información de Cuenta</h2>
        <div class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Email</label>
              <input
                type="email"
                :value="authStore.user?.email"
                disabled
                class="input-field mt-1 bg-gray-100 cursor-not-allowed"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Nombre de Usuario</label>
              <input
                type="text"
                :value="authStore.user?.name"
                disabled
                class="input-field mt-1 bg-gray-100 cursor-not-allowed"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Empresa</label>
              <input
                type="text"
                :value="authStore.tenant?.name"
                disabled
                class="input-field mt-1 bg-gray-100 cursor-not-allowed"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Rol</label>
              <input
                type="text"
                :value="authStore.user?.role"
                disabled
                class="input-field mt-1 bg-gray-100 cursor-not-allowed"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Estado del Plan</label>
            <div class="mt-2 flex items-center">
              <span class="px-3 py-1 rounded-full text-sm font-medium"
                :class="authStore.tenant?.status === 'active' 
                  ? 'bg-green-100 text-green-800' 
                  : 'bg-yellow-100 text-yellow-800'">
                {{ authStore.tenant?.status === 'active' ? 'Activo' : 'Prueba' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Change Password -->
      <div class="card">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Cambiar Contraseña</h2>
        <form @submit.prevent="changePassword" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Contraseña Actual</label>
            <input
              v-model="passwordForm.current"
              type="password"
              class="input-field mt-1"
              placeholder="••••••••"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Nueva Contraseña</label>
            <input
              v-model="passwordForm.new"
              type="password"
              class="input-field mt-1"
              placeholder="••••••••"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Confirmar Contraseña</label>
            <input
              v-model="passwordForm.confirm"
              type="password"
              class="input-field mt-1"
              placeholder="••••••••"
            />
          </div>

          <div v-if="passwordError" class="rounded-md bg-red-50 p-4">
            <div class="text-sm text-red-700">{{ passwordError }}</div>
          </div>

          <div v-if="passwordSuccess" class="rounded-md bg-green-50 p-4">
            <div class="text-sm text-green-700">✅ Contraseña actualizada exitosamente</div>
          </div>

          <button
            type="submit"
            :disabled="passwordLoading"
            class="btn-primary text-white"
          >
            {{ passwordLoading ? 'Actualizando...' : 'Actualizar Contraseña' }}
          </button>
        </form>
      </div>

      <!-- API Keys (for developers) -->
      <div class="card">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Claves API</h2>
        <p class="text-gray-600 mb-4">
          Utiliza estas claves para integrar Nexus SaaS con tus aplicaciones.
        </p>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Clave API</label>
            <div class="flex items-center gap-2">
              <input
                type="text"
                :value="apiKey"
                disabled
                class="input-field bg-gray-100 cursor-not-allowed flex-1"
              />
              <button
                @click="copyToClipboard(apiKey)"
                type="button"
                class="btn-secondary"
              >
                📋 Copiar
              </button>
            </div>
          </div>

          <div>
            <button type="button" class="btn-secondary text-sm">
              🔄 Regenerar Clave API
            </button>
          </div>
        </div>
      </div>

      <!-- Integrations -->
      <div class="card">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Integraciones</h2>
        <div class="space-y-4">
          <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
            <div>
              <h3 class="font-semibold text-gray-900">AWS SES</h3>
              <p class="text-sm text-gray-600">Envío de emails a través de Amazon SES</p>
            </div>
            <button type="button" class="btn-secondary">Configurar</button>
          </div>

          <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
            <div>
              <h3 class="font-semibold text-gray-900">AWS SNS</h3>
              <p class="text-sm text-gray-600">Envío de SMS a través de Amazon SNS</p>
            </div>
            <button type="button" class="btn-secondary">Configurar</button>
          </div>

          <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
            <div>
              <h3 class="font-semibold text-gray-900">Stripe / PayU</h3>
              <p class="text-sm text-gray-600">Procesamiento de pagos</p>
            </div>
            <button type="button" class="btn-secondary">Configurar</button>
          </div>

          <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
            <div>
              <h3 class="font-semibold text-gray-900">360nrs</h3>
              <p class="text-sm text-gray-600">Envío de llamadas de voz IVR</p>
            </div>
            <button type="button" class="btn-secondary">Configurar</button>
          </div>
        </div>
      </div>

      <!-- Danger Zone -->
      <div class="card border-2 border-red-200 bg-red-50">
        <h2 class="text-xl font-semibold text-red-900 mb-6">Zona de Peligro</h2>
        <p class="text-red-800 mb-4">
          Estas acciones son irreversibles. Por favor, úsalas con cuidado.
        </p>

        <button
          @click="logout"
          type="button"
          class="btn-secondary text-red-700 border-red-300 hover:bg-red-100"
        >
          🚪 Cerrar Sesión
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { useApi } from '~/composables/useApi'

definePageMeta({
  middleware: 'auth',
})

const router = useRouter()
const authStore = useAuthStore()
const api = useApi()

const passwordForm = reactive({
  current: '',
  new: '',
  confirm: '',
})

const passwordLoading = ref(false)
const passwordError = ref<string | null>(null)
const passwordSuccess = ref(false)

// Generate a mock API key (in real app, this would come from backend)
const apiKey = computed(() => {
  return authStore.user?.id 
    ? `sk_live_${authStore.user.id}_${Math.random().toString(36).substr(2, 9).toUpperCase()}`
    : ''
})

const changePassword = async () => {
  if (passwordForm.new !== passwordForm.confirm) {
    passwordError.value = 'Las contraseñas no coinciden'
    return
  }

  if (passwordForm.new.length < 8) {
    passwordError.value = 'La contraseña debe tener al menos 8 caracteres'
    return
  }

  passwordLoading.value = true
  passwordError.value = null
  passwordSuccess.value = false

  try {
    await api.post('/auth/change-password', {
      current_password: passwordForm.current,
      new_password: passwordForm.new,
    })

    passwordForm.current = ''
    passwordForm.new = ''
    passwordForm.confirm = ''
    passwordSuccess.value = true

    setTimeout(() => {
      passwordSuccess.value = false
    }, 3000)
  } catch (err: any) {
    passwordError.value = err.response?.data?.error || 'Error al cambiar contraseña'
  } finally {
    passwordLoading.value = false
  }
}

const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text)
  // You could add a toast notification here
}

const logout = () => {
  authStore.logout()
  router.push('/auth/login')
}
</script>
