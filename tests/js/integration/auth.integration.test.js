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
global.localStorage = dom.window.localStorage
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
    
    localStorage.clear()
    vi.clearAllMocks()
  })

  it('moet auth module kunnen laden en initialiseren', async () => {
    const { default: Auth } = await import('../../../resources/js/auth/auth.js')
    
    expect(Auth).toBeDefined()
    expect(typeof Auth.init).toBe('function')
    expect(typeof Auth.login).toBe('function')
    expect(typeof Auth.register).toBe('function')
    expect(typeof Auth.logout).toBe('function')
  })

  it('moet login form kunnen submitten met API call', async () => {
    const { default: Auth } = await import('../../../resources/js/auth/auth.js')
    
    // Mock successful login response
    fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({
        success: true,
        token: 'mock-jwt-token',
        user: { id: 1, email: 'test@example.com', name: 'Test User' }
      })
    })
    
    Auth.init()
    
    // Fill in form
    document.getElementById('email').value = 'test@example.com'
    document.getElementById('password').value = 'password123'
    
    // Submit form
    const form = document.getElementById('login-form')
    const submitEvent = new dom.window.Event('submit', { bubbles: true })
    form.dispatchEvent(submitEvent)
    
    // Wait for async operations
    await new Promise(resolve => setTimeout(resolve, 100))
    
    expect(fetch).toHaveBeenCalledWith('/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        email: 'test@example.com',
        password: 'password123'
      })
    })
  })

  it('moet registration form kunnen afhandelen', async () => {
    const { default: Auth } = await import('../../../resources/js/auth/auth.js')
    
    // Mock successful registration response
    fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({
        success: true,
        token: 'mock-jwt-token',
        user: { id: 2, email: 'newuser@example.com', name: 'New User' }
      })
    })
    
    Auth.init()
    
    // Fill in registration form
    document.getElementById('name').value = 'New User'
    document.getElementById('reg-email').value = 'newuser@example.com'
    document.getElementById('reg-password').value = 'password123'
    document.getElementById('confirm-password').value = 'password123'
    
    // Submit registration form
    const form = document.getElementById('register-form')
    const submitEvent = new dom.window.Event('submit', { bubbles: true })
    form.dispatchEvent(submitEvent)
    
    await new Promise(resolve => setTimeout(resolve, 100))
    
    expect(fetch).toHaveBeenCalledWith('/api/auth/register', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        name: 'New User',
        email: 'newuser@example.com',
        password: 'password123',
        confirm_password: 'password123'
      })
    })
  })

  it('moet errors kunnen tonen in DOM', async () => {
    const { default: Auth } = await import('../../../resources/js/auth/auth.js')
    
    // Mock login error response
    fetch.mockResolvedValueOnce({
      ok: false,
      status: 422,
      json: () => Promise.resolve({
        success: false,
        errors: {
          email: ['Invalid credentials'],
          password: ['Password is required']
        }
      })
    })
    
    Auth.init()
    
    // Submit form with empty values
    document.getElementById('email').value = 'invalid@email.com'
    document.getElementById('password').value = ''
    
    const form = document.getElementById('login-form')
    const submitEvent = new dom.window.Event('submit', { bubbles: true })
    form.dispatchEvent(submitEvent)
    
    await new Promise(resolve => setTimeout(resolve, 100))
    
    // Check if errors are displayed
    const errorContainer = document.getElementById('error-messages')
    expect(errorContainer.innerHTML).toContain('Invalid credentials')
    expect(errorContainer.innerHTML).toContain('Password is required')
  })

  it('moet JWT token kunnen opslaan en ophalen', async () => {
    const { default: Auth } = await import('../../../resources/js/auth/auth.js')
    
    const mockToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.test'
    
    // Store token
    Auth.setToken(mockToken)
    
    // Retrieve token
    const retrievedToken = Auth.getToken()
    expect(retrievedToken).toBe(mockToken)
    
    // Check localStorage
    expect(localStorage.getItem('auth_token')).toBe(mockToken)
  })

  it('moet user session kunnen beheren', async () => {
    const { default: Auth } = await import('../../../resources/js/auth/auth.js')
    
    const mockUser = {
      id: 1,
      email: 'test@example.com',
      name: 'Test User'
    }
    
    // Set user
    Auth.setUser(mockUser)
    
    // Check if user is set
    expect(Auth.isLoggedIn()).toBe(true)
    expect(Auth.getUser()).toEqual(mockUser)
    
    // Logout
    Auth.logout()
    
    expect(Auth.isLoggedIn()).toBe(false)
    expect(Auth.getUser()).toBeNull()
    expect(localStorage.getItem('auth_token')).toBeNull()
  })

  it('moet password visibility kunnen togglen', async () => {
    const { default: Auth } = await import('../../../resources/js/auth/auth.js')
    
    Auth.init()
    
    const passwordInput = document.getElementById('password')
    
    // Initial state should be password
    expect(passwordInput.type).toBe('password')
    
    // Toggle visibility
    Auth.togglePasswordVisibility('password')
    
    // Should now be text (visible)
    expect(passwordInput.type).toBe('text')
    
    // Toggle back
    Auth.togglePasswordVisibility('password')
    
    // Should be password again (hidden)
    expect(passwordInput.type).toBe('password')
  })

  it('moet CSRF token kunnen injecteren in forms', async () => {
    const { default: Auth } = await import('../../../resources/js/auth/auth.js')
    
    // Mock CSRF token in meta tag
    const metaToken = document.createElement('meta')
    metaToken.name = 'csrf-token'
    metaToken.content = 'mock-csrf-token-123'
    document.head.appendChild(metaToken)
    
    Auth.init()
    
    // Check if CSRF token was injected into forms
    const loginForm = document.getElementById('login-form')
    const csrfInput = loginForm.querySelector('input[name="_token"]')
    
    expect(csrfInput).toBeTruthy()
    expect(csrfInput.value).toBe('mock-csrf-token-123')
  })

  it('moet auto-redirect naar dashboard na login', async () => {
    const { default: Auth } = await import('../../../resources/js/auth/auth.js')
    
    // Mock successful login with redirect
    fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({
        success: true,
        token: 'mock-jwt-token',
        user: { id: 1, email: 'test@example.com' },
        redirect: '/dashboard'
      })
    })
    
    // Mock window.location
    delete window.location
    window.location = { href: '' }
    
    Auth.init()
    
    // Fill and submit login form
    document.getElementById('email').value = 'test@example.com'
    document.getElementById('password').value = 'password123'
    
    const form = document.getElementById('login-form')
    const submitEvent = new dom.window.Event('submit', { bubbles: true })
    form.dispatchEvent(submitEvent)
    
    await new Promise(resolve => setTimeout(resolve, 100))
    
    // Check if redirect happened
    expect(window.location.href).toBe('/dashboard')
  })
}) 