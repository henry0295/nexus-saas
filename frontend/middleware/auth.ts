export default defineNuxtRouteMiddleware((to, from) => {
  const auth = useAuthStore()

  // Permitir acceso sin autenticación a rutas públicas
  const publicRoutes = ['/auth/login', '/auth/signup', '/']

  if (publicRoutes.includes(to.path)) {
    return
  }

  // Verificar autenticación
  if (!auth.isAuthenticated) {
    return navigateTo('/auth/login')
  }

  // Verificar permisos de admin
  if (to.path.startsWith('/admin') && !auth.isAdmin) {
    return navigateTo('/dashboard')
  }
})
