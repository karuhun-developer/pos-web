import '../css/app.css'

import { createApp, h, type DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from 'ziggy-js'
import { applyStoredTheme } from '@/lib/theme'

// Tema dipasang sebelum app di-mount supaya tidak ada kedipan terang→gelap.
applyStoredTheme()

createInertiaApp({
  title: (title) => (title ? `${title} · POS Pro` : 'POS Pro'),
  resolve: (name) =>
    resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
    ),
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      // Tanpa plugin ini, route() di dalam <template> tidak pernah ada dan
      // SETIAP halaman mati dengan "route is not a function" — direktif
      // @routes cuma menyediakan datanya (window.Ziggy), bukan fungsinya.
      .use(ZiggyVue)
      .mount(el)
  },
  progress: { color: '#2a78d6' },
})
