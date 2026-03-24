<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
          Crear Cuenta
        </h2>
        <p class="mt-2 text-gray-600">
          Obtén 100 créditos de prueba gratis
        </p>
      </div>

      <form @submit.prevent="handleSignup" class="mt-8 space-y-6">
        <!-- Company Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700">
            Nombre de Empresa
          </label>
          <input
            v-model="form.companyName"
            type="text"
            required
            class="input-field mt-1"
            placeholder="Mi Empresa"
          />
        </div>

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
            min="8"
            class="input-field mt-1"
            placeholder="••••••••"
          />
          <p class="mt-1 text-sm text-gray-500">
            Mínimo 8 caracteres
          </p>
        </div>

        <!-- Password Confirmation -->
        <div>
          <label class="block text-sm font-medium text-gray-700">
            Confirmar Contraseña
          </label>
          <input
            v-model="form.passwordConfirmation"
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

        <!-- Success Message -->
        <div v-if="success" class="rounded-md bg-green-50 p-4">
          <div class="text-sm text-green-700">¡Cuenta creada exitosamente! Redirigiendo...</div>
        </div>

        <!-- Submit Button -->
        <button type="submit" :disabled="loading || success" class="btn-primary w-full text-white">
          {{ loading ? 'Creando cuenta...' : 'Registrarse' }}
        </button>
      </form>

      <!-- Login Link -->
      <div class="text-center">
        <p class="text-gray-600">
          ¿Ya tienes cuenta?
          <NuxtLink to="/auth/login" class="text-blue-600 hover:text-blue-500 font-medium">
            Inicia sesión aquí
          </NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth',
})

const form = reactive({
  companyName: '',
  email: '',
  password: '',
  passwordConfirmation: '',
})

const loading = ref(false)
const error = ref<string | null>(null)
const success = ref(false)
const { register } = useAuth()

const handleSignup = async () => {
  if (form.password !== form.passwordConfirmation) {
    error.value = 'Las contraseñas no coinciden'
    return
  }

  loading.value = true
  error.value = null

  const result = await register(
    form.companyName,
    form.email,
    form.password,
    form.passwordConfirmation
  )

  if (!result.success) {
    error.value = result.error
    loading.value = false
  } else {
    success.value = true
  }
}
</script>
