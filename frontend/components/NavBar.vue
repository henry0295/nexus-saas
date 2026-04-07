<template>
  <nav class="bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <!-- Logo -->
        <div class="flex items-center">
          <NuxtLink to="/" class="text-2xl font-bold text-blue-600">
            NexusSaaS
          </NuxtLink>
        </div>

        <!-- Menu Desktop -->
        <div class="hidden md:flex md:items-center md:space-x-8">
          <template v-if="auth.isAuthenticated">
            <NuxtLink
              to="/dashboard"
              class="text-gray-600 hover:text-gray-900 transition-colors"
            >
              Dashboard
            </NuxtLink>

            <NuxtLink
              v-if="auth.isAdmin"
              to="/admin"
              class="text-gray-600 hover:text-gray-900 transition-colors"
            >
              Admin
            </NuxtLink>

            <div class="relative group">
              <button
                class="text-gray-600 hover:text-gray-900 transition-colors flex items-center gap-2"
              >
                {{ auth.user?.name }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg hidden group-hover:block">
                <button
                  @click="logout"
                  class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 transition-colors"
                >
                  Cerrar Sesión
                </button>
              </div>
            </div>
          </template>

          <template v-else>
            <NuxtLink
              to="/auth/login"
              class="text-gray-600 hover:text-gray-900 transition-colors"
            >
              Iniciar Sesión
            </NuxtLink>

            <NuxtLink
              to="/auth/signup"
              class="btn-primary"
            >
              Registrarse
            </NuxtLink>
          </template>
        </div>

        <!-- Mobile Menu Button -->
        <div class="flex md:hidden items-center">
          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="inline-flex items-center justify-center p-2 rounded-md text-gray-600 hover:text-gray-900"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Mobile Menu -->
      <div v-if="mobileMenuOpen" class="md:hidden pb-4">
        <template v-if="auth.isAuthenticated">
          <NuxtLink
            to="/dashboard"
            class="block px-3 py-2 text-gray-600 hover:bg-gray-100"
          >
            Dashboard
          </NuxtLink>

          <NuxtLink
            v-if="auth.isAdmin"
            to="/admin"
            class="block px-3 py-2 text-gray-600 hover:bg-gray-100"
          >
            Admin
          </NuxtLink>

          <button
            @click="logout"
            class="w-full text-left px-3 py-2 text-gray-600 hover:bg-gray-100"
          >
            Cerrar Sesión
          </button>
        </template>

        <template v-else>
          <NuxtLink
            to="/auth/login"
            class="block px-3 py-2 text-gray-600 hover:bg-gray-100"
          >
            Iniciar Sesión
          </NuxtLink>

          <NuxtLink
            to="/auth/signup"
            class="block px-3 py-2 text-blue-600 font-medium hover:bg-gray-100"
          >
            Registrarse
          </NuxtLink>
        </template>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { useAuth } from '~/composables/useAuth'

const auth = useAuthStore()
const mobileMenuOpen = ref(false)
const { logout } = useAuth()
</script>
