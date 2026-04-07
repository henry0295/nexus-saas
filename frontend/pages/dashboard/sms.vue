<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Enviar SMS</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Form -->
      <div class="lg:col-span-2">
        <div class="card">
          <form @submit.prevent="sendSms" class="space-y-6">
            <!-- Recipients -->
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Destinatarios (uno por línea o separados por comas)
              </label>
              <textarea
                v-model="form.recipients"
                rows="5"
                class="input-field mt-1"
                placeholder="+573001234567&#10;+573009876543"
              />
              <p class="mt-2 text-sm text-gray-500">
                {{ recipientCount }} destinatario(s)
              </p>
            </div>

            <!-- Message -->
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Mensaje
              </label>
              <textarea
                v-model="form.message"
                rows="4"
                maxlength="1000"
                class="input-field mt-1"
                placeholder="Escribe tu mensaje aquí..."
              />
              <p class="mt-2 text-sm text-gray-500">
                {{ form.message.length }}/1000 caracteres | {{ estimatedParts }} parte(s)
              </p>
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
                placeholder="ej: Promo-Marzo"
              />
            </div>

            <!-- Error -->
            <div v-if="error" class="rounded-md bg-red-50 p-4">
              <div class="text-sm text-red-700">{{ error }}</div>
            </div>

            <!-- Submit -->
            <button type="submit" :disabled="loading" class="btn-primary w-full text-white">
              {{ loading ? 'Enviando...' : 'Enviar SMS' }}
            </button>
          </form>
        </div>
      </div>

      <!-- Stats -->
      <div class="space-y-4">
        <div class="card">
          <p class="text-gray-600 text-sm">Costo Estimado</p>
          <p class="text-3xl font-bold text-gray-900">$ {{ estimatedCost.toFixed(3) }}</p>
        </div>

        <div class="card">
          <p class="text-gray-600 text-sm">Saldo Disponible</p>
          <p class="text-3xl font-bold text-green-600">{{ credits?.balance || 0 }}</p>
        </div>

        <div class="card bg-blue-50">
          <h3 class="font-semibold text-gray-900 mb-2">ℹ️ Tips</h3>
          <ul class="text-sm text-gray-600 space-y-2">
            <li>• SMS locales: 10 dígitos</li>
            <li>• SMS internacionales: +país + número</li>
            <li>• Máximo 1000 SMS por envío</li>
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
  campaign: '',
})

const loading = ref(false)
const error = ref<string | null>(null)
const api = useApi()
const { credits, getBalance } = useCredits()

const recipientCount = computed(() => {
  return form.recipients
    .split(/[,\n]/)
    .map(r => r.trim())
    .filter(r => r.length > 0).length
})

const estimatedParts = computed(() => {
  return Math.ceil(form.message.length / 160)
})

const estimatedCost = computed(() => {
  // Asumiendo costo de 0.026 por SMS
  return recipientCount.value * estimatedParts.value * 0.026
})

const sendSms = async () => {
  if (recipientCount.value === 0) {
    error.value = 'Ingresa al menos un destinatario'
    return
  }

  if (form.message.trim().length === 0) {
    error.value = 'Ingresa un mensaje'
    return
  }

  if (estimatedCost.value > (credits?.balance || 0)) {
    error.value = 'Saldo insuficiente'
    return
  }

  loading.value = true
  error.value = null

  try {
    const recipients = form.recipients
      .split(/[,\n]/)
      .map(r => r.trim())
      .filter(r => r.length > 0)

    const response = await api.post(
      recipientCount.value === 1 ? '/sms/send' : '/sms/bulk',
      {
        recipient: recipients[0],
        recipients: recipients,
        message: form.message,
        campaign: form.campaign || undefined,
      }
    )

    // Reset form
    form.recipients = ''
    form.message = ''
    form.campaign = ''

    // Refresh credits
    await getBalance()
  } catch (err: any) {
    error.value = err.response?.data?.error || 'Error al enviar SMS'
  } finally {
    loading.value = false
  }
}
</script>
