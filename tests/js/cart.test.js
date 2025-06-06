import { describe, it, expect, beforeEach, vi } from 'vitest'

// Mock Cart module - dit zou de echte cart.js modules moeten importeren
const mockCart = {
  items: [],
  addItem(product) {
    const existingItem = this.items.find(item => item.id === product.id)
    if (existingItem) {
      existingItem.quantity += product.quantity || 1
    } else {
      this.items.push({
        id: product.id,
        name: product.name,
        price: product.price,
        quantity: product.quantity || 1
      })
    }
    this.updateCartCount()
    this.saveToLocalStorage()
  },
  
  removeItem(productId) {
    this.items = this.items.filter(item => item.id !== productId)
    this.updateCartCount()
    this.saveToLocalStorage()
  },
  
  calculateTotal() {
    return this.items.reduce((total, item) => total + (item.price * item.quantity), 0)
  },
  
  getTotalWithTax() {
    const subtotal = this.calculateTotal()
    const btw = subtotal * 0.21
    return subtotal + btw
  },
  
  getItems() {
    return this.items
  },
  
  updateCartCount() {
    const count = this.items.reduce((total, item) => total + item.quantity, 0)
    // Mock DOM updates
    return count
  },
  
  saveToLocalStorage() {
    localStorage.setItem('cart_items', JSON.stringify(this.items))
  },
  
  loadFromLocalStorage() {
    const saved = localStorage.getItem('cart_items')
    this.items = saved ? JSON.parse(saved) : []
  },
  
  clear() {
    this.items = []
    this.updateCartCount()
    this.saveToLocalStorage()
  }
}

describe('Cart Functionaliteit', () => {
  beforeEach(() => {
    mockCart.clear()
    localStorage.clear()
  })

  describe('Items toevoegen', () => {
    it('moet een item kunnen toevoegen aan lege cart', () => {
      const product = {
        id: 'email-assistant',
        name: 'AI Email Assistent',
        price: 29.99
      }
      
      mockCart.addItem(product)
      
      expect(mockCart.items).toHaveLength(1)
      expect(mockCart.items[0]).toEqual({
        id: 'email-assistant',
        name: 'AI Email Assistent',
        price: 29.99,
        quantity: 1
      })
    })

    it('moet quantity verhogen van bestaand item', () => {
      const product = {
        id: 'email-assistant',
        name: 'AI Email Assistent',
        price: 29.99
      }
      
      mockCart.addItem(product)
      mockCart.addItem(product)
      
      expect(mockCart.items).toHaveLength(1)
      expect(mockCart.items[0].quantity).toBe(2)
    })

    it('moet meerdere verschillende items kunnen bewaren', () => {
      const product1 = { id: 'email-assistant', name: 'Email Tool', price: 29.99 }
      const product2 = { id: 'ai-basics', name: 'AI Basics', price: 97.00 }
      
      mockCart.addItem(product1)
      mockCart.addItem(product2)
      
      expect(mockCart.items).toHaveLength(2)
    })
  })

  describe('Items verwijderen', () => {
    beforeEach(() => {
      mockCart.addItem({ id: 'test-item', name: 'Test', price: 10.00 })
    })

    it('moet item kunnen verwijderen', () => {
      mockCart.removeItem('test-item')
      expect(mockCart.items).toHaveLength(0)
    })

    it('moet niets doen bij onbekend item ID', () => {
      mockCart.removeItem('onbekend-item')
      expect(mockCart.items).toHaveLength(1)
    })
  })

  describe('Totaal berekeningen', () => {
    beforeEach(() => {
      mockCart.addItem({ id: 'item1', name: 'Item 1', price: 10.00, quantity: 2 })
      mockCart.addItem({ id: 'item2', name: 'Item 2', price: 5.50, quantity: 1 })
    })

    it('moet subtotaal correct berekenen', () => {
      const total = mockCart.calculateTotal()
      expect(total).toBe(25.50) // (10.00 * 2) + (5.50 * 1)
    })

    it('moet totaal met BTW correct berekenen', () => {
      const totalWithTax = mockCart.getTotalWithTax()
      const expected = 25.50 * 1.21 // 21% BTW
      expect(totalWithTax).toBeCloseTo(expected, 2)
    })
  })

  describe('LocalStorage persistentie', () => {
    it('moet cart opslaan in localStorage', () => {
      const product = { id: 'test', name: 'Test Product', price: 15.00 }
      mockCart.addItem(product)
      
      const saved = localStorage.getItem('cart_items')
      expect(saved).toBeTruthy()
      expect(JSON.parse(saved)).toHaveLength(1)
    })

    it('moet cart laden uit localStorage', () => {
      const testItems = [
        { id: 'test1', name: 'Test 1', price: 10.00, quantity: 1 }
      ]
      localStorage.setItem('cart_items', JSON.stringify(testItems))
      
      mockCart.loadFromLocalStorage()
      expect(mockCart.items).toEqual(testItems)
    })

    it('moet lege array geven als localStorage leeg is', () => {
      mockCart.loadFromLocalStorage()
      expect(mockCart.items).toEqual([])
    })
  })

  describe('Cart leegmaken', () => {
    it('moet alle items verwijderen bij clear()', () => {
      mockCart.addItem({ id: 'test1', name: 'Test 1', price: 10.00 })
      mockCart.addItem({ id: 'test2', name: 'Test 2', price: 20.00 })
      
      mockCart.clear()
      
      expect(mockCart.items).toHaveLength(0)
      expect(localStorage.getItem('cart_items')).toBe('[]')
    })
  })

  describe('getItems() functie', () => {
    it('moet alle cart items retourneren', () => {
      const product1 = { id: 'item1', name: 'Item 1', price: 10.00 }
      const product2 = { id: 'item2', name: 'Item 2', price: 20.00 }
      
      mockCart.addItem(product1)
      mockCart.addItem(product2)
      
      const items = mockCart.getItems()
      expect(items).toHaveLength(2)
      expect(items[0].id).toBe('item1')
      expect(items[1].id).toBe('item2')
    })
  })
}) 