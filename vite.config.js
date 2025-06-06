import { defineConfig } from 'vite';
import path from 'path';
import purgecss from 'vite-plugin-purgecss';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig(({ mode }) => ({
  plugins: [
    ...(mode === 'production'
      ? [
          purgecss({
            content: [
              './resources/**/*.{js,jsx,ts,tsx,vue,php,html}',
              './resources/views/**/*.php',
            ],
            safelist: [/^chunk-/, /^bundle-/],
          }),
          viteStaticCopy({
            targets: [
              { src: 'images/**/*', dest: '../images' },
              { src: 'fonts/**/*', dest: '../fonts' },
            ],
          }),
        ]
      : []),
  ],
  build: {
    manifest: true,
    outDir: 'public_html/assets/js',
    assetsDir: '',
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, 'resources/js/core/main.js'),
        'stripe-payment': path.resolve(__dirname, 'resources/js/stripe/stripe-payment.js'),
        stripe: path.resolve(__dirname, 'resources/js/stripe/payment.js'),
        cart: path.resolve(__dirname, 'resources/js/cart/cart.js'),
        auth: path.resolve(__dirname, 'resources/js/auth/auth.js'),
      },
      output: {
        entryFileNames: 'bundle-[name].[hash].js',
        chunkFileNames: 'chunk-[name].[hash].js',
      },
    },
  },
})); 