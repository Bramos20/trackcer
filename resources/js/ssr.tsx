import { createInertiaApp } from '@inertiajs/react'
import createServer from '@inertiajs/react/server'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { renderToString } from 'react-dom/server'
import { ThemeProvider } from '@/components/theme-provider'
import './utils/chart'
import '../css/index.css'
// import '../sass/app.scss'

createServer((page) =>
  createInertiaApp({
    page,
    render: renderToString,
    resolve: (name) =>
      resolvePageComponent(`./Pages/${name}.tsx`, import.meta.glob('./Pages/**/*.tsx')),
    setup: ({ App, props }) => (
      <ThemeProvider attribute="class" defaultTheme="system" enableSystem>
        <App {...props} />
      </ThemeProvider>
    ),
  })
)