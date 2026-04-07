import type { AxiosInstance } from 'axios'
import axios from 'axios'
import { useRuntimeConfig } from '#app'
import { useAuthStore } from '~/stores/auth'

let apiClient: AxiosInstance | null = null

export const useApi = () => {
  const config = useRuntimeConfig()
  const auth = useAuthStore()

  if (!apiClient) {
    apiClient = axios.create({
      baseURL: config.public.apiBaseUrl,
      headers: {
        'Content-Type': 'application/json',
      },
    })

    // Interceptor para agregar token
    apiClient.interceptors.request.use((config) => {
      if (auth.token) {
        config.headers.Authorization = `Bearer ${auth.token}`
      }
      return config
    })

    // Interceptor para manejo de errores
    apiClient.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error.response?.status === 401) {
          auth.logout()
          navigateTo('/auth/login')
        }
        return Promise.reject(error)
      }
    )
  }

  return apiClient
}

export const useApiCall = async (method: string, url: string, data?: any) => {
  try {
    const api = useApi()
    const response = await api({
      method,
      url,
      data,
    })
    return { data: response.data, error: null }
  } catch (error: any) {
    return { data: null, error: error.response?.data || error.message }
  }
}
