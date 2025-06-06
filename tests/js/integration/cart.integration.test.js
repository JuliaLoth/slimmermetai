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
global.localStorage = dom.window.localStorage
global.sessionStorage = dom.window.sessionStorage

// Mock fetch voor API calls
global.fetch = vi.fn()

describe('Cart Integration Tests', () => {
  beforeEach(() => {
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
    
    // Clear localStorage
    localStorage.clear()
    
    // Reset fetch mock
    vi.clearAllMocks()
    
    // Mock successful API responses
    fetch.mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ success: true })
    })
  })

  it('moet cart functionaliteit kunnen laden via module import', async () => {
    // Dynamically import cart module
    const { default: Cart } = await import('../../../resources/js/cart/cart.js')
    
    expect(Cart).toBeDefined()
    expect(typeof Cart.addItem).toBe('function')
    expect(typeof Cart.getItems).toBe('function')
    expect(typeof Cart.calculateTotal).toBe('function')
  })

  it('moet items kunnen toevoegen aan cart en localStorage updaten', async () => {
    const { default: Cart } = await import('../../../resources/js/cart/cart.js')
    
    // Initialize cart
    Cart.init()
    
    // Add item to cart
    const item = {
      id: '1',
      name: 'Test Product',
      price: 29.99,
      quantity: 1
    }
    
    Cart.addItem(item)
    
    // Check localStorage was updated
    const cartData = JSON.parse(localStorage.getItem('cart') || '[]')
    expect(cartData).toHaveLength(1)
    expect(cartData[0].name).toBe('Test Product')
    expect(cartData[0].price).toBe(29.99)
  })

  it('moet cart counter in DOM kunnen updaten', async () => {
    const { default: Cart } = await import('../../../resources/js/cart/cart.js')
    
    Cart.init()
    
    // Add item
    Cart.addItem({
      id: '1',
      name: 'Test Product', 
      price: 29.99,
      quantity: 2
    })
    
    // Update cart count in DOM
    Cart.updateCartCount()
    
    // Check DOM was updated
    const cartCountElement = document.getElementById('cart-count')
    expect(cartCountElement.textContent).toBe('2')
  })

  it('moet total kunnen berekenen inclusief BTW', async () => {
    const { default: Cart } = await import('../../../resources/js/cart/cart.js')
    
    Cart.init()
    
    // Add multiple items
    Cart.addItem({ id: '1', name: 'Product 1', price: 29.99, quantity: 2 })
    Cart.addItem({ id: '2', name: 'Product 2', price: 49.99, quantity: 1 })
    
    const total = Cart.calculateTotal()
    const totalWithTax = Cart.getTotalWithTax()
    
    expect(total).toBe(109.97) // (29.99 * 2) + 49.99
    expect(totalWithTax).toBeGreaterThan(total) // Should include 21% BTW
  })

  it('moet event delegation werken voor add-to-cart knoppen', async () => {
    const { default: Cart } = await import('../../../resources/js/cart/cart.js')
    
    Cart.init()
    
    // Simulate click on add-to-cart button
    const button = document.querySelector('.add-to-cart-btn')
    const clickEvent = new dom.window.Event('click', { bubbles: true })
    
    button.dispatchEvent(clickEvent)
    
    // Check if item was added (based on data attributes)
    const cartItems = Cart.getItems()
    expect(cartItems).toHaveLength(1)
    expect(cartItems[0].name).toBe('Test Product')
    expect(cartItems[0].price).toBe('29.99')
  })

  it('moet cart kunnen leegmaken', async () => {
    const { default: Cart } = await import('../../../resources/js/cart/cart.js')
    
    Cart.init()
    
    // Add items
    Cart.addItem({ id: '1', name: 'Product 1', price: 29.99, quantity: 1 })
    Cart.addItem({ id: '2', name: 'Product 2', price: 49.99, quantity: 1 })
    
    expect(Cart.getItems()).toHaveLength(2)
    
    // Clear cart
    Cart.clearCart()
    
    expect(Cart.getItems()).toHaveLength(0)
    expect(localStorage.getItem('cart')).toBe('[]')
  })

  it('moet cart state persistent maken tussen sessies', async () => {
    const { default: Cart } = await import('../../../resources/js/cart/cart.js')
    
    // First session - add items
    Cart.init()
    Cart.addItem({ id: '1', name: 'Persistent Product', price: 99.99, quantity: 1 })
    
    // Simulate page reload by reinitializing
    const { default: CartReloaded } = await import('../../../resources/js/cart/cart.js')
    CartReloaded.init()
    
    // Check if items were restored from localStorage
    const items = CartReloaded.getItems()
    expect(items).toHaveLength(1)
    expect(items[0].name).toBe('Persistent Product')
  })

  it('moet API calls kunnen maken voor checkout', async () => {
    const { default: Cart } = await import('../../../resources/js/cart/cart.js')
    
    Cart.init()
    Cart.addItem({ id: '1', name: 'Checkout Product', price: 199.99, quantity: 1 })
    
    // Mock checkout API response
    fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({
        success: true,
        session: { id: 'cs_test_mock_123' }
      })
    })
    
    // Attempt checkout
    const result = await Cart.checkout()
    
    expect(fetch).toHaveBeenCalledWith('/api/stripe/checkout', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        items: Cart.getItems(),
        total: Cart.getTotalWithTax()
      })
    })
    
    expect(result.success).toBe(true)
    expect(result.session.id).toBe('cs_test_mock_123')
  })
}) 