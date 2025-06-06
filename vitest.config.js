/// <reference types="vitest" />
import { defineConfig } from 'vite'

export default defineConfig({
  test: {
    environment: 'happy-dom',
    globals: true,
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
      exclude: [
        'node_modules/',
        'vendor/',
        'tests/',
        'coverage/',
        '**/*.config.js',
        '**/*.config.ts'
      ]
    },
    setupFiles: ['./tests/js/setup.js'],
    testTimeout: 10000
  },
  resolve: {
    alias: {
      '@': '/resources/js'
    }
  }
}) 