// Global test setup for Vitest
import { beforeEach } from 'vitest'

// Mock localStorage voor tests
global.localStorage = {
  data: {},
  getItem(key) {
    return this.data[key] || null
  },
  setItem(key, value) {
    this.data[key] = value
  },
  removeItem(key) {
    delete this.data[key]
  },
  clear() {
    this.data = {}
  }
}

// Mock sessionStorage voor tests
global.sessionStorage = {
  data: {},
  getItem(key) {
    return this.data[key] || null
  },
  setItem(key, value) {
    this.data[key] = value
  },
  removeItem(key) {
    delete this.data[key]
  },
  clear() {
    this.data = {}
  }
}

// Mock window.location
global.window = {
  location: {
    href: 'http://localhost:8000',
    origin: 'http://localhost:8000',
    pathname: '/',
    search: '',
    hash: ''
  }
}

// Reset localStorage en sessionStorage voor elke test
beforeEach(() => {
  localStorage.clear()
  sessionStorage.clear()
}) 