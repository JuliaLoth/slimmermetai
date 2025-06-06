/* ========================================
   UNIFIED CART SYSTEM - SlimmerMetAI
   Vervangt alle andere cart implementaties
   ======================================== */

class UnifiedCart {
    constructor() {
        this.items = [];
        this.storageKey = 'slimmerAICart';
        this.initialized = false;
        
        // Bind methods to preserve context
        this.addItem = this.addItem.bind(this);
        this.removeItem = this.removeItem.bind(this);
        this.updateQuantity = this.updateQuantity.bind(this);
        this.updateCartCount = this.updateCartCount.bind(this);
        
        this.init();
    }
    
    init() {
        if (this.initialized) {
            console.log('[UnifiedCart] Already initialized');
            return;
        }
        
        console.log('[UnifiedCart] Initializing...');
        
        // Load from storage
        this.loadFromStorage();
        
        // Setup event delegation for add-to-cart buttons
        this.setupEventListeners();
        
        // Initial cart count update
        this.updateCartCount();
        
        // Setup cart page functionality if on cart page
        if (this.isCartPage()) {
            this.initCartPage();
        }
        
        // Listen for storage changes (multi-tab sync)
        this.setupStorageListener();
        
        this.initialized = true;
        
        // Dispatch cart ready event
        window.dispatchEvent(new CustomEvent('cartReady', { detail: this }));
        
        console.log('[UnifiedCart] Initialization complete. Items:', this.items.length);
    }
    
