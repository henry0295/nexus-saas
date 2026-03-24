export const useAuth = () => {
  const auth = useAuthStore()
  const api = useApi()
  const router = useRouter()

  const register = async (
    companyName: string,
    email: string,
    password: string,
    passwordConfirmation: string
  ) => {
    try {
      const response = await api.post('/auth/register', {
        company_name: companyName,
        email,
        password,
        password_confirmation: passwordConfirmation,
      })

      auth.setUser(response.data.user)
      auth.setToken(response.data.token)
      auth.setTenant(response.data.tenant)

      await router.push('/dashboard')
      return { success: true }
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Error en registro',
      }
    }
  }

  const login = async (email: string, password: string) => {
    try {
      const response = await api.post('/auth/login', {
        email,
        password,
      })

      auth.setUser(response.data.user)
      auth.setToken(response.data.token)
      auth.setTenant(response.data.tenant)

      await router.push('/dashboard')
      return { success: true }
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.message || 'Credenciales inválidas',
      }
    }
  }

  const logout = async () => {
    try {
      await api.post('/auth/logout')
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      auth.logout()
      await router.push('/auth/login')
    }
  }

  const getMe = async () => {
    try {
      const response = await api.get('/auth/me')
      auth.setUser(response.data.user)
      auth.setTenant(response.data.tenant)
      return { success: true, data: response.data }
    } catch (error) {
      auth.logout()
      return { success: false, error }
    }
  }

  return {
    register,
    login,
    logout,
    getMe,
    isAuthenticated: computed(() => auth.isAuthenticated),
    user: computed(() => auth.user),
    tenant: computed(() => auth.tenant),
  }
}
