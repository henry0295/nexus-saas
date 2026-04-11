import { defineStore } from 'pinia'

interface User {
  id: number
  email: string
  name: string
  role: string
  tenant_id: number
}

interface Tenant {
  id: number
  name: string
  email: string
  status: string
  plan: string
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as User | null,
    token: null as string | null,
    tenant: null as Tenant | null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    isSuperAdmin: (state) => state.user?.role === 'superadmin',
    isAdmin: (state) => state.user?.role === 'admin' || state.user?.role === 'superadmin',
  },

  actions: {
    setUser(user: User) {
      this.user = user
    },

    setToken(token: string) {
      this.token = token
      if (typeof localStorage !== 'undefined') {
        localStorage.setItem('auth_token', token)
      }
    },

    setTenant(tenant: Tenant) {
      this.tenant = tenant
    },

    logout() {
      this.user = null
      this.token = null
      this.tenant = null
      if (typeof localStorage !== 'undefined') {
        localStorage.removeItem('auth_token')
      }
    },

    hydrate() {
      const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null
      if (token) {
        this.token = token
      }
    },
  },
})

