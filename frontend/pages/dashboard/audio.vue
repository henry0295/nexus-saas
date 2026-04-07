<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Enviar Llamadas de Voz</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Form -->
      <div class="lg:col-span-2">
        <div class="card">
          <form @submit.prevent="sendAudio" class="space-y-6">
            <!-- Recipients -->
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Números teléfónicos (uno por línea o separados por comas)
              </label>
              <textarea
                v-model="form.recipients"
                rows="5"
                class="input-field mt-1"
                placeholder="+573001234567&#10;+573009876543"
              />
              <p class="mt-2 text-sm text-gray-500">
                {{ recipientCount }} número(s)
              </p>
            </div>

            <!-- Message -->
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Mensaje de Voz
              </label>
              <textarea
                v-model="form.message"
                rows="4"
                maxlength="500"
                class="input-field mt-1"
                placeholder="Escribe el mensaje que se convertirá en voz..."
              />
              <p class="mt-2 text-sm text-gray-500">
                {{ form.message.length }}/500 caracteres | Duración estimada: {{ estimatedDuration }}s
              </p>
            </div>

            <!-- Gender -->
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Voz
              </label>
              <select v-model="form.gender" class="input-field mt-1">
                <option value="female">Femenina</option>
                <option value="male">Masculina</option>
              </select>
            </div>

            <!-- Language -->
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Idioma
              </label>
              <select v-model="form.language" class="input-field mt-1">
                <option value="es-CO">Español (Colombia)</option>
                <option value="es-ES">Español (España)</option>
                <option value="en-US">Inglés (USA)</option>
              </select>
            </div>

            <!-- Campaign (optional) -->
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Campaña (opcional)
              </label>
              <input
                v-model="form.campaign"
                type="text"
                class="input-field mt-1"
                placeholder="ej: CobrosMarzo"
              />
            </div>

            <!-- Error -->
            <div v-if="error" class="rounded-md bg-red-50 p-4">
              <div class="text-sm text-red-700">{{ error }}</div>
            </div>

            <!-- Success -->
            <div v-if="success" class="rounded-md bg-green-50 p-4">
              <div class="text-sm text-green-700">✅ Llamadas programadas exitosamente</div>
            </div>

            <!-- Submit -->
            <button type="submit" :disabled="loading" class="btn-primary w-full text-white">
              {{ loading ? 'Procesando...' : 'Enviar Llamadas' }}
            </button>
          </form>
        </div>
      </div>

      <!-- Stats -->
      <div class="space-y-4">
        <div class="card">
          <p class="text-gray-600 text-sm">Costo por Llamada</p>
          <p class="text-3xl font-bold text-gray-900">$ 0.07</p>
        </div>

        <div class="card">
          <p class="text-gray-600 text-sm">Costo Total Estimado</p>
          <p class="text-3xl font-bold text-gray-900">$ {{ estimatedCost.toFixed(2) }}</p>
        </div>

        <div class="card">
          <p class="text-gray-600 text-sm">Saldo Disponible</p>
          <p class="text-3xl font-bold text-green-600">{{ credits?.value?.balance || 0 }}</p>
        </div>

        <div class="card bg-blue-50">
          <h3 class="font-semibold text-gray-900 mb-2">ℹ️ Tips</h3>
          <ul class="text-sm text-gray-600 space-y-2">
            <li>• Números: 10 dígitos (local)</li>
            <li>• Con código país: +país + número</li>
            <li>• Máximo 500 caracteres</li>
            <li>• Máximo 100 llamadas por envío</li>
            <li>• Costo: $0.07 por llamada</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth',
})

const form = reactive({
  recipients: '',
  message: '',
  gender: 'female',
  language: 'es-CO',
  campaign: '',
})

const loading = ref(false)
const error = ref<string | null>(null)
const success = ref(false)
const api = useApi()
const { credits, getBalance } = useCredits()

const recipientCount = computed(() => {
  return form.recipients
    .split(/[,\n]/)
    .map(r => r.trim())
    .filter(r => r.length > 0).length
})

// Estimar duración: ~0.5 segundos por palabra
const estimatedDuration = computed(() => {
  const words = form.message.split(/\s+/).filter(w => w.length > 0).length
  return Math.ceil(words * 0.5)
})

const estimatedCost = computed(() => {
  // Asumiendo costo de 0.07 por llamada
  return recipientCount.value * 0.07
})

const sendAudio = async () => {
  if (recipientCount.value === 0) {
    error.value = 'Ingresa al menos un número telefónico'
    return
  }

  if (form.message.trim().length === 0) {
    error.value = 'Ingresa un mensaje'
    return
  }

  if (recipientCount.value > 100) {
    error.value = 'Máximo 100 llamadas por envío'
    return
  }

  if (estimatedCost.value > (credits.value?.balance || 0)) {
    error.value = 'Saldo insuficiente'
    return
  }

  loading.value = true
  error.value = null
  success.value = false

  try {
    const recipients = form.recipients
      .split(/[,\n]/)
      .map(r => r.trim())
      .filter(r => r.length > 0)

    const response = await api.post(
      recipientCount.value === 1 ? '/audio/call' : '/audio/bulk',
      {
        phone: recipients[0],
        phones: recipients,
        message: form.message,
        gender: form.gender,
        language: form.language,
        campaign: form.campaign || undefined,
      }
    )

    // Reset form
    form.recipients = ''
    form.message = ''
    form.gender = 'female'
    form.language = 'es-CO'
    form.campaign = ''
    success.value = true

    // Clear success message after 3 seconds
    setTimeout(() => {
      success.value = false
    }, 3000)

    // Refresh credits
    await getBalance()
  } catch (err: any) {
    error.value = err.response?.data?.error || 'Error al enviar llamadas'
  } finally {
    loading.value = false
  }
}
</script>
