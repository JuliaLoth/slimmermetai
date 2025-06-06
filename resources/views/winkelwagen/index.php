<?php /** @var string $title */ ?>

<a href="#main-content" class="skip-link">Direct naar inhoud</a>

<section class="hero-with-background" aria-labelledby="cart-heading">
    <div class="container">
        <div class="hero-content">
            <h1 id="cart-heading">Winkelwagen</h1>
            <p>Bekijk je geselecteerde items en rond je bestelling af</p>
        </div>
    </div>
</section>

<section class="section cart-section" id="main-content">
    <div class="container">
        <div id="cart-items-container">
            <div id="cart-items">
                <div class="empty-cart-message">
                    <p>Je winkelwagen wordt geladen...</p>
                </div>
            </div>
            <div id="cart-summary" class="cart-summary">
                <h3>Samenvatting</h3>
                <div class="summary-row">
                    <span>Subtotaal</span>
                    <span id="cart-subtotal">€0,00</span>
                </div>
                <div class="summary-row">
                    <span>BTW (21%)</span>
                    <span id="cart-tax">€0,00</span>
                </div>
                <div class="summary-row total">
                    <span>Totaal</span>
                    <span id="cart-total">€0,00</span>
                </div>
                <button id="checkout-btn" class="btn btn-primary" disabled>Afrekenen</button>
                <button id="clear-cart-btn" class="btn btn-outline">Winkelwagen leegmaken</button>
            </div>
        </div>
    </div>
</section>

<?php use App\Infrastructure\Utils\Asset; ?>

<!-- LAAD NIEUWE JAVASCRIPT BUNDLES -->
<script type="module" src="<?= Asset::url('main') ?>"></script>
<script type="module" src="<?= Asset::url('cart') ?>"></script>
<script type="module" src="<?= Asset::url('stripe-payment') ?>"></script>

