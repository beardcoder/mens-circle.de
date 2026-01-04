import { defineConfig } from 'vite'
import typo3 from 'vite-plugin-typo3'

export default defineConfig({
  plugins: [typo3()],
  build: {
    target: 'esnext',
    cssMinify: 'lightningcss',
    rollupOptions: {
      input: {
        main: 'packages/sitepackage/Resources/Private/PageView/Assets/main.ts',
      },
    },
  },
  css: {
    transformer: 'lightningcss',
    lightningcss: {
      drafts: {
        customMedia: true,
      },
    },
  },
  server: {
    origin: 'https://mens-circle.ddev.site',
  },
})
