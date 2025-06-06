// Core modules
import './components.js';
import './course-filters.js';

console.log('=== MAIN.JS LOADED ===');
console.log('Main.js timestamp:', new Date().toISOString());
console.log('[DEBUG] Main.js file execution started');

// Hoofdscript voor de Slimmer met AI website
import '../../css/style.css';
import '../../css/components/navbar.css';
import '../../css/components/footer.css';
import '../../css/components/hero.css';
import '../../css/components/testimonials.css';
import '../../css/components/slider.css';
import '../../css/components/cta.css';
import '../../css/components/notification.css';
import '../../css/components/cart-notification.css';

console.log('[DEBUG] CSS imports completed');

// Global Cart object voor Stripe compatibility
window.Cart = {
    calculateTotal: function() {
        const cart = JSON.parse(localStorage.getItem('slimmerAICart') || '[]');
        const subtotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
        console.log('[Cart.calculateTotal] Calculated total:', subtotal);
        return subtotal;
    },
    
    getItems: function() {
        return JSON.parse(localStorage.getItem('slimmerAICart') || '[]');
    },
    
    // NIEUWE ITEMS PROPERTY VOOR COMPATIBILITY
    get items() {
        return this.getItems();
    },
    
    getTotalWithTax: function() {
        const subtotal = this.calculateTotal();
        const tax = subtotal * 0.21; // 21% BTW
        return subtotal + tax;
    }
};

// Global showNotification functie
window.showNotification = function(message, type = 'info') {
    console.log(`[Notification ${type}]:`, message);
    
    // Probeer bestaande notification systeem te gebruiken
    if (typeof window.createNotification === 'function') {
        window.createNotification(message, type);
        return;
    }
    
    // Fallback: simpele alert voor nu
    if (type === 'error') {
        alert('Error: ' + message);
    } else if (type === 'success') {
        alert('Success: ' + message);
    } else {
        alert('Info: ' + message);
    }
};

// Check JS beschikbaarheid voor fallback
document.documentElement.classList.remove('no-js');
document.documentElement.classList.add('js');

console.log('[DEBUG] Document classes updated');

// Direct na het laden van de pagina
document.addEventListener('DOMContentLoaded', function() {
    console.log('[main.js] DOMContentLoaded fired.');
    console.log('[DEBUG] DOMContentLoaded event triggered');
    
    // Update cart count bij pagina load
    updateAllCartCounts();
    
    console.log('[DEBUG] About to set up cart event listener');
    
    // DIRECT: Voeg cart event listeners toe
    document.body.addEventListener('click', function(e) {
        console.log('[CART DEBUG] Click detected on:', e.target);
        console.log('[CART DEBUG] Target classes:', e.target.className);
        console.log('[CART DEBUG] Closest button:', e.target.closest('.add-to-cart-btn'));
        
        if (e.target.matches('.add-to-cart-btn') || e.target.closest('.add-to-cart-btn')) {
            console.log('[DIRECT] Add to cart button clicked!');
            e.preventDefault();
            e.stopPropagation();
            
            const button = e.target.closest('.add-to-cart-btn');
            console.log('[CART DEBUG] Button found:', button);
            console.log('[CART DEBUG] Button data attributes:', {
                id: button.dataset.productId,
                name: button.dataset.productName,
                price: button.dataset.productPrice,
                type: button.dataset.productType,
                img: button.dataset.productImg
            });
            
            const item = {
                id: button.dataset.productId,
                name: button.dataset.productName,
                price: parseFloat(button.dataset.productPrice),
                type: button.dataset.productType,
                img: button.dataset.productImg
            };
            
            console.log('[CART DEBUG] Item to add:', item);
            
            // Gebruik eenvoudige cart functionaliteit
            let cart = JSON.parse(localStorage.getItem('slimmerAICart') || '[]');
            console.log('[CART DEBUG] Current cart:', cart);
            
            const existing = cart.find(i => i.id === item.id);
            if (existing) {
                existing.quantity += 1;
                console.log('[CART DEBUG] Updated existing item quantity:', existing.quantity);
            } else {
                cart.push({...item, quantity: 1});
                console.log('[CART DEBUG] Added new item to cart');
            }
            localStorage.setItem('slimmerAICart', JSON.stringify(cart));
            console.log('[CART DEBUG] Saved cart to localStorage:', cart);
            
            // Update cart count voor alle implementaties
            updateAllCartCounts();
            
            alert(`${item.name} toegevoegd aan winkelwagen!`);
            console.log('[DIRECT] Item added to cart:', item);
        } else {
            console.log('[CART DEBUG] Click was not on add-to-cart button');
        }
    });
    
    console.log('[DEBUG] Cart event listener attached successfully');
    
    // Debug: Toon alle add-to-cart buttons
    setTimeout(() => {
        const buttons = document.querySelectorAll('.add-to-cart-btn');
        console.log('[CART DEBUG] Found', buttons.length, 'add-to-cart buttons');
        buttons.forEach((btn, index) => {
            console.log(`[CART DEBUG] Button ${index + 1}:`, {
                element: btn,
                visible: btn.offsetParent !== null,
                disabled: btn.disabled,
                style: btn.style.cssText,
                classes: btn.className,
                pointerEvents: getComputedStyle(btn).pointerEvents
            });
        });
    }, 2000);
    
    console.log('[DEBUG] DOMContentLoaded setup completed');
});

console.log('[DEBUG] DOMContentLoaded listener attached');

// Multi-fallback cart count systeem
function updateAllCartCounts() {
    const cart = JSON.parse(localStorage.getItem('slimmerAICart') || '[]');
    const count = cart.reduce((total, item) => total + item.quantity, 0);
    
    console.log('[updateAllCartCounts] Updating cart count to:', count);
    
    // 1. Update unified header cart count
    const unifiedCartCount = document.getElementById('unified-cart-count');
    if (unifiedCartCount) {
        unifiedCartCount.textContent = count;
        if (count > 0) {
            unifiedCartCount.classList.add('visible');
        } else {
            unifiedCartCount.classList.remove('visible');
        }
        console.log('[updateAllCartCounts] Updated unified cart count');
    }
    
    // 2. Update all elements with class cart-count
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(element => {
        element.textContent = count;
        if (count > 0) {
            element.classList.add('visible');
        } else {
            element.classList.remove('visible');
        }
    });
    console.log('[updateAllCartCounts] Updated', cartCountElements.length, 'cart-count elements');
    
    // 3. Custom event voor andere componenten
    window.dispatchEvent(new CustomEvent('cartUpdated', { detail: { count } }));
}

console.log('[DEBUG] Main.js file execution completed'); 