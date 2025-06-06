import { describe, it, expect, beforeEach, vi } from 'vitest'

// Mock Auth functionaliteit
const mockAuth = {
  isLoggedIn: false,
  user: null,
  
  async login(email, password) {
    // Mock login validatie
    if (!email || !password) {
      throw new Error('Email en wachtwoord zijn verplicht')
    }
    
    if (password.length < 6) {
      throw new Error('Wachtwoord moet minimaal 6 karakters bevatten')
    }
    
    // Mock successful login
    this.isLoggedIn = true
    this.user = { 
      email, 
      name: 'Test Gebruiker',
      id: 'test-user-123' 
    }
    
    // Simuleer JWT token storage
    localStorage.setItem('auth_token', 'mock-jwt-token-123')
    
    return { success: true, user: this.user }
  },
  
  async register(name, email, password, confirmPassword) {
    // Input validatie
    if (!name || !email || !password || !confirmPassword) {
      throw new Error('Alle velden zijn verplicht')
    }
    
    if (password !== confirmPassword) {
      throw new Error('Wachtwoorden komen niet overeen')
    }
    
    if (password.length < 6) {
      throw new Error('Wachtwoord moet minimaal 6 karakters bevatten')
    }
    
    // Email format validatie
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(email)) {
      throw new Error('Ongeldig email adres')
    }
    
    // Mock successful registration
    this.isLoggedIn = true
    this.user = { name, email, id: 'new-user-456' }
    localStorage.setItem('auth_token', 'mock-jwt-token-456')
    
    return { success: true, user: this.user }
  },
  
  logout() {
    this.isLoggedIn = false
    this.user = null
    localStorage.removeItem('auth_token')
  },
  
  async forgotPassword(email) {
    if (!email) {
      throw new Error('Email adres is verplicht')
    }
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(email)) {
      throw new Error('Ongeldig email adres')
    }
    
    // Mock password reset
    return { 
      success: true, 
      message: 'Wachtwoord reset link verzonden naar ' + email 
    }
  },
  
  checkAuthStatus() {
    const token = localStorage.getItem('auth_token')
    if (token && token.startsWith('mock-jwt-token')) {
      this.isLoggedIn = true
      this.user = { 
        id: token.split('-')[3], 
        email: 'test@example.com',
        name: 'Test Gebruiker'
      }
    } else {
      this.isLoggedIn = false
      this.user = null
    }
    return this.isLoggedIn
  },
  
  // Password visibility toggle
  togglePasswordVisibility(inputId) {
    const input = { type: 'password' } // Mock input element
    if (input.type === 'password') {
      input.type = 'text'
      return 'text'
    } else {
      input.type = 'password'
      return 'password'
    }
  }
}

