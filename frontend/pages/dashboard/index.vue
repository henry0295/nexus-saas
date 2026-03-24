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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600 text-sm">Saldo de Créditos</p>
            <p class="text-3xl font-bold text-gray-900">{{ credits.balance || 0 }}</p>
          </div>
          <div class="text-3xl">💰</div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600 text-sm">Total Gastado</p>
            <p class="text-3xl font-bold text-gray-900">{{ credits.totalUsed || 0 }}</p>
          </div>
          <div class="text-3xl">📊</div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600 text-sm">Plan</p>
            <p class="text-3xl font-bold text-gray-900">{{ auth.tenant?.plan || 'N/A' }}</p>
          </div>
          <div class="text-3xl">📦</div>
        </div>
      </div>
    </div>

    <!-- Navigation -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <NuxtLink to="/dashboard/sms" class="card hover:shadow-lg transition-shadow cursor-pointer">
        <div class="text-4xl mb-4">📱</div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">SMS</h3>
        <p class="text-gray-600">
          Envía mensajes de texto a tus clientes
        </p>
      </NuxtLink>

      <NuxtLink to="/dashboard/email" class="card hover:shadow-lg transition-shadow cursor-pointer">
        <div class="text-4xl mb-4">📧</div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Email</h3>
        <p class="text-gray-600">
          Distribuye emails masivos
        </p>
      </NuxtLink>

      <NuxtLink to="/dashboard/audio" class="card hover:shadow-lg transition-shadow cursor-pointer">
        <div class="text-4xl mb-4">📞</div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Audio</h3>
        <p class="text-gray-600">
          Automatiza llamadas de audio
        </p>
      </NuxtLink>

      <NuxtLink to="/dashboard/credits" class="card hover:shadow-lg transition-shadow cursor-pointer">
        <div class="text-4xl mb-4">🛒</div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Comprar Créditos</h3>
        <p class="text-gray-600">
          Amplía tu saldo de créditos
        </p>
      </NuxtLink>

      <NuxtLink to="/dashboard/settings" class="card hover:shadow-lg transition-shadow cursor-pointer">
        <div class="text-4xl mb-4">⚙️</div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Configuración</h3>
        <p class="text-gray-600">
          Gestiona tu cuenta y usuarios
        </p>
      </NuxtLink>

      <NuxtLink v-if="auth.isAdmin" to="/admin" class="card hover:shadow-lg transition-shadow cursor-pointer bg-blue-50">
        <div class="text-4xl mb-4">👨‍💼</div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Admin Panel</h3>
        <p class="text-gray-600">
          Gestión de tenants y precios
        </p>
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth',
})

const auth = useAuthStore()
const { credits } = useCredits()
</script>
