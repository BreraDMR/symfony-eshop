import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// The SPA is served under /spa/ by Symfony and built into public/spa. During
// development, `npm run dev` proxies API and image requests to the PHP app.
export default defineConfig({
    base: '/spa/',
    plugins: [react()],
    build: {
        outDir: '../public/spa',
        emptyOutDir: true,
    },
    server: {
        port: 5173,
        proxy: {
            '/api': 'http://localhost:8080',
            '/images': 'http://localhost:8080',
        },
    },
})
