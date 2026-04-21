<template>
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">SMS - Centro de Control</h1>

    <!-- Tabs -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
      <button 
        v-for="tab in tabs"
        :key="tab.id"
        @click="activeTab = tab.id"
        :class="[
          'px-4 py-2 rounded-lg font-medium transition-all',
          activeTab === tab.id
            ? 'bg-blue-600 text-white shadow-md'
            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
        ]"
      >
        {{ tab.icon }} {{ tab.name }}
      </button>
    </div>

    <!-- TAB 1: Enviar SMS -->
    <div v-if="activeTab === 'send'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
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

            <!-- Success -->
            <div v-if="success" class="rounded-md bg-green-50 p-4">
              <div class="text-sm text-green-700">✅ SMS enviados exitosamente</div>
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
          <p class="text-gray-600 text-sm">Costo por SMS</p>
          <p class="text-3xl font-bold text-gray-900">$ 0.026</p>
        </div>

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
            <li>• Costo: $0.026 por SMS</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- TAB 2: Plantillas -->
    <div v-if="activeTab === 'templates'" class="card">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-900">Plantillas de SMS</h2>
        <button @click="showTemplateModal = true" class="btn-primary text-white">
          ➕ Nueva Plantilla
        </button>
      </div>

      <div v-if="templates.length === 0" class="text-center py-12">
        <p class="text-gray-500 mb-4">No tienes plantillas creadas aún</p>
        <button @click="showTemplateModal = true" class="btn-secondary">
          Crear Primera Plantilla
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-for="template in templates" :key="template.id" class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
          <h3 class="font-semibold text-gray-900 mb-2">{{ template.name }}</h3>
          <p class="text-sm text-gray-600 mb-2 line-clamp-2">{{ template.message }}</p>
          <p class="text-xs text-gray-500 mb-4">{{ template.message.length }}/1000 caracteres | {{ Math.ceil(template.message.length / 160) }} parte(s)</p>
          <div class="flex gap-2">
            <button @click="useTemplate(template)" class="btn-secondary text-sm">
              Usar
            </button>
            <button @click="editTemplate(template)" class="btn-secondary text-sm">
              Editar
            </button>
            <button @click="deleteTemplate(template.id)" class="btn-secondary text-sm text-red-600">
              Eliminar
            </button>
          </div>
        </div>
      </div>

      <!-- Template Modal -->
      <div v-if="showTemplateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6">
          <h3 class="text-xl font-bold text-gray-900 mb-4">
            {{ editingTemplate ? 'Editar Plantilla' : 'Nueva Plantilla' }}
          </h3>

          <form @submit.prevent="saveTemplate" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Nombre</label>
              <input
                v-model="templateForm.name"
                type="text"
                class="input-field mt-1"
                placeholder="ej: Recordatorio de Cita"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Mensaje</label>
              <textarea
                v-model="templateForm.message"
                rows="4"
                maxlength="1000"
                class="input-field mt-1"
                placeholder="Contenido del SMS"
              />
              <p class="mt-2 text-sm text-gray-500">
                {{ templateForm.message.length }}/1000 caracteres | {{ Math.ceil(templateForm.message.length / 160) }} parte(s)
              </p>
            </div>

            <div class="bg-blue-50 p-3 rounded-lg">
              <p class="text-sm text-blue-800">💡 Puedes usar variables como {{nombre}}, {{fecha}}, {{hora}}</p>
            </div>

            <div class="flex gap-2 justify-end">
              <button type="button" @click="showTemplateModal = false" class="btn-secondary">
                Cancelar
              </button>
              <button type="submit" class="btn-primary text-white">
                {{ editingTemplate ? 'Actualizar' : 'Crear' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth',
})

const activeTab = ref('send')
const showTemplateModal = ref(false)
const editingTemplate = ref(null)

const tabs = ref([
  { id: 'send', name: 'Enviar', icon: '📱' },
  { id: 'templates', name: 'Plantillas', icon: '📋' },
])

const form = reactive({
  recipients: '',
  message: '',
  campaign: '',
})

const templateForm = reactive({
  name: '',
  message: '',
})

const loading = ref(false)
const error = ref<string | null>(null)
const success = ref(false)
const api = useApi()
const { credits } = useCredits()

const templates = ref([])

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
  // Costo de 0.026 por SMS
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

  if (estimatedCost.value > (credits.value?.balance || 0)) {
    error.value = 'Saldo insuficiente'
    return
  }

  loading.value = true
  error.value = null
  success.value = false

  try {
    // Simular envío
    setTimeout(() => {
      success.value = true
      form.recipients = ''
      form.message = ''
      form.campaign = ''
      loading.value = false
    }, 1000)
  } catch (err: any) {
    error.value = 'Error al enviar SMS'
    loading.value = false
  }
}

const saveTemplate = async () => {
  if (!templateForm.name || !templateForm.message) {
    return
  }

  try {
    const endpoint = editingTemplate.value
      ? `/sms-templates/${editingTemplate.value}`
      : '/sms-templates'
    
    const method = editingTemplate.value ? 'put' : 'post'
    
    const response = await api[method](endpoint, {
      name: templateForm.name,
      message: templateForm.message,
    })

    if (editingTemplate.value) {
      const index = templates.value.findIndex(t => t.id === editingTemplate.value)
      templates.value[index] = response.data
    } else {
      templates.value.push(response.data)
    }

    templateForm.name = ''
    templateForm.message = ''
    editingTemplate.value = null
    showTemplateModal.value = false
  } catch (err: any) {
    console.error('Error saving template:', err)
  }
}

const useTemplate = (template: any) => {
  form.message = template.message
  activeTab.value = 'send'
}

const editTemplate = (template: any) => {
  editingTemplate.value = template.id
  templateForm.name = template.name
  templateForm.message = template.message
  showTemplateModal.value = true
}

const deleteTemplate = async (id: number) => {
  try {
    await api.delete(`/sms-templates/${id}`)
    templates.value = templates.value.filter(t => t.id !== id)
  } catch (err: any) {
    console.error('Error deleting template:', err)
  }
}

// Load data from API on component mount
onMounted(async () => {
  try {
    const response = await api.get('/sms-templates')
    templates.value = response.data || []
  } catch (err: any) {
    console.error('Error loading SMS templates:', err)
  }
})
</script>
