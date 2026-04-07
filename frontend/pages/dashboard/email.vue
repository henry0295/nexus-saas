<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Enviar Correos</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Form -->
      <div class="lg:col-span-2">
        <div class="card">
          <form @submit.prevent="sendEmail" class="space-y-6">
            <!-- Recipients -->
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Destinatarios (uno por línea o separados por comas)
              </label>
              <textarea
                v-model="form.recipients"
                rows="5"
                class="input-field mt-1"
                placeholder="ejemplo@correo.com&#10;otro@correo.com"
              />
              <p class="mt-2 text-sm text-gray-500">
                {{ recipientCount }} destinatario(s)
              </p>
            </div>

            <!-- Subject -->
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Asunto
              </label>
              <input
                v-model="form.subject"
                type="text"
                maxlength="100"
                class="input-field mt-1"
                placeholder="Asunto del correo..."
              />
              <p class="mt-2 text-sm text-gray-500">
                {{ form.subject.length }}/100 caracteres
              </p>
            </div>

            <!-- Body -->
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Mensaje
              </label>
              <textarea
                v-model="form.body"
                rows="6"
                maxlength="5000"
                class="input-field mt-1"
                placeholder="Escribe el contenido de tu correo aquí..."
              />
              <p class="mt-2 text-sm text-gray-500">
                {{ form.body.length }}/5000 caracteres
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
                placeholder="ej: Newsletter-Marzo"
              />
            </div>

            <!-- Error -->
            <div v-if="error" class="rounded-md bg-red-50 p-4">
              <div class="text-sm text-red-700">{{ error }}</div>
            </div>

            <!-- Success -->
            <div v-if="success" class="rounded-md bg-green-50 p-4">
              <div class="text-sm text-green-700">✅ Correos enviados exitosamente</div>
            </div>

            <!-- Submit -->
            <button type="submit" :disabled="loading" class="btn-primary w-full text-white">
              {{ loading ? 'Enviando...' : 'Enviar Correos' }}
            </button>
          </form>
        </div>
      </div>

      <!-- Stats -->
      <div class="space-y-4">
        <div class="card">
          <p class="text-gray-600 text-sm">Costo por Correo</p>
          <p class="text-3xl font-bold text-gray-900">$ 0.001</p>
        </div>

        <div class="card">
          <p class="text-gray-600 text-sm">Costo Total Estimado</p>
          <p class="text-3xl font-bold text-gray-900">$ {{ estimatedCost.toFixed(3) }}</p>
        </div>

        <div class="card">
          <p class="text-gray-600 text-sm">Saldo Disponible</p>
          <p class="text-3xl font-bold text-green-600">{{ credits?.balance || 0 }}</p>
        </div>

        <div class="card bg-blue-50">
          <h3 class="font-semibold text-gray-900 mb-2">ℹ️ Tips</h3>
          <ul class="text-sm text-gray-600 space-y-2">
            <li>• Formato: usuario@dominio.com</li>
            <li>• Máximo 5000 caracteres</li>
            <li>• Máximo 1000 correos por envío</li>
            <li>• Costo: $0.001 por correo</li>
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
  subject: '',
  body: '',
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
    .filter(r => r.length > 0 && r.includes('@')).length
})

const estimatedCost = computed(() => {
  // Asumiendo costo de 0.001 por email
  return recipientCount.value * 0.001
})

const sendEmail = async () => {
  if (recipientCount.value === 0) {
    error.value = 'Ingresa al menos un destinatario válido'
    return
  }

  if (form.subject.trim().length === 0) {
    error.value = 'Ingresa un asunto'
    return
  }

  if (form.body.trim().length === 0) {
    error.value = 'Ingresa el mensaje'
    return
  }

  if (estimatedCost.value > (credits?.balance || 0)) {
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
      .filter(r => r.length > 0 && r.includes('@'))

    const response = await api.post(
      recipientCount.value === 1 ? '/email/send' : '/email/bulk',
      {
        recipient: recipients[0],
        recipients: recipients,
        subject: form.subject,
        body: form.body,
        campaign: form.campaign || undefined,
      }
    )

    // Reset form
    form.recipients = ''
    form.subject = ''
    form.body = ''
    form.campaign = ''
    success.value = true

    // Clear success message after 3 seconds
    setTimeout(() => {
      success.value = false
    }, 3000)

    // Refresh credits
    await getBalance()
  } catch (err: any) {
    error.value = err.response?.data?.error || 'Error al enviar correos'
  } finally {
    loading.value = false
  }
}
</script>