<script type="module">
// Winkelwagen pagina functionaliteit MET Cart.calculateTotal() ondersteuning
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Winkelwagen] Initializing cart page...');
    
    // Wacht tot Cart object geladen is
    function waitForCart(callback) {
        if (window.Cart && typeof window.Cart.calculateTotal === 'function') {
            callback();
        } else {
            console.log('[Winkelwagen] Waiting for Cart object...');
            setTimeout(() => waitForCart(callback), 100);
        }
    }
    
    waitForCart(() => {
        console.log('[Winkelwagen] Cart object available, initializing...');
        initializeCartPage();
    });
    
    function initializeCartPage() {
        loadCartItems();
        
        // Clear cart button
        document.getElementById('clear-cart-btn').addEventListener('click', function() {
            if (confirm('Weet je zeker dat je de winkelwagen wilt leegmaken?')) {
                localStorage.setItem('slimmerAICart', '[]');
                loadCartItems();
                
                if (typeof updateAllCartCounts === 'function') {
                    updateAllCartCounts();
                }
                
                alert('Winkelwagen is leeggemaakt.');
            }
        });
        
        // MODERNE CHECKOUT MET STRIPE-PAYMENT MODULE
        document.getElementById('checkout-btn').addEventListener('click', async function() {
            const cart = JSON.parse(localStorage.getItem('slimmerAICart') || '[]');
            if (cart.length === 0) {
                alert('Je winkelwagen is leeg.');
                return;
            }
            
            // Disable button
            this.disabled = true;
            this.textContent = 'Bezig met laden...';
            
            try {
                console.log('[Checkout] Using Cart.calculateTotal():', window.Cart.calculateTotal());
                
                // Gebruik StripePayment module als beschikbaar
                if (window.StripePayment && typeof window.StripePayment.handleCheckout === 'function') {
                    console.log('[Checkout] Using StripePayment module...');
                    await window.StripePayment.handleCheckout();
                } else {
                    // Fallback naar directe API call
                    console.log('[Checkout] Using fallback checkout...');
                    await fallbackCheckout(cart);
                }
                
            } catch (error) {
                console.error('[Checkout] Error:', error);
                alert('Er ging iets mis bij het starten van de betaling: ' + error.message);
                
                // Herstel button state
                this.disabled = false;
                this.textContent = 'Afrekenen';
            }
        });
    }
    
    async function fallbackCheckout(cart) {
        const checkoutData = {
            line_items: cart.map(item => ({
                price_data: {
                    currency: 'eur',
                    product_data: {
                        name: item.name || 'Product',
                        description: item.type || 'Product'
                    },
                    unit_amount: Math.round((item.price || 0) * 100),
                },
                quantity: parseInt(item.quantity || 1, 10)
            })),
            mode: 'payment',
            success_url: window.location.origin + '/betaling-succes',
            cancel_url: window.location.origin + '/winkelwagen'
        };
        
        const response = await fetch('/api/stripe/checkout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(checkoutData)
        });
        
        if (response.ok) {
            const data = await response.json();
            console.log('[Checkout] Response received:', data);
            
            if (data.success && data.data && data.data.session) {
                const session = data.data.session;
                
                // Check if this is a mock session (session ID starts with cs_test_mock_)
                if (session.id && session.id.startsWith('cs_test_mock_')) {
                    console.log('[Checkout] Mock session detected, redirecting to success URL');
                    
                    // Voor mock sessions, ga direct naar de success URL
                    if (session.url && session.url.includes('mock=true')) {
                        console.log('[Checkout] Using mock success URL:', session.url);
                        window.location.href = session.url;
                        return;
                    } else {
                        // Fallback: maak eigen mock success URL
                        const mockSuccessUrl = checkoutData.success_url + 
                            '?mock=true&session_id=' + session.id + 
                            '&total=' + (cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)).toFixed(2);
                        console.log('[Checkout] Using fallback mock URL:', mockSuccessUrl);
                        window.location.href = mockSuccessUrl;
                        return;
                    }
                } else {
                    // Echte Stripe session - gebruik normale redirect
                    console.log('[Checkout] Real Stripe session, using URL:', session.url);
                    if (session.url) {
                        window.location.href = session.url;
                        return;
                    }
                }
            }
            
            throw new Error('Geen geldige checkout URL ontvangen');
        } else {
            const errorText = await response.text();
            throw new Error(`Checkout API fout: ${response.status}`);
        }
    }
    
    function loadCartItems() {
        console.log('[Winkelwagen] Loading cart items from localStorage...');
        const cart = JSON.parse(localStorage.getItem('slimmerAICart') || '[]');
        console.log('[Winkelwagen] Cart items loaded:', cart);
        
        const cartItemsContainer = document.getElementById('cart-items');
        const emptyMessage = cartItemsContainer.querySelector('.empty-cart-message');
        
        if (cart.length === 0) {
            if (emptyMessage) {
                emptyMessage.innerHTML = '<p>Je winkelwagen is leeg. <a href="/tools">Ontdek onze tools</a> of <a href="/ai-cursussen">bekijk onze cursussen</a>.</p>';
            }
            updateCartSummary(0, 0);
            document.getElementById('checkout-btn').disabled = true;
            return;
        }
        
        // Verwijder empty message
        if (emptyMessage) {
            emptyMessage.remove();
        }
        
        // Render cart items
        let html = '';
        cart.forEach(item => {
            html += `
                <div class="cart-item" data-id="${item.id}">
                    <div class="cart-item-info">
                        <h4>${item.name}</h4>
                        <p class="cart-item-type">${item.type === 'tool' ? 'AI Tool' : 'AI Cursus'}</p>
                    </div>
                    <div class="cart-item-controls">
                        <div class="quantity-controls">
                            <button class="quantity-btn minus" onclick="updateQuantity('${item.id}', ${item.quantity - 1})">-</button>
                            <span class="quantity">${item.quantity}</span>
                            <button class="quantity-btn plus" onclick="updateQuantity('${item.id}', ${item.quantity + 1})">+</button>
                        </div>
                        <div class="cart-item-price">€${(item.price * item.quantity).toFixed(2)}</div>
                        <button class="remove-btn" onclick="removeItem('${item.id}')">×</button>
                    </div>
                </div>
            `;
        });
        
        cartItemsContainer.innerHTML = html;
        
        // Update summary
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const tax = subtotal * 0.21;
        updateCartSummary(subtotal, tax);
        
        document.getElementById('checkout-btn').disabled = false;
    }
    
    function updateCartSummary(subtotal, tax) {
        const total = subtotal + tax;
        document.getElementById('cart-subtotal').textContent = `€${subtotal.toFixed(2)}`;
        document.getElementById('cart-tax').textContent = `€${tax.toFixed(2)}`;
        document.getElementById('cart-total').textContent = `€${total.toFixed(2)}`;
    }
    
    // Global functions
    window.updateQuantity = function(itemId, newQuantity) {
        if (newQuantity < 1) return;
        
        let cart = JSON.parse(localStorage.getItem('slimmerAICart') || '[]');
        const item = cart.find(i => i.id === itemId);
        if (item) {
            item.quantity = newQuantity;
            localStorage.setItem('slimmerAICart', JSON.stringify(cart));
            loadCartItems();
            
            if (typeof updateAllCartCounts === 'function') {
                updateAllCartCounts();
            }
        }
    };
    
    window.removeItem = function(itemId) {
        let cart = JSON.parse(localStorage.getItem('slimmerAICart') || '[]');
        cart = cart.filter(item => item.id !== itemId);
        localStorage.setItem('slimmerAICart', JSON.stringify(cart));
        loadCartItems();
        
        if (typeof updateAllCartCounts === 'function') {
            updateAllCartCounts();
        }
    };
    
    console.log('[Winkelwagen] Cart page initialized successfully');
});
</script>

<!-- BESTAANDE CSS STYLING -->
<style>
.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 1rem;
    background: white;
}

.cart-item-info h4 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.cart-item-type {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.cart-item-controls {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-btn {
    width: 30px;
    height: 30px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.quantity-btn:hover {
    background: #f5f5f5;
}

.quantity {
    min-width: 20px;
    text-align: center;
    font-weight: 500;
}

.cart-item-price {
    font-weight: 600;
    color: #333;
    min-width: 80px;
    text-align: right;
}

.remove-btn {
    width: 30px;
    height: 30px;
    border: none;
    background: #ff4444;
    color: white;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.remove-btn:hover {
    background: #cc0000;
}

.cart-summary {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-top: 2rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.summary-row.total {
    font-weight: 600;
    font-size: 1.1rem;
    padding-top: 0.5rem;
    border-top: 1px solid #ddd;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    margin-top: 1rem;
    width: 100%;
}

.btn-primary {
    background: #8B5FBF;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #7A4FB3;
}

.btn-primary:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.btn-outline {
    background: transparent;
    border: 1px solid #ddd;
    color: #666;
}

.btn-outline:hover {
    background: #f5f5f5;
}

@media (max-width: 768px) {
    .cart-item {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .cart-item-controls {
        justify-content: space-between;
    }
}
</style> 