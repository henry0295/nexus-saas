<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
          Iniciar Sesión
        </h2>
      </div>

      <form @submit.prevent="handleLogin" class="mt-8 space-y-6">
        <!-- Email -->
        <div>
          <label class="block text-sm font-medium text-gray-700">
            Email
          </label>
          <input
            v-model="form.email"
            type="email"
            required
            class="input-field mt-1"
            placeholder="tu@email.com"
          />
        </div>

        <!-- Password -->
        <div>
          <label class="block text-sm font-medium text-gray-700">
            Contraseña
          </label>
          <input
            v-model="form.password"
            type="password"
            required
            class="input-field mt-1"
            placeholder="••••••••"
          />
        </div>

        <!-- Error Message -->
        <div v-if="error" class="rounded-md bg-red-50 p-4">
          <div class="text-sm text-red-700">{{ error }}</div>
        </div>

        <!-- Submit Button -->
        <button type="submit" :disabled="loading" class="btn-primary w-full text-white">
          {{ loading ? 'Iniciando sesión...' : 'Iniciar Sesión' }}
        </button>
      </form>

      <!-- Sign Up Link -->
      <div class="text-center">
        <p class="text-gray-600">
          ¿No tienes cuenta?
          <NuxtLink to="/auth/signup" class="text-blue-600 hover:text-blue-500 font-medium">
            Regístrate aquí
          </NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const form = reactive({
  email: '',
  password: '',
})

const loading = ref(false)
const error = ref<string | null>(null)
const { login } = useAuth()

const handleLogin = async () => {
  loading.value = true
  error.value = null

  const result = await login(form.email, form.password)

  if (!result.success) {
    error.value = result.error
    loading.value = false
  }
}
</script>
