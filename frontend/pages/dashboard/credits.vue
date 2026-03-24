<template>
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Comprar Créditos</h1>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
      <div
        v-for="pkg in packages"
        :key="pkg.id"
        class="card hover:shadow-lg transition-shadow cursor-pointer"
        :class="pkg.id === selectedPackage ? 'ring-2 ring-blue-500' : ''"
        @click="selectPackage(pkg.id)"
      >
        <div class="text-center">
          <h3 class="text-xl font-bold text-gray-900">{{ pkg.credits }}</h3>
          <p class="text-gray-600 text-sm">Créditos</p>

          <div class="my-4 text-3xl font-bold text-blue-600">
            ${{ pkg.price.toFixed(2) }}
          </div>

          <p v-if="pkg.discount > 0" class="text-sm text-green-600 font-semibold mb-4">
            ¡{{ (pkg.discount * 100).toFixed(0) }}% Descuento!
          </p>

          <button
            @click.stop="purchase(pkg.id)"
            :disabled="loading"
            class="btn-primary w-full text-white"
          >
            {{ loading ? 'Procesando...' : 'Comprar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Current Balance -->
    <div class="card mb-8">
      <h2 class="text-xl font-bold text-gray-900 mb-4">Saldo Actual</h2>
      <div class="flex items-center justify-between">
        <div>
          <p class="text-gray-600">Créditos disponibles</p>
          <p class="text-4xl font-bold text-green-600">{{ credits.balance || 0 }}</p>
        </div>
        <div class="text-5xl">💰</div>
      </div>
    </div>

    <!-- Transaction History -->
    <div class="card">
      <h2 class="text-xl font-bold text-gray-900 mb-4">Historial de Transacciones</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Fecha</th>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Tipo</th>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Cantidad</th>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Monto</th>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="transactions.length === 0">
              <td colspan="5" class="px-4 py-4 text-center text-gray-600">
                No hay transacciones
              </td>
            </tr>
            <tr v-for="tx in transactions" :key="tx.id" class="border-t">
              <td class="px-4 py-4 text-sm text-gray-900">
                {{ new Date(tx.created_at).toLocaleDateString() }}
              </td>
              <td class="px-4 py-4 text-sm text-gray-900">{{ tx.type }}</td>
              <td class="px-4 py-4 text-sm text-gray-900">{{ tx.amount }}</td>
              <td class="px-4 py-4 text-sm text-gray-900">${{ tx.price }}</td>
              <td class="px-4 py-4 text-sm">
                <span
                  class="px-3 py-1 rounded-full text-xs font-semibold"
                  :class="{
                    'bg-green-100 text-green-800': tx.status === 'completed',
                    'bg-yellow-100 text-yellow-800': tx.status === 'pending',
                    'bg-red-100 text-red-800': tx.status === 'failed',
                  }"
                >
                  {{ tx.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="fixed bottom-4 right-4 bg-red-50 border border-red-200 rounded-lg p-4 max-w-md">
      <p class="text-red-700">{{ error }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth',
})

const { packages, credits, purchaseCredits, getTransactions } = useCredits()
const selectedPackage = ref('growth')
const loading = ref(false)
const error = ref<string | null>(null)
const transactions = ref<any[]>([])

const selectPackage = (id: string) => {
  selectedPackage.value = id
}

const purchase = async (packageId: string) => {
  loading.value = true
  error.value = null

  const result = await purchaseCredits(packageId, 'credit_card')

  if (result.success) {
    // En un caso real, redireccionaría a Stripe/PayU
    // por ahora mostramos el mensaje de éxito
    error.value = null
  } else {
    error.value = result.error
  }

  loading.value = false
}

onMounted(async () => {
  const txData = await getTransactions()
  if (txData?.data) {
    transactions.value = txData.data
  }
})
</script>
