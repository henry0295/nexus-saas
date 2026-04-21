<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">
        Dashboard
      </h1>
      <p class="text-gray-600 mt-2">
        Bienvenido de vuelta, {{ auth.user?.name }}
      </p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="card bg-gradient-to-br from-blue-50 to-blue-100">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600 text-sm font-medium">Saldo de Créditos</p>
            <p class="text-3xl font-bold text-blue-900 mt-2">{{ credits?.balance || 0 }}</p>
          </div>
          <div class="text-4xl">💰</div>
        </div>
      </div>

      <div class="card bg-gradient-to-br from-green-50 to-green-100">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600 text-sm font-medium">Emails Este Mes</p>
            <p class="text-3xl font-bold text-green-900 mt-2">{{ stats?.this_month?.breakdown_by_type?.email?.total_sent || 0 }}</p>
          </div>
          <div class="text-4xl">📧</div>
        </div>
      </div>

      <div class="card bg-gradient-to-br from-purple-50 to-purple-100">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600 text-sm font-medium">SMS Este Mes</p>
            <p class="text-3xl font-bold text-purple-900 mt-2">{{ stats?.this_month?.breakdown_by_type?.sms?.total_sent || 0 }}</p>
          </div>
          <div class="text-4xl">📱</div>
        </div>
      </div>

      <div class="card bg-gradient-to-br from-orange-50 to-orange-100">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600 text-sm font-medium">Gastado Este Mes</p>
            <p class="text-3xl font-bold text-orange-900 mt-2">${{ (stats?.this_month?.total_spent || 0).toFixed(2) }}</p>
          </div>
          <div class="text-4xl">📊</div>
        </div>
      </div>
    </div>

    <!-- Services Section with Menu -->
    <div class="mb-8">
      <h2 class="text-2xl font-bold text-gray-900 mb-4">Servicios Disponibles</h2>
      
      <!-- Services Menu Tabs -->
      <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
        <button 
          v-for="service in services"
          :key="service.id"
          @click="activeService = service.id"
          :class="[
            'px-4 py-2 rounded-lg font-medium transition-all',
            activeService === service.id
              ? 'bg-blue-600 text-white shadow-md'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          {{ service.icon }} {{ service.name }}
        </button>
      </div>

      <!-- Services Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- SMS -->
        <NuxtLink v-if="activeService === 'all' || activeService === 'sms'" to="/dashboard/sms" class="card hover:shadow-lg transition-all cursor-pointer group">
          <div class="text-5xl mb-4 group-hover:scale-110 transition-transform">📱</div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">SMS</h3>
          <p class="text-gray-600 mb-4">
            Envía mensajes de texto a tus clientes
          </p>
          <div class="pt-4 border-t border-gray-200">
            <p class="text-sm text-gray-500">Costo: $0.026 por SMS</p>
          </div>
        </NuxtLink>

        <!-- Email -->
        <NuxtLink v-if="activeService === 'all' || activeService === 'email'" to="/dashboard/email" class="card hover:shadow-lg transition-all cursor-pointer group">
          <div class="text-5xl mb-4 group-hover:scale-110 transition-transform">📧</div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">Email</h3>
          <p class="text-gray-600 mb-4">
            Distribuye emails masivos a tu audiencia
          </p>
          <div class="pt-4 border-t border-gray-200">
            <p class="text-sm text-gray-500">Costo: $0.001 por email</p>
          </div>
        </NuxtLink>

        <!-- Audio -->
        <NuxtLink v-if="activeService === 'all' || activeService === 'audio'" to="/dashboard/audio" class="card hover:shadow-lg transition-all cursor-pointer group">
          <div class="text-5xl mb-4 group-hover:scale-110 transition-transform">📞</div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">Audio / IVR</h3>
          <p class="text-gray-600 mb-4">
            Automatiza llamadas de audio e IVR
          </p>
          <div class="pt-4 border-t border-gray-200">
            <p class="text-sm text-gray-500">Costo: Según duración</p>
          </div>
        </NuxtLink>

        <!-- Buy Credits -->
        <NuxtLink v-if="activeService === 'all'" to="/dashboard/credits" class="card hover:shadow-lg transition-all cursor-pointer group">
          <div class="text-5xl mb-4 group-hover:scale-110 transition-transform">🛒</div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">Comprar Créditos</h3>
          <p class="text-gray-600 mb-4">
            Amplía tu saldo de créditos
          </p>
          <div class="pt-4 border-t border-gray-200">
            <p class="text-sm text-gray-500">Múltiples opciones de pago</p>
          </div>
        </NuxtLink>

        <!-- Settings -->
        <NuxtLink v-if="activeService === 'all'" to="/dashboard/settings" class="card hover:shadow-lg transition-all cursor-pointer group">
          <div class="text-5xl mb-4 group-hover:scale-110 transition-transform">⚙️</div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">Configuración</h3>
          <p class="text-gray-600 mb-4">
            Gestiona tu cuenta, usuarios y preferencias
          </p>
          <div class="pt-4 border-t border-gray-200">
            <p class="text-sm text-gray-500">Seguridad y privacidad</p>
          </div>
        </NuxtLink>

        <!-- Admin Panel -->
        <NuxtLink v-if="auth.isAdmin && (activeService === 'all' || activeService === 'admin')" to="/admin" class="card hover:shadow-lg transition-all cursor-pointer group bg-gradient-to-br from-blue-50 to-blue-100">
          <div class="text-5xl mb-4 group-hover:scale-110 transition-transform">👨‍💼</div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">Admin Panel</h3>
          <p class="text-gray-600 mb-4">
            Gestión de tenants, usuarios y precios
          </p>
          <div class="pt-4 border-t border-gray-200">
            <p class="text-sm text-blue-600 font-medium">Solo administradores</p>
          </div>
        </NuxtLink>
      </div>
    </div>

    <!-- Quick Tips -->
    <div class="card bg-gradient-to-r from-blue-50 to-indigo-50">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">💡 Tips Útiles</h3>
      <ul class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
        <li class="flex items-start">
          <span class="text-blue-600 mr-2">✓</span>
          <span>Crea plantillas para ahorrar tiempo en campañas frecuentes</span>
        </li>
        <li class="flex items-start">
          <span class="text-blue-600 mr-2">✓</span>
          <span>Monitorea tu saldo de créditos para no quedarte sin servicio</span>
        </li>
        <li class="flex items-start">
          <span class="text-blue-600 mr-2">✓</span>
          <span>Usa segmentación para optimizar tus campañas</span>
        </li>
        <li class="flex items-start">
          <span class="text-blue-600 mr-2">✓</span>
          <span>Revisa los reportes de tus envíos periódicamente</span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { useCredits } from '~/composables/useCredits'

definePageMeta({
  middleware: 'auth',
})

const auth = useAuthStore()
const { credits } = useCredits()
const api = useApi()

const activeService = ref('all')
const stats = ref<any>(null)
const loadingStats = ref(false)

const services = ref([
  { id: 'all', name: 'Todos', icon: '📋' },
  { id: 'sms', name: 'SMS', icon: '📱' },
  { id: 'email', name: 'Email', icon: '📧' },
  { id: 'audio', name: 'Audio', icon: '📞' },
  ...(auth.isAdmin ? [{ id: 'admin', name: 'Admin', icon: '👨‍💼' }] : []),
])

// Load dashboard statistics
const loadStats = async () => {
  loadingStats.value = true
  try {
    const response = await api.get('/dashboard/stats')
    stats.value = response.data
  } catch (err: any) {
    console.error('Error loading dashboard stats:', err)
  } finally {
    loadingStats.value = false
  }
}

// Load stats on component mount
onMounted(() => {
  loadStats()
})
</script>
