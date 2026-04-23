import { useAuthStore } from '~/stores/auth'

export default defineNuxtRouteMiddleware((to, from) => {
  const auth = useAuthStore()

  // Permitir acceso sin autenticación a rutas públicas de autenticación
  const publicRoutes = ['/auth/login', '/auth/signup']

  if (publicRoutes.includes(to.path)) {
    return
  }

  // Si es la ruta raíz (/), redirigir a dashboard si está autenticado, sino a login
  if (to.path === '/') {
    if (auth.isAuthenticated) {
      return navigateTo('/dashboard')
    } else {
      return navigateTo('/auth/login')
    }
  }

  // Verificar autenticación para otras rutas protegidas
  if (!auth.isAuthenticated) {
    return navigateTo('/auth/login')
  }

  // Verificar permisos de admin
  if (to.path.startsWith('/admin') && !auth.isAdmin) {
    return navigateTo('/dashboard')
  }
})
