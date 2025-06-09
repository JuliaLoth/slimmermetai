import { describe, it, expect, beforeEach, vi } from 'vitest'
import { JSDOM } from 'jsdom'

// Setup DOM environment
const dom = new JSDOM('<!DOCTYPE html><html><body></body></html>', {
  url: 'http://localhost:8000',
  pretendToBeVisual: true,
  resources: 'usable'
})

global.window = dom.window
global.document = dom.window.document

// Mock localStorage with more comprehensive methods
const localStorageMock = {
  getItem: vi.fn((key) => {
    return localStorageMock._storage[key] || null
  }),
  setItem: vi.fn((key, value) => {
    localStorageMock._storage[key] = value
  }),
  removeItem: vi.fn((key) => {
    delete localStorageMock._storage[key]
  }),
  clear: vi.fn(() => {
    localStorageMock._storage = {}
  }),
  _storage: {}
}

global.localStorage = localStorageMock
global.fetch = vi.fn()

describe('Auth Integration Tests', () => {
  beforeEach(() => {
    // Setup minimal auth page DOM
    document.body.innerHTML = `
      <form id="login-form">
        <input type="email" id="email" name="email" />
        <input type="password" id="password" name="password" />
        <button type="submit">Login</button>
      </form>
      
      <form id="register-form">
        <input type="text" id="name" name="name" />
        <input type="email" id="reg-email" name="email" />
        <input type="password" id="reg-password" name="password" />
        <input type="password" id="confirm-password" name="confirm_password" />
        <button type="submit">Register</button>
      </form>
      
      <div id="error-messages"></div>
      <div id="success-messages"></div>
    `
    
    // Clear localStorage mock
    localStorageMock.clear()
    localStorageMock._storage = {}
    vi.clearAllMocks()
  })

  it('moet auth module kunnen laden en initialiseren', async () => {
    const Auth = await import('../../../resources/js/auth/auth.js')
    const AuthModule = Auth.default
    
    expect(AuthModule).toBeDefined()
    expect(typeof AuthModule.init).toBe('function')
    expect(typeof AuthModule.login).toBe('function')
    expect(typeof AuthModule.logout).toBe('function')
  })

  it('moet login form kunnen submitten met API call', async () => {
    const Auth = await import('../../../resources/js/auth/auth.js')
    const AuthModule = Auth.default
    
    // Mock successful login response
    fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({
        success: true,
        token: 'mock-jwt-token',
        user: { id: 1, email: 'test@example.com', name: 'Test User' }
      })
    })
    
    AuthModule.init()
    
    // Test login functionaliteit
    const result = await AuthModule.login({
      email: 'test@example.com',
      password: 'password123'
    })
    
    expect(result).toBeDefined()
    expect(fetch).toHaveBeenCalled()
  })

  it('moet JWT token kunnen opslaan en ophalen', async () => {
    const Auth = await import('../../../resources/js/auth/auth.js')
    const AuthModule = Auth.default
    
    const mockToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.test'
    
    // Store token
    AuthModule.setToken(mockToken)
    
    // Retrieve token
    const retrievedToken = AuthModule.getToken()
    expect(retrievedToken).toBe(mockToken)
    
    // Check localStorage mock
    expect(localStorageMock.getItem('token')).toBe(mockToken)
  })

  it('moet user session kunnen beheren', async () => {
    const Auth = await import('../../../resources/js/auth/auth.js')
    const AuthModule = Auth.default
    
    const mockUser = {
      id: 1,
      email: 'test@example.com',
      name: 'Test User'
    }
    
    // Set user
    AuthModule.setUser(mockUser)
    
    // Check if user is set
    expect(AuthModule.getUser()).toEqual(mockUser)
    expect(AuthModule.isLoggedIn()).toBe(true)
    
    // Logout (await because it's async)
    await AuthModule.logout()
    
    expect(AuthModule.isLoggedIn()).toBe(false)
    expect(AuthModule.getUser()).toBeNull()
  })

  it('moet password visibility kunnen togglen', async () => {
    const Auth = await import('../../../resources/js/auth/auth.js')
    const AuthModule = Auth.default
    
    const passwordInput = document.getElementById('password')
    
    // Initial state should be password
    expect(passwordInput.type).toBe('password')
    
    // Create mock button for toggle
    const button = document.createElement('button')
    button.innerHTML = '<svg></svg>'
    
    // Toggle visibility
    AuthModule.togglePasswordVisibility('password', button)
    
    // Should now be text (visible)
    expect(passwordInput.type).toBe('text')
    
    // Toggle back
    AuthModule.togglePasswordVisibility('password', button)
    
    // Should be password again (hidden)
    expect(passwordInput.type).toBe('password')
  })

  it('moet errors kunnen afhandelen bij API failures', async () => {
    const Auth = await import('../../../resources/js/auth/auth.js')
    const AuthModule = Auth.default
    
    // Mock login error response
    fetch.mockResolvedValueOnce({
      ok: false,
      status: 422,
      json: () => Promise.resolve({
        success: false,
        message: 'Invalid credentials'
      })
    })
    
    const result = await AuthModule.login({
      email: 'invalid@email.com',
      password: 'wrongpassword'
    })
    
    expect(result.success).toBe(false)
    expect(result.message).toContain('Invalid credentials')
  })

  it('moet network errors kunnen afhandelen', async () => {
    const Auth = await import('../../../resources/js/auth/auth.js')
    const AuthModule = Auth.default
    
    // Mock network error
    fetch.mockRejectedValueOnce(new Error('Network error'))
    
    const result = await AuthModule.login({
      email: 'test@example.com',
      password: 'password123'
    })
    
    expect(result.success).toBe(false)
    expect(result.message).toBeDefined()
  })
}) 