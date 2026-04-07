export default defineNuxtConfig({
  devtools: { enabled: true },
  modules: ['@pinia/nuxt', '@nuxtjs/tailwindcss'],

  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.NUXT_PUBLIC_API_BASE_URL || 'http://localhost:8000/api',
    },
  },

  css: ['~/assets/css/main.css'],

  app: {
    head: {
      title: 'NexusSaaS - Plataforma de Comunicación Empresarial',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'description', content: 'NexusSaaS - SMS, Email y Audio para tu negocio' },
      ],
    },
  },

  ssr: true,
  routeRules: {
    '/api/**': { cache: { maxAge: 60 * 10 } },
  },

  typescript: {
    strict: true,
    typeCheck: true,
  },

  components: {
    dirs: [
      {
        path: '~/components',
        pathPrefix: false,
      },
    ],
  },

  nitro: {
    prerender: {
      crawlLinks: false,
      routes: [],
      ignore: ['/sitemap.xml', '/rss.xml'],
    },
  },
})
