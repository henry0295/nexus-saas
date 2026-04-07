<template>
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Admin Panel</h1>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="card">
        <p class="text-gray-600 text-sm">Total de Tenants</p>
        <p class="text-3xl font-bold text-gray-900">-</p>
        <p class="text-xs text-gray-500 mt-2">Actualizar después de implementar</p>
      </div>

      <div class="card">
        <p class="text-gray-600 text-sm">Activos</p>
        <p class="text-3xl font-bold text-green-600">-</p>
      </div>

      <div class="card">
        <p class="text-gray-600 text-sm">Trial</p>
        <p class="text-3xl font-bold text-blue-600">-</p>
      </div>

      <div class="card">
        <p class="text-gray-600 text-sm">Suspendidos</p>
        <p class="text-3xl font-bold text-red-600">-</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-8">
      <nav class="flex space-x-8" aria-label="Tabs">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            activeTab === tab.id
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap border-b-2 font-medium text-sm py-4 px-1',
          ]"
        >
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <!-- Tenants List -->
    <div v-if="activeTab === 'tenants'" class="card">
      <h2 class="text-xl font-bold text-gray-900 mb-4">Todos los Tenants</h2>
      <div class="text-center py-8 text-gray-500">
        Cargando datos... (Conectar a API /admin/tenants)
      </div>
    </div>

    <!-- Pricing Rules -->
    <div v-if="activeTab === 'pricing'" class="space-y-6">
      <div class="card">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Reglas de Pricing</h2>

        <table class="min-w-full">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Canal</th>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Costo AWS</th>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Margen %</th>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Precio Venta</th>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="rule in pricingRules" :key="rule.id" class="border-t">
              <td class="px-4 py-4 text-sm font-medium text-gray-900">{{ rule.channel }}</td>
              <td class="px-4 py-4 text-sm text-gray-900">${{ rule.aws_cost }}</td>
              <td class="px-4 py-4 text-sm text-gray-900">{{ rule.margin_percentage }}%</td>
              <td class="px-4 py-4 text-sm font-semibold text-green-600">${{ rule.selling_price }}</td>
              <td class="px-4 py-4 text-sm">
                <button class="text-blue-600 hover:text-blue-800 font-medium">
                  Editar
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="mt-6 pt-6 border-t">
          <button class="btn-primary text-white">
            Agregar Regla
          </button>
        </div>
      </div>
    </div>

    <!-- Audit Logs -->
    <div v-if="activeTab === 'audit'" class="card">
      <h2 class="text-xl font-bold text-gray-900 mb-4">Logs de Auditoría</h2>
      <div class="text-center py-8 text-gray-500">
        Cargando logs... (Conectar a API /admin/audit-logs)
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  middleware: 'auth',
})

const auth = useAuthStore()

// Verificar que es admin
if (!auth.isAdmin) {
  navigateTo('/dashboard')
}

const activeTab = ref('overview')
const pricingRules = ref([
  {
    id: 1,
    channel: 'sms',
    aws_cost: 0.02,
    margin_percentage: 30,
    selling_price: 0.026,
  },
  {
    id: 2,
    channel: 'email',
    aws_cost: 0.0001,
    margin_percentage: 900,
    selling_price: 0.001,
  },
  {
    id: 3,
    channel: 'audio',
    aws_cost: 0.05,
    margin_percentage: 40,
    selling_price: 0.07,
  },
])

const tabs = [
  { id: 'overview', name: 'Resumen' },
  { id: 'tenants', name: 'Tenants' },
  { id: 'pricing', name: 'Pricing' },
  { id: 'audit', name: 'Auditoría' },
]
</script>
