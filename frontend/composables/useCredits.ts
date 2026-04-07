interface CreditsData {
  balance: number
  transactions?: any[]
}

export const useCredits = () => {
  const apiInstance = useApi()
  const credits = ref<CreditsData | null>(null)
  const packages = ref<any[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const getBalance = async () => {
    loading.value = true
    try {
      const response = await apiInstance.get('/credits/balance')
      credits.value = response.data
      error.value = null
      return response.data
    } catch (err: any) {
      error.value = err.message
      return null
    } finally {
      loading.value = false
    }
  }

  const getPackages = async () => {
    loading.value = true
    try {
      const response = await apiInstance.get('/credits/packages')
      packages.value = response.data.packages
      error.value = null
      return response.data.packages
    } catch (err: any) {
      error.value = err.message
      return []
    } finally {
      loading.value = false
    }
  }

  const purchaseCredits = async (packageId: string, paymentMethod?: string) => {
    loading.value = true
    try {
      const response = await apiInstance.post('/credits/purchase', {
        package_id: packageId,
        payment_method: paymentMethod || 'credit_card',
      })
      error.value = null
      return { success: true, data: response.data }
    } catch (err: any) {
      error.value = err.response?.data?.error || err.message
      return { success: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  const getTransactions = async (page: number = 1) => {
    loading.value = true
    try {
      const response = await apiInstance.get(`/credits/transactions?page=${page}`)
      error.value = null
      return response.data
    } catch (err: any) {
      error.value = err.message
      return null
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    getBalance()
    getPackages()
  })

  return {
    credits,
    packages,
    loading,
    error,
    getBalance,
    getPackages,
    purchaseCredits,
    getTransactions,
  }
}
