module.exports = {
  env: {
    browser: true,
    es2022: true,
    node: true,
  },
  extends: [
    'eslint:recommended',
    'plugin:import/recommended',
    'prettier',
  ],
  parserOptions: {
    ecmaVersion: 'latest',
    sourceType: 'module',
  },
  rules: {
    'no-console': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
    'no-undef': 'off',
    'no-inner-declarations': 'off',
    'no-unused-vars': 'warn',
  },
  globals: {
    Cart: 'readonly',
    showNotification: 'readonly',
    StripePayment: 'readonly',
    Stripe: 'readonly',
    CourseDataManager: 'readonly',
    auth: 'readonly',
    csrfToken: 'readonly',
  },
}; 