    loadFromStorage() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            this.items = stored ? JSON.parse(stored) : [];
            console.log('[UnifiedCart] Loaded from storage:', this.items.length, 'items');
        } catch (error) {
            console.error('[UnifiedCart] Error loading from storage:', error);
            this.items = [];
        }
    }
    
    saveToStorage() {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(this.items));
            console.log('[UnifiedCart] Saved to storage:', this.items.length, 'items');
            
            // Dispatch storage change event for multi-tab sync
            window.dispatchEvent(new StorageEvent('storage', {
                key: this.storageKey,
                newValue: JSON.stringify(this.items),
                storageArea: localStorage
            }));
        } catch (error) {
            console.error('[UnifiedCart] Error saving to storage:', error);
        }
    }
    
    setupEventListeners() {
        // Event delegation for add-to-cart buttons
        document.addEventListener('click', (e) => {
            const button = e.target.closest('.add-to-cart-btn');
            if (button) {
                e.preventDefault();
                this.handleAddToCartClick(button);
            }
        });
        
        console.log('[UnifiedCart] Event listeners setup complete');
    }
    
    setupStorageListener() {
        window.addEventListener('storage', (e) => {
            if (e.key === this.storageKey) {
                console.log('[UnifiedCart] Storage change detected from another tab');
                this.loadFromStorage();
                this.updateCartCount();
                
                if (this.isCartPage()) {
                    this.renderCartItems();
                    this.updateCartSummary();
                }
            }
        });
    }
    
    handleAddToCartClick(button) {
        const item = this.extractItemFromButton(button);
        
        if (this.validateItem(item)) {
            this.addItem(item);
            this.showAddToCartFeedback(item);
        } else {
            console.error('[UnifiedCart] Invalid item data:', item);
            this.showError('Er is een fout opgetreden bij het toevoegen aan de winkelwagen.');
        }
    }
    
    extractItemFromButton(button) {
        return {
            id: button.dataset.productId || button.getAttribute('data-product-id'),
            name: button.dataset.productName || button.getAttribute('data-product-name'),
            price: parseFloat(button.dataset.productPrice || button.getAttribute('data-product-price') || 0),
            type: button.dataset.productType || button.getAttribute('data-product-type') || 'product',
            img: button.dataset.productImg || button.getAttribute('data-product-img') || '',
            quantity: parseInt(button.dataset.quantity || button.getAttribute('data-quantity') || 1)
        };
    }
    
    validateItem(item) {
        return item.id && item.name && item.price >= 0 && item.quantity > 0;
    }
    
    addItem(item) {
        console.log('[UnifiedCart] Adding item:', item);
        
        const existingItem = this.items.find(i => i.id === item.id);
        
        if (existingItem) {
            existingItem.quantity += item.quantity;
            console.log('[UnifiedCart] Updated existing item quantity:', existingItem.quantity);
        } else {
            this.items.push({ ...item });
            console.log('[UnifiedCart] Added new item');
        }
        
        this.saveToStorage();
        this.updateCartCount();
        this.dispatchCartUpdateEvent();
        
        // Update cart page if we're on it
        if (this.isCartPage()) {
            this.renderCartItems();
            this.updateCartSummary();
        }
    }
    
    removeItem(itemId) {
        console.log('[UnifiedCart] Removing item:', itemId);
        
        const initialLength = this.items.length;
        this.items = this.items.filter(item => item.id !== itemId);
        
        if (this.items.length < initialLength) {
            this.saveToStorage();
            this.updateCartCount();
            this.dispatchCartUpdateEvent();
            
            if (this.isCartPage()) {
                this.renderCartItems();
                this.updateCartSummary();
            }
            
            console.log('[UnifiedCart] Item removed successfully');
        }
    }
    
    updateQuantity(itemId, quantity) {
        console.log('[UnifiedCart] Updating quantity for item:', itemId, 'to:', quantity);
        
        const item = this.items.find(i => i.id === itemId);
        if (item) {
            item.quantity = Math.max(1, parseInt(quantity));
            this.saveToStorage();
            this.updateCartCount();
            this.dispatchCartUpdateEvent();
            
            if (this.isCartPage()) {
                this.updateCartSummary();
            }
        }
    }
    
    clearCart() {
        if (confirm('Weet je zeker dat je de winkelwagen wilt leegmaken?')) {
            console.log('[UnifiedCart] Clearing cart');
            this.items = [];
            this.saveToStorage();
            this.updateCartCount();
            this.dispatchCartUpdateEvent();
            
            if (this.isCartPage()) {
                this.renderCartItems();
                this.updateCartSummary();
            }
            
            this.showNotification('Winkelwagen is leeggemaakt', 'info');
        }
    }
    
    updateCartCount() {
        const count = this.items.reduce((total, item) => total + item.quantity, 0);
        console.log('[UnifiedCart] Updating cart count to:', count);
        
        // Update all cart count elements in DOM
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(element => {
            element.textContent = count;
            element.style.display = count > 0 ? 'flex' : 'none';
        });
        
        // Trigger UnifiedHeader update if available
        if (window.UnifiedHeader && window.UnifiedHeader.prototype.updateCartCount) {
            window.dispatchEvent(new CustomEvent('cartUpdated', { detail: { count } }));
        }
        
        console.log('[UnifiedCart] Cart count updated on', cartCountElements.length, 'elements');
    }
    
    dispatchCartUpdateEvent() {
        window.dispatchEvent(new CustomEvent('cartUpdated', {
            detail: {
                items: this.items,
                count: this.items.reduce((total, item) => total + item.quantity, 0),
                total: this.getCartTotal()
            }
        }));
    }
    
    isCartPage() {
        return window.location.pathname.includes('/winkelwagen') || 
               document.querySelector('.cart-section') !== null;
    }
    
    initCartPage() {
        console.log('[UnifiedCart] Initializing cart page functionality');
        
        this.renderCartItems();
        this.updateCartSummary();
        this.setupCartPageEvents();
    }
    
    renderCartItems() {
        const container = document.getElementById('cart-items');
        if (!container) {
            console.log('[UnifiedCart] Cart items container not found');
            return;
        }
        
        if (this.items.length === 0) {
            container.innerHTML = this.getEmptyCartHTML();
            return;
        }
        
        container.innerHTML = this.items.map(item => this.getCartItemHTML(item)).join('');
    }
    
    getEmptyCartHTML() {
        return `
            <div class="empty-cart-message">
                <div class="empty-cart-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="m1 1 4 4 14 1-1 3H6"></path>
                    </svg>
                </div>
                <h3>Je winkelwagen is leeg</h3>
                <p>Ontdek onze handige AI-tools en e-learnings om aan de slag te gaan.</p>
                <div class="empty-cart-actions">
                    <a href="/tools" class="btn btn-primary">Bekijk Tools</a>
                    <a href="/e-learnings" class="btn btn-outline">Bekijk Cursussen</a>
                </div>
            </div>
        `;
    }
    
    getCartItemHTML(item) {
        const total = item.price * item.quantity;
        
        return `
            <div class="cart-item" data-item-id="${item.id}">
                <div class="cart-item-image">
                    ${item.img ? 
                        `<img src="${item.img}" alt="${item.name}" loading="lazy">` : 
                        `<div class="cart-item-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="9" cy="9" r="2"></circle>
                                <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
                            </svg>
                        </div>`
                    }
                </div>
                <div class="cart-item-details">
                    <h4 class="cart-item-name">${item.name}</h4>
                    <p class="cart-item-type">${this.getProductTypeLabel(item.type)}</p>
                    <p class="cart-item-price">€${item.price.toFixed(2)}</p>
                </div>
                <div class="cart-item-quantity">
                    <button class="quantity-btn decrease" data-action="decrease" data-item-id="${item.id}" aria-label="Verminder aantal">-</button>
                    <input type="number" class="quantity-input" value="${item.quantity}" min="1" data-item-id="${item.id}" aria-label="Aantal items">
                    <button class="quantity-btn increase" data-action="increase" data-item-id="${item.id}" aria-label="Verhoog aantal">+</button>
                </div>
                <div class="cart-item-total">
                    <span class="item-total">€${total.toFixed(2)}</span>
                    <button class="remove-item-btn" data-item-id="${item.id}" aria-label="Verwijder ${item.name}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3,6 5,6 21,6"></polyline>
                            <path d="m19,6-1,12a2,2 0 0,1-2,2H8a2,2 0 0,1-2-2L5,6m5,0V4a2,2 0 0,1,2-2h0a2,2 0 0,1,2,2V6"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;
    }
    
    setupCartPageEvents() {
        // Quantity controls
        document.addEventListener('click', (e) => {
            if (e.target.matches('.quantity-btn')) {
                const action = e.target.dataset.action;
                const itemId = e.target.dataset.itemId;
                const item = this.items.find(i => i.id === itemId);
                
                if (item) {
                    if (action === 'increase') {
                        this.updateQuantity(itemId, item.quantity + 1);
                    } else if (action === 'decrease') {
                        this.updateQuantity(itemId, Math.max(1, item.quantity - 1));
                    }
                    
                    // Update the input value
                    const input = document.querySelector(`.quantity-input[data-item-id="${itemId}"]`);
                    if (input) {
                        input.value = item.quantity;
                    }
                }
            }
            
            if (e.target.matches('.remove-item-btn') || e.target.closest('.remove-item-btn')) {
                const button = e.target.closest('.remove-item-btn');
                const itemId = button.dataset.itemId;
                this.removeItem(itemId);
            }
        });
        
        // Quantity input changes
        document.addEventListener('change', (e) => {
            if (e.target.matches('.quantity-input')) {
                const itemId = e.target.dataset.itemId;
                const quantity = parseInt(e.target.value);
                this.updateQuantity(itemId, quantity);
            }
        });
        
        // Clear cart button
        const clearCartBtn = document.getElementById('clear-cart-btn');
        if (clearCartBtn) {
            clearCartBtn.addEventListener('click', () => this.clearCart());
        }
    }
    
    updateCartSummary() {
        const subtotal = this.getCartSubtotal();
        const tax = this.getCartTax();
        const total = this.getCartTotal();
        
        // Update summary elements
        this.updateElement('.cart-subtotal', `€${subtotal.toFixed(2)}`);
        this.updateElement('.cart-tax', `€${tax.toFixed(2)}`);
        this.updateElement('.cart-total', `€${total.toFixed(2)}`);
        
        // Update checkout button state
        const checkoutBtn = document.getElementById('checkout-btn');
        if (checkoutBtn) {
            checkoutBtn.disabled = this.items.length === 0;
            if (this.items.length === 0) {
                checkoutBtn.textContent = 'Winkelwagen is leeg';
            } else {
                checkoutBtn.textContent = `Afrekenen (€${total.toFixed(2)})`;
            }
        }
    }
    
    updateElement(selector, content) {
        const element = document.querySelector(selector);
        if (element) {
            element.textContent = content;
        }
    }
    
    getCartSubtotal() {
        return this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
    }
    
    getCartTax() {
        return this.getCartSubtotal() * 0.21; // 21% BTW
    }
    
    getCartTotal() {
        return this.getCartSubtotal() + this.getCartTax();
    }
    
    getProductTypeLabel(type) {
        const labels = {
            'tool': 'AI Tool',
            'course': 'E-learning',
            'product': 'Product'
        };
        return labels[type] || 'Product';
    }
    
    showAddToCartFeedback(item) {
        this.showNotification(`${item.name} toegevoegd aan winkelwagen!`, 'success');
    }
    
    showNotification(message, type = 'info') {
        // Create or update notification
        let notification = document.getElementById('cart-notification');
        
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'cart-notification';
            notification.className = 'cart-notification';
            document.body.appendChild(notification);
        }
        
        notification.className = `cart-notification ${type} show`;
        notification.textContent = message;
        
        // Auto hide after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
        
        console.log('[UnifiedCart] Notification shown:', message);
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    // Public API methods
    getItems() {
        return [...this.items];
    }
    
    getItemCount() {
        return this.items.reduce((total, item) => total + item.quantity, 0);
    }
    
    hasItem(itemId) {
        return this.items.some(item => item.id === itemId);
    }
    
    reset() {
        this.items = [];
        this.saveToStorage();
        this.updateCartCount();
        this.dispatchCartUpdateEvent();
        
        if (this.isCartPage()) {
            this.renderCartItems();
            this.updateCartSummary();
        }
        
        console.log('[UnifiedCart] Cart reset');
    }
}

// Initialize cart when DOM is ready
let cartInstance = null;

function initializeUnifiedCart() {
    if (cartInstance) {
        console.log('[UnifiedCart] Already initialized, returning existing instance');
        return cartInstance;
    }
    
    cartInstance = new UnifiedCart();
    
    // Make globally available for backward compatibility
    window.Cart = cartInstance;
    window.UnifiedCart = UnifiedCart;
    
    return cartInstance;
}

// Auto-initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeUnifiedCart);
} else {
    initializeUnifiedCart();
}

// Export for module usage
export { UnifiedCart, initializeUnifiedCart };

console.log('[UnifiedCart] Module loaded'); 