<template>
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Email - Centro de Control</h1>

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

    <!-- TAB 1: Enviar Correos -->
    <div v-if="activeTab === 'send'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
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

    <!-- TAB 2: Plantillas -->
    <div v-if="activeTab === 'templates'" class="card">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-900">Plantillas de Email</h2>
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
          <p class="text-sm text-gray-600 mb-4 line-clamp-3">{{ template.body }}</p>
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
                placeholder="ej: Newsletter Marzo"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Asunto</label>
              <input
                v-model="templateForm.subject"
                type="text"
                class="input-field mt-1"
                placeholder="Asunto del correo"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Contenido</label>
              <textarea
                v-model="templateForm.body"
                rows="6"
                class="input-field mt-1"
                placeholder="Contenido de la plantilla"
              />
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

    <!-- TAB 3: Remitentes -->
    <div v-if="activeTab === 'senders'" class="card">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-900">Remitentes Verificados</h2>
        <button @click="showSenderModal = true" class="btn-primary text-white">
          ➕ Nuevo Remitente
        </button>
      </div>

      <div v-if="senders.length === 0" class="text-center py-12">
        <p class="text-gray-500 mb-4">No tienes remitentes verificados</p>
        <button @click="showSenderModal = true" class="btn-secondary">
          Agregar Remitente
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-for="sender in senders" :key="sender.id" class="border border-gray-200 rounded-lg p-4">
          <div class="flex items-start justify-between">
            <div>
              <h3 class="font-semibold text-gray-900">{{ sender.name }}</h3>
              <p class="text-sm text-gray-600">{{ sender.email }}</p>
              <span :class="[
                'inline-block mt-2 px-2 py-1 rounded text-xs font-medium',
                sender.verified ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
              ]">
                {{ sender.verified ? '✓ Verificado' : '◐ Pendiente' }}
              </span>
            </div>
            <button @click="deleteSender(sender.id)" class="text-red-600 hover:text-red-800">
              🗑️
            </button>
          </div>
        </div>
      </div>

      <!-- Sender Modal -->
      <div v-if="showSenderModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
          <h3 class="text-xl font-bold text-gray-900 mb-4">Nuevo Remitente</h3>

          <form @submit.prevent="saveSender" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Nombre</label>
              <input
                v-model="senderForm.name"
                type="text"
                class="input-field mt-1"
                placeholder="ej: Notificaciones"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Email</label>
              <input
                v-model="senderForm.email"
                type="email"
                class="input-field mt-1"
                placeholder="notificaciones@tudominio.com"
              />
            </div>

            <div class="bg-blue-50 p-3 rounded-lg">
              <p class="text-sm text-blue-800">
                Se enviará un correo de verificación. Debes confirmar el remitente antes de poder usarlo.
              </p>
            </div>

            <div class="flex gap-2 justify-end">
              <button type="button" @click="showSenderModal = false" class="btn-secondary">
                Cancelar
              </button>
              <button type="submit" class="btn-primary text-white">
                Agregar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- TAB 4: Subdominios -->
    <div v-if="activeTab === 'domains'" class="card">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-900">Subdominios para Email</h2>
        <button @click="showDomainModal = true" class="btn-primary text-white">
          ➕ Nuevo Subdominio
        </button>
      </div>

      <div v-if="domains.length === 0" class="text-center py-12">
        <p class="text-gray-500 mb-4">No tienes subdominios configurados</p>
        <button @click="showDomainModal = true" class="btn-secondary">
          Configurar Subdominio
        </button>
      </div>

      <div v-else class="space-y-4">
        <div v-for="domain in domains" :key="domain.id" class="border border-gray-200 rounded-lg p-4">
          <div class="flex items-start justify-between mb-2">
            <div>
              <h3 class="font-semibold text-gray-900">{{ domain.subdomain }}</h3>
              <p class="text-sm text-gray-600">{{ domain.domain }}</p>
            </div>
            <span :class="[
              'px-2 py-1 rounded text-xs font-medium',
              domain.verified ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
            ]">
              {{ domain.verified ? '✓ Verificado' : '◐ Verificando' }}
            </span>
          </div>
          
          <div v-if="!domain.verified" class="mt-3 p-3 bg-yellow-50 rounded text-sm">
            <p class="text-yellow-800 font-medium mb-2">Registros DNS necesarios:</p>
            <code class="bg-white p-2 rounded block text-xs text-gray-600 mb-2">
              {{ domain.dnsRecord }}
            </code>
            <button @click="copyDNS(domain.dnsRecord)" class="text-yellow-600 hover:text-yellow-800 text-sm">
              📋 Copiar registro
            </button>
          </div>

          <button @click="deleteDomain(domain.id)" class="mt-3 text-red-600 hover:text-red-800 text-sm">
            Eliminar
          </button>
        </div>
      </div>

      <!-- Domain Modal -->
      <div v-if="showDomainModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
          <h3 class="text-xl font-bold text-gray-900 mb-4">Nuevo Subdominio</h3>

          <form @submit.prevent="saveDomain" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Subdominio</label>
              <input
                v-model="domainForm.subdomain"
                type="text"
                class="input-field mt-1"
                placeholder="ej: mail"
              />
              <p class="mt-1 text-xs text-gray-500">Resultado: mail.tudominio.com</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Dominio Principal</label>
              <input
                v-model="domainForm.domain"
                type="text"
                class="input-field mt-1"
                placeholder="ej: tudominio.com"
              />
            </div>

            <div class="bg-blue-50 p-3 rounded-lg">
              <p class="text-sm text-blue-800">
                Deberás verificar el dominio agregando registros DNS en tu proveedor.
              </p>
            </div>

            <div class="flex gap-2 justify-end">
              <button type="button" @click="showDomainModal = false" class="btn-secondary">
                Cancelar
              </button>
              <button type="submit" class="btn-primary text-white">
                Agregar
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
const showSenderModal = ref(false)
const showDomainModal = ref(false)
const editingTemplate = ref(null)

