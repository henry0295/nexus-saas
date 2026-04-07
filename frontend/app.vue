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

onMounted(() => {
  auth.hydrate()
})
</script>

<style scoped>
main {
  min-height: calc(100vh - 64px);
}
</style>
