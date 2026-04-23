<template>
  <!-- If authenticated, show dashboard redirect message; otherwise show landing page -->
  <div v-if="!isLoaded" class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
      <p class="text-gray-600">Cargando...</p>
    </div>
  </div>

  <div v-else-if="auth.isAuthenticated" class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="text-center">
      <p class="text-gray-600 mb-4">Redirigiendo al dashboard...</p>
      <NuxtLink to="/dashboard" class="btn-primary">
        Ir al Dashboard
      </NuxtLink>
    </div>
  </div>

  <div v-else class="bg-gradient-to-r from-blue-600 to-blue-800 text-white">
    <!-- Hero Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
        <div>
          <h1 class="text-4xl md:text-5xl font-bold mb-6">
            Tu Plataforma de Comunicación Empresarial
          </h1>
          <p class="text-xl mb-8 text-blue-100">
            Envía SMS, emails y llamadas de audio con un solo clic. Gestiona tus clientes, controla tus costos y crece tu negocio.
          </p>

          <div class="flex gap-4">
            <NuxtLink to="/auth/signup" class="btn-primary bg-white text-blue-600 hover:bg-gray-100">
              Comenzar Gratis
            </NuxtLink>
            <NuxtLink to="/auth/login" class="btn-secondary border-white text-white hover:bg-blue-700">
              Iniciar Sesión
            </NuxtLink>
          </div>
        </div>

        <div class="hidden md:block">
          <div class="bg-white bg-opacity-10 rounded-lg p-8">
            <div class="space-y-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-400 rounded-full flex items-center justify-center">✓</div>
                <span>100 créditos de prueba incluidos</span>
              </div>
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-400 rounded-full flex items-center justify-center">✓</div>
                <span>SMS, Email y Audio IVR</span>
              </div>
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-400 rounded-full flex items-center justify-center">✓</div>
                <span>Precios desde $0.026 por SMS</span>
              </div>
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-400 rounded-full flex items-center justify-center">✓</div>
                <span>API REST completa documentada</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="bg-blue-700 py-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-12">Características Principales</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="bg-white bg-opacity-10 rounded-lg p-8">
            <div class="text-4xl mb-4">📱</div>
            <h3 class="text-xl font-bold mb-2">SMS Masivos</h3>
            <p class="text-blue-100">
              Envía hasta 1,000 SMS simultáneamente con validación automática de números.
            </p>
          </div>

          <div class="bg-white bg-opacity-10 rounded-lg p-8">
            <div class="text-4xl mb-4">📧</div>
            <h3 class="text-xl font-bold mb-2">Emails Masivos</h3>
            <p class="text-blue-100">
              Distribuye emails a miles de contactos con tracking de entregas.
            </p>
          </div>

          <div class="bg-white bg-opacity-10 rounded-lg p-8">
            <div class="text-4xl mb-4">📞</div>
            <h3 class="text-xl font-bold mb-2">Llamadas IVR</h3>
            <p class="text-blue-100">
              Automatiza llamadas de audio con soporte multiidoma.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
      <div class="text-center">
        <h2 class="text-3xl font-bold mb-6">¿Listo para comenzar?</h2>
        <NuxtLink to="/auth/signup" class="btn-primary bg-white text-blue-600 hover:bg-gray-100 text-lg">
          Crear Cuenta Gratis
        </NuxtLink>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: false,
})

const auth = useAuthStore()
const isLoaded = ref(false)

// Check authentication after hydration on client side
onMounted(() => {
  // Hydrate auth state from localStorage
  auth.hydrate()
  isLoaded.value = true
  
  // If authenticated, redirect to dashboard
  if (auth.isAuthenticated) {
    navigateTo('/dashboard')
  }
})

// Ensure hydration happens immediately if already mounted
if (process.client && typeof window !== 'undefined') {
  auth.hydrate()
}
</script>