describe('Auth Functionaliteit', () => {
  beforeEach(() => {
    mockAuth.logout()
    localStorage.clear()
  })

  describe('Login', () => {
    it('moet succesvol inloggen met geldige credentials', async () => {
      const result = await mockAuth.login('test@example.com', 'password123')
      
      expect(result.success).toBe(true)
      expect(mockAuth.isLoggedIn).toBe(true)
      expect(mockAuth.user.email).toBe('test@example.com')
      expect(localStorage.getItem('auth_token')).toBeTruthy()
    })

    it('moet error gooien bij ontbrekende email', async () => {
      await expect(mockAuth.login('', 'password123')).rejects.toThrow('Email en wachtwoord zijn verplicht')
    })

    it('moet error gooien bij ontbrekend wachtwoord', async () => {
      await expect(mockAuth.login('test@example.com', '')).rejects.toThrow('Email en wachtwoord zijn verplicht')
    })

    it('moet error gooien bij te kort wachtwoord', async () => {
      await expect(mockAuth.login('test@example.com', '123')).rejects.toThrow('Wachtwoord moet minimaal 6 karakters bevatten')
    })
  })

  describe('Registratie', () => {
    it('moet succesvol registreren met geldige gegevens', async () => {
      const result = await mockAuth.register(
        'Jan Jansen',
        'jan@example.com',
        'password123',
        'password123'
      )
      
      expect(result.success).toBe(true)
      expect(mockAuth.isLoggedIn).toBe(true)
      expect(mockAuth.user.name).toBe('Jan Jansen')
      expect(mockAuth.user.email).toBe('jan@example.com')
    })

    it('moet error gooien bij niet-overeenkomende wachtwoorden', async () => {
      await expect(mockAuth.register(
        'Jan Jansen',
        'jan@example.com',
        'password123',
        'password456'
      )).rejects.toThrow('Wachtwoorden komen niet overeen')
    })

    it('moet error gooien bij ongeldig email formaat', async () => {
      await expect(mockAuth.register(
        'Jan Jansen',
        'invalid-email',
        'password123',
        'password123'
      )).rejects.toThrow('Ongeldig email adres')
    })

    it('moet error gooien bij ontbrekende velden', async () => {
      await expect(mockAuth.register(
        '',
        'jan@example.com',
        'password123',
        'password123'
      )).rejects.toThrow('Alle velden zijn verplicht')
    })
  })

  describe('Wachtwoord vergeten', () => {
    it('moet succesvol reset link verzenden', async () => {
      const result = await mockAuth.forgotPassword('test@example.com')
      
      expect(result.success).toBe(true)
      expect(result.message).toContain('test@example.com')
    })

    it('moet error gooien bij ontbrekend email', async () => {
      await expect(mockAuth.forgotPassword('')).rejects.toThrow('Email adres is verplicht')
    })

    it('moet error gooien bij ongeldig email formaat', async () => {
      await expect(mockAuth.forgotPassword('invalid-email')).rejects.toThrow('Ongeldig email adres')
    })
  })

  describe('Logout', () => {
    it('moet gebruiker uitloggen en storage leegmaken', async () => {
      // Eerst inloggen
      await mockAuth.login('test@example.com', 'password123')
      expect(mockAuth.isLoggedIn).toBe(true)
      
      // Dan uitloggen
      mockAuth.logout()
      
      expect(mockAuth.isLoggedIn).toBe(false)
      expect(mockAuth.user).toBeNull()
      expect(localStorage.getItem('auth_token')).toBeNull()
    })
  })

  describe('Auth Status Check', () => {
    it('moet ingelogde status detecteren met geldige token', () => {
      localStorage.setItem('auth_token', 'mock-jwt-token-123')
      
      const isLoggedIn = mockAuth.checkAuthStatus()
      
      expect(isLoggedIn).toBe(true)
      expect(mockAuth.user).toBeTruthy()
    })

    it('moet uitgelogde status detecteren zonder token', () => {
      const isLoggedIn = mockAuth.checkAuthStatus()
      
      expect(isLoggedIn).toBe(false)
      expect(mockAuth.user).toBeNull()
    })

    it('moet uitgelogde status detecteren met ongeldige token', () => {
      localStorage.setItem('auth_token', 'invalid-token')
      
      const isLoggedIn = mockAuth.checkAuthStatus()
      
      expect(isLoggedIn).toBe(false)
      expect(mockAuth.user).toBeNull()
    })
  })

  describe('Password Visibility Toggle', () => {
    it('moet password type togglen naar text', () => {
      const newType = mockAuth.togglePasswordVisibility('password-input')
      expect(newType).toBe('text')
    })
  })

  describe('Email validatie', () => {
    const testCases = [
      { email: 'valid@example.com', valid: true },
      { email: 'test.email@domain.co.uk', valid: true },
      { email: 'user+tag@example.org', valid: true },
      { email: 'invalid-email', valid: false },
      { email: '@example.com', valid: false },
      { email: 'user@', valid: false },
      { email: 'user@.com', valid: false },
      { email: '', valid: false }
    ]

    testCases.forEach(({ email, valid }) => {
      it(`moet ${email || 'lege string'} als ${valid ? 'geldig' : 'ongeldig'} markeren`, () => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        expect(emailRegex.test(email)).toBe(valid)
      })
    })
  })
}) 