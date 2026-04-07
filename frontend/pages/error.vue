<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 to-gray-800">
    <div class="text-center px-4">
      <div class="mb-8">
        <h1 class="text-8xl font-bold text-red-500 mb-4">{{ error.statusCode || '500' }}</h1>
        <h2 class="text-3xl font-semibold text-white mb-4">{{ error.statusMessage || 'Error' }}</h2>
        <p class="text-gray-400 text-lg mb-8">{{ error.message || 'Something went wrong' }}</p>
      </div>

      <div class="space-y-4">
        <NuxtLink
          to="/"
          class="inline-block px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-200"
        >
          Go to Home
        </NuxtLink>
        
        <button
          @click="handleError"
          class="ml-4 px-8 py-3 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-lg transition duration-200"
        >
          Clear Error & Retry
        </button>
      </div>

      <div v-if="isDev" class="mt-12 p-6 bg-gray-800 rounded-lg text-left max-w-2xl mx-auto">
        <h3 class="text-yellow-400 font-bold mb-4">Development Info:</h3>
        <pre class="text-gray-300 text-sm overflow-auto max-h-96">{{ JSON.stringify(error, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { NuxtError } from '#app'
import { clearError } from '#app'

const props = defineProps({
  error: {
    type: Object as () => Partial<NuxtError>,
    required: true
  }
})

const isDev = process.env.NODE_ENV === 'development'

const handleError = () => {
  clearError({ redirect: '/' })
}
</script>