const tabs = ref([
  { id: 'send', name: 'Enviar', icon: '📧' },
  { id: 'templates', name: 'Plantillas', icon: '📋' },
  { id: 'senders', name: 'Remitentes', icon: '👤' },
  { id: 'domains', name: 'Subdominios', icon: '🌐' },
])

// Send form
const form = reactive({
  recipients: '',
  subject: '',
  body: '',
  campaign: '',
})

// Template form
const templateForm = reactive({
  name: '',
  subject: '',
  body: '',
})

// Sender form
const senderForm = reactive({
  name: '',
  email: '',
})

// Domain form
const domainForm = reactive({
  subdomain: '',
  domain: '',
})

const loading = ref(false)
const error = ref<string | null>(null)
const success = ref(false)
const api = useApi()
const { credits } = useCredits()

// Mock data
const templates = ref([])
const senders = ref([])
const domains = ref([])

const recipientCount = computed(() => {
  return form.recipients
    .split(/[,\n]/)
    .map(r => r.trim())
    .filter(r => r.length > 0 && r.includes('@')).length
})

const estimatedCost = computed(() => {
  return recipientCount.value * 0.001
})

const sendEmail = async () => {
  if (recipientCount.value === 0) {
    error.value = 'Ingresa al menos un destinatario'
    return
  }

  if (form.subject.trim().length === 0) {
    error.value = 'Ingresa un asunto'
    return
  }

  if (form.body.trim().length === 0) {
    error.value = 'Ingresa el contenido del correo'
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
      form.subject = ''
      form.body = ''
      form.campaign = ''
      loading.value = false
    }, 1000)
  } catch (err: any) {
    error.value = 'Error al enviar correos'
    loading.value = false
  }
}

const saveTemplate = async () => {
  if (!templateForm.name || !templateForm.subject || !templateForm.body) {
    return
  }

  try {
    const endpoint = editingTemplate.value
      ? `/email-templates/${editingTemplate.value}`
      : '/email-templates'
    
    const method = editingTemplate.value ? 'put' : 'post'
    
    const response = await api[method](endpoint, {
      name: templateForm.name,
      subject: templateForm.subject,
      body: templateForm.body,
    })

    if (editingTemplate.value) {
      const index = templates.value.findIndex(t => t.id === editingTemplate.value)
      templates.value[index] = response.data
    } else {
      templates.value.push(response.data)
    }

    templateForm.name = ''
    templateForm.subject = ''
    templateForm.body = ''
    editingTemplate.value = null
    showTemplateModal.value = false
  } catch (err: any) {
    console.error('Error saving template:', err)
  }
}

const useTemplate = (template: any) => {
  form.subject = template.subject
  form.body = template.body
  activeTab.value = 'send'
}

const editTemplate = (template: any) => {
  editingTemplate.value = template.id
  templateForm.name = template.name
  templateForm.subject = template.subject
  templateForm.body = template.body
  showTemplateModal.value = true
}

const deleteTemplate = async (id: number) => {
  try {
    await api.delete(`/email-templates/${id}`)
    templates.value = templates.value.filter(t => t.id !== id)
  } catch (err: any) {
    console.error('Error deleting template:', err)
  }
}

const saveSender = async () => {
  if (!senderForm.name || !senderForm.email) {
    return
  }

  try {
    const response = await api.post('/email-senders', {
      name: senderForm.name,
      email: senderForm.email,
    })

    senders.value.push(response.data)
    senderForm.name = ''
    senderForm.email = ''
    showSenderModal.value = false
  } catch (err: any) {
    console.error('Error saving sender:', err)
  }
}

const deleteSender = async (id: number) => {
  try {
    await api.delete(`/email-senders/${id}`)
    senders.value = senders.value.filter(s => s.id !== id)
  } catch (err: any) {
    console.error('Error deleting sender:', err)
  }
}

const saveDomain = async () => {
  if (!domainForm.subdomain || !domainForm.domain) {
    return
  }

  try {
    const response = await api.post('/email-domains', {
      subdomain: domainForm.subdomain,
      domain: domainForm.domain,
    })

    domains.value.push(response.data)
    domainForm.subdomain = ''
    domainForm.domain = ''
    showDomainModal.value = false
  } catch (err: any) {
    console.error('Error saving domain:', err)
  }
}

const deleteDomain = async (id: number) => {
  try {
    await api.delete(`/email-domains/${id}`)
    domains.value = domains.value.filter(d => d.id !== id)
  } catch (err: any) {
    console.error('Error deleting domain:', err)
  }
}

const copyDNS = (record: string) => {
  navigator.clipboard.writeText(record)
}

// Load data from API on component mount
onMounted(async () => {
  try {
    const [templatesRes, sendersRes, domainsRes] = await Promise.all([
      api.get('/email-templates'),
      api.get('/email-senders'),
      api.get('/email-domains'),
    ])

    templates.value = templatesRes.data || []
    senders.value = sendersRes.data || []
    domains.value = domainsRes.data || []
  } catch (err: any) {
    console.error('Error loading email data:', err)
  }
})
</script>

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
