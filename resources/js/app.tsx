import './bootstrap'
import { createRoot } from 'react-dom/client'
import { createInertiaApp } from '@inertiajs/react'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ThemeProvider } from '@/components/theme-provider'
import './utils/chart'
import '../css/index.css'
// import '../sass/app.scss'

createInertiaApp({
  resolve: name =>
    resolvePageComponent(`./Pages/${name}.tsx`, import.meta.glob('./Pages/**/*.tsx')),
  setup({ el, App, props }) {
    const root = createRoot(el)
    root.render(
      <ThemeProvider attribute="class" defaultTheme="dark" enableSystem>
        <App {...props} />
      </ThemeProvider>
    )
  },
})