import tailwindcss from '@tailwindcss/vite';

export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: false },
  modules: ['@pinia/nuxt'],
  css: ['~/assets/css/main.css'],
  vite: { plugins: [tailwindcss()] },
  components: [{ path: '~/components', pathPrefix: false }],
  runtimeConfig: {
    public: {
      // URL REST API Laravel. Nilai bisa ditimpa lewat env NUXT_PUBLIC_API_BASE / NUXT_PUBLIC_API_ORIGIN.
      apiBase: 'http://localhost:4000/api',
      apiOrigin: 'http://localhost:4000',
    },
  },
  app: {
    pageTransition: { name: 'page', mode: 'out-in' },
    head: {
      htmlAttrs: { lang: 'id' },
      title: 'Mahapreneur — Marketplace Bisnis Mahasiswa',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        {
          name: 'description',
          content:
            'Mahapreneur adalah marketplace mahasiswa: belanja produk dan jasa dari toko mahasiswa lain, atau buka tokomu sendiri dan antar pesanan langsung ke pembeli.',
        },
        { name: 'theme-color', content: '#102C40' },
        { property: 'og:title', content: 'Mahapreneur — Marketplace Bisnis Mahasiswa' },
        { property: 'og:description', content: 'Belanja produk dan jasa dari toko mahasiswa. Diantar langsung oleh sellernya.' },
        { property: 'og:image', content: '/og-image.png' },
        { property: 'og:type', content: 'website' },
      ],
      link: [
        { rel: 'icon', href: '/favicon.ico', sizes: 'any' },
        { rel: 'icon', type: 'image/png', sizes: '32x32', href: '/favicon-32.png' },
        { rel: 'icon', type: 'image/png', sizes: '16x16', href: '/favicon-16.png' },
        { rel: 'apple-touch-icon', sizes: '180x180', href: '/apple-touch-icon.png' },
      ],
    },
  },
});
