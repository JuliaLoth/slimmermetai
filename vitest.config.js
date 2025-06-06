/// <reference types="vitest" />
import { defineConfig } from 'vitest/config'
import { resolve } from 'path'

export default defineConfig({
  test: {
    // Test environment setup
    environment: 'jsdom',
    setupFiles: ['./tests/js/setup.js'],
    globals: true,
    
    // Test discovery
    include: [
      'tests/js/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}'
    ],
    exclude: [
      'node_modules',
      'dist',
      'coverage',
      'vendor',
      'public',
      '.git'
    ],

    // Test execution
    timeout: 10000,
    testTimeout: 5000,
    hookTimeout: 10000,
    
    // Coverage configuration
    coverage: {
      provider: 'v8',
      reporter: ['text', 'html', 'clover', 'json'],
      reportsDirectory: './coverage',
      clean: true,
      cleanOnRerun: true,
      
      // Include patterns for coverage
      include: [
        'resources/js/**/*.js',
        'public/assets/js/**/*.js',
        'react-apps/**/*.{js,jsx,ts,tsx}'
      ],
      
      // Exclude patterns
      exclude: [
        'tests/**',
        'node_modules/**',
        'vendor/**',
        'coverage/**',
        '**/node_modules/**',
        '**/dist/**',
        '**/*.config.{js,ts}',
        '**/*.d.ts',
        '**/*.test.{js,ts,jsx,tsx}',
        '**/*.spec.{js,ts,jsx,tsx}',
        '**/mock*/**',
        '**/__mocks__/**',
        '**/__tests__/**'
      ],
      
      // Coverage thresholds
      thresholds: {
        global: {
          branches: 80,
          functions: 80,
          lines: 80,
          statements: 80
        },
        
        // Module-specific thresholds
        'resources/js/auth/': {
          branches: 85,
          functions: 90,
          lines: 85,
          statements: 85
        },
        
        'resources/js/cart/': {
          branches: 80,
          functions: 85,
          lines: 80,
          statements: 80
        },
        
        'resources/js/core/': {
          branches: 90,
          functions: 90,
          lines: 90,
          statements: 90
        }
      },
      
      // Watermarks for coverage levels
      watermarks: {
        statements: [60, 80],
        functions: [60, 80],
        branches: [60, 80],
        lines: [60, 80]
      },
      
      // Skip coverage for certain files
      skipFull: false,
      all: true,
      
      // Coverage ignore patterns
      ignorePatterns: [
        '*.min.js',
        '*.bundle.js',
        '**/vendor/**',
        '**/third-party/**'
      ]
    },

    // Parallel execution
    pool: 'threads',
    poolOptions: {
      threads: {
        singleThread: false,
        minThreads: 1,
        maxThreads: 4
      }
    },
    
    // Watch mode configuration
    watch: {
      ignore: [
        'node_modules/**',
        'coverage/**',
        'dist/**',
        'vendor/**'
      ]
    },
    
    // Reporting
    reporter: ['default', 'html', 'json'],
    outputFile: {
      html: './coverage/test-results.html',
      json: './coverage/test-results.json'
    },
    
    // Mock configuration
    deps: {
      inline: [
        // Add any dependencies that need to be inlined for tests
      ]
    },
    
    // Global test configuration
    globals: true,
    
    // CSS and static assets
    css: {
      modules: {
        classNameStrategy: 'stable'
      }
    }
  },
  
  // Build configuration for tests
  define: {
    __DEV__: true,
    __TEST__: true
  },
  
  // Resolve configuration
  resolve: {
    alias: {
      '@': resolve(__dirname, './resources/js'),
      '@tests': resolve(__dirname, './tests/js'),
      '@components': resolve(__dirname, './resources/js/components'),
      '@utils': resolve(__dirname, './resources/js/core/utils.js'),
      '@config': resolve(__dirname, './resources/js/core/config.js'),
      '@auth': resolve(__dirname, './resources/js/auth'),
      '@cart': resolve(__dirname, './resources/js/cart')
    }
  },
  
  // Server configuration for test environment
  server: {
    deps: {
      inline: ['vitest-canvas-mock']
    }
  }
}) 