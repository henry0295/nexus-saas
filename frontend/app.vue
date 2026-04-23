<template>
  <div>
    <NavBar />
    <main>
      <NuxtPage />
    </main>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

// Hydrate auth store on app load
const auth = useAuthStore()

// Hydrate immediately on client side
if (process.client) {
  auth.hydrate()
}

// Also hydrate on mount for SSR safety
onMounted(() => {
  auth.hydrate()
})
</script>

<style scoped>
main {
  min-height: calc(100vh - 64px);
}
</style>
