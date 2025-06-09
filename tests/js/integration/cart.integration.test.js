import { describe, it, expect, beforeEach, vi } from 'vitest'
import { JSDOM } from 'jsdom'

// Setup DOM environment voor tests
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
global.sessionStorage = localStorageMock

// Mock fetch voor API calls
global.fetch = vi.fn()

// Mock showNotification function
global.showNotification = vi.fn()

describe('Cart Integration Tests', () => {
  let CartModule

  beforeEach(async () => {
    // Reset DOM voor elke test
    document.body.innerHTML = `
      <div id="cart-count">0</div>
      <div class="cart-count">0</div>
      <button class="add-to-cart-btn" 
              data-product-id="1" 
              data-product-name="Test Product" 
              data-product-price="29.99">
        Add to Cart
      </button>
    `
    
    // Clear localStorage mock
    localStorageMock.clear()
    localStorageMock._storage = {}
    
    // Reset fetch mock
    vi.clearAllMocks()
    
    // Mock successful API responses
    fetch.mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ success: true })
    })

    // Import and reset cart module for each test
    const Cart = await import('../../../resources/js/cart/cart.js')
    CartModule = Cart.default
    
    // Reset cart state completely
    CartModule.items = []
    CartModule.initialized = false
    
    // Initialize fresh for each test
    CartModule.init()
  })

  it('moet cart functionaliteit kunnen laden via module import', async () => {
    expect(CartModule).toBeDefined()
    expect(typeof CartModule.addItem).toBe('function')
    expect(typeof CartModule.getItems).toBe('function')
    expect(typeof CartModule.calculateTotal).toBe('function')
    expect(typeof CartModule.init).toBe('function')
  })

  it('moet items kunnen toevoegen aan cart en localStorage updaten', async () => {
    // Add item to cart
    const item = {
      id: '1',
      name: 'Test Product',
      price: 29.99,
      quantity: 1
    }
    
    CartModule.addItem(item)
    
    // Check items were added
    const cartItems = CartModule.getItems()
    expect(cartItems).toHaveLength(1)
    expect(cartItems[0].name).toBe('Test Product')
    expect(cartItems[0].price).toBe(29.99)
    
    // Check localStorage was updated
    expect(localStorageMock.setItem).toHaveBeenCalled()
  })

  it('moet cart counter in DOM kunnen updaten', async () => {
    // Add item
    CartModule.addItem({
      id: '1',
      name: 'Test Product', 
      price: 29.99,
      quantity: 2
    })
    
    // Update cart count in DOM
    CartModule.renderCartCount()
    
    // Check DOM was updated
    const cartCountElement = document.getElementById('cart-count')
    expect(cartCountElement.textContent).toBe('2')
  })

  it('moet total kunnen berekenen inclusief BTW', async () => {
    // Add multiple items with specific quantities
    CartModule.addItem({ id: '1', name: 'Product 1', price: 29.99, quantity: 2 })
    CartModule.addItem({ id: '2', name: 'Product 2', price: 49.99, quantity: 1 })
    
    const total = CartModule.calculateTotal()
    const totalWithTax = CartModule.getTotalWithTax()
    
    expect(total).toBe(109.97) // (29.99 * 2) + 49.99
    expect(totalWithTax).toBeGreaterThan(total) // Should include 21% BTW
  })

  it('moet cart kunnen leegmaken', async () => {
    // Add items
    CartModule.addItem({ id: '1', name: 'Product 1', price: 29.99, quantity: 1 })
    CartModule.addItem({ id: '2', name: 'Product 2', price: 49.99, quantity: 1 })
    
    expect(CartModule.getItems()).toHaveLength(2)
    
    // Clear cart using resetCart method (which doesn't require confirmation)
    CartModule.resetCart(false) // false = don't show notification
    
    expect(CartModule.getItems()).toHaveLength(0)
  })

  it('moet cart state persistent maken tussen sessies', async () => {
    // First session - add items
    CartModule.addItem({ id: '1', name: 'Persistent Product', price: 99.99, quantity: 1 })
    
    // Simulate saving to localStorage
    const cartData = JSON.stringify(CartModule.getItems())
    localStorageMock.setItem('slimmerAICart', cartData)
    
    // Reset cart items and load from storage
    CartModule.items = []
    CartModule.loadFromStorage()
    
    // Check if items were restored from localStorage
    const items = CartModule.getItems()
    expect(items).toHaveLength(1)
    expect(items[0].name).toBe('Persistent Product')
  })

  it('moet items kunnen verwijderen uit cart', async () => {
    // Add items
    CartModule.addItem({ id: '1', name: 'Product 1', price: 29.99, quantity: 1 })
    CartModule.addItem({ id: '2', name: 'Product 2', price: 49.99, quantity: 1 })
    
    expect(CartModule.getItems()).toHaveLength(2)
    
    // Remove one item
    CartModule.removeItem('1')
    
    expect(CartModule.getItems()).toHaveLength(1)
    expect(CartModule.getItems()[0].id).toBe('2')
  })

  it('moet quantity kunnen updaten', async () => {
    // Add item
    CartModule.addItem({ id: '1', name: 'Product 1', price: 29.99, quantity: 1 })
    
    // Update quantity
    CartModule.updateQuantity('1', 5)
    
    const items = CartModule.getItems()
    expect(items[0].quantity).toBe(5)
  })
}) 