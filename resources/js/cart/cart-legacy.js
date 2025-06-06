// Winkelwagen functionaliteit voor Slimmer met AI

// Cart object voor het beheren van winkelwagengegevens
const Cart = {
    items: [],
    initialized: false,
    
    // Initialisatie
    init: function(forceReload = false) {
        // Voorkom dubbele initialisatie
        if (this.initialized && !forceReload) {
            console.log('Cart is al geïnitialiseerd, gebruik Cart.init(true) om opnieuw te laden');
            return;
        }
        
        // Laad uit storage in plaats van te resetten
        this.loadFromStorage();
        
        this.renderCartCount();
        
        // Pagina-specifieke initialisaties
        if (document.querySelector('.cart-section')) {
            this.renderCartItems();
            this.updateCartSummary();
            
            // Event listener voor "Leeg winkelwagen" knop
            const clearCartBtn = document.getElementById('clear-cart-btn');
            if (clearCartBtn) {
                clearCartBtn.addEventListener('click', this.clearCart.bind(this));
            }
        }
        
        // Event listeners voor "Toevoegen aan winkelwagen" knoppen
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.getAttribute('data-product-id');
                const productType = this.getAttribute('data-product-type');
                const productName = this.getAttribute('data-product-name');
                const productPrice = parseFloat(this.getAttribute('data-product-price'));
                const productImg = this.getAttribute('data-product-img');
                
                Cart.addItem({
                    id: productId,
                    type: productType,
                    name: productName,
                    price: productPrice, // Prijs is nu inclusief BTW
                    img: productImg,
                    quantity: 1
                });
                
                // Toon bevestigingsmelding
                showNotification(`${productName} is toegevoegd aan je winkelwagen!`, 'success');
            });
        });
        
        // Event listener voor checkout knop
        const checkoutBtn = document.getElementById('checkout-btn');
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', this.checkout.bind(this));
        }
        
        this.initialized = true;
    },
    
    // Laad cart gegevens uit localStorage
    loadFromStorage: function() {
        let savedCart = localStorage.getItem('slimmerAICart');
        
        // Controleer of er gegevens zijn in het oude formaat (migratie)
        if (!savedCart) {
            const oldCart = localStorage.getItem('cart');
            if (oldCart) {
                console.log('Oude winkelwagendata gevonden, migreren naar nieuw formaat...');
                try {
                    // Migreer data van oud naar nieuw formaat
                    const oldCartData = JSON.parse(oldCart);
                    this.items = oldCartData;
                    // Sla direct op in nieuw formaat
                    this.saveToStorage();
                    // Verwijder oude data
                    localStorage.removeItem('cart');
                    console.log('Migratie voltooid.');
                    
                    // Update winkelwagenteller nadat de items zijn geladen
                    this.renderCartCount();
                    
                    return; // We hebben de data al geladen in this.items
                } catch (e) {
                    console.error('Fout bij migreren van oude winkelwagendata:', e);
                }
            }
        }
        
        // Normale verwerking voor nieuwe data
        if (savedCart) {
            try {
                this.items = JSON.parse(savedCart);
                // Geen filtering meer, we behouden alle items
                this.saveToStorage();
                
                // Update winkelwagenteller nadat de items zijn geladen
                this.renderCartCount();
                
            } catch (e) {
                console.error('Fout bij laden van winkelwagen:', e);
                this.items = [];
            }
        }
        
        // Zorg dat de winkelwagenteller altijd wordt bijgewerkt
        this.renderCartCount();
    },
    
    // Bewaar cart gegevens in localStorage
    saveToStorage: function() {
        localStorage.setItem('slimmerAICart', JSON.stringify(this.items));
    },
    
    // Voeg een item toe aan de winkelwagen
    addItem: function(item) {
        // Controleer of het item al in de winkelwagen zit
        const existingItem = this.items.find(i => i.id === item.id);
        
        if (existingItem) {
            existingItem.quantity += item.quantity;
        } else {
            this.items.push(item);
        }
        
        this.saveToStorage();
        this.renderCartCount();
        
        // Update de winkelwagen pagina als we daar zijn
        if (document.querySelector('.cart-section')) {
            this.renderCartItems();
            this.updateCartSummary();
        }
    },
    
    // Verwijder een item uit de winkelwagen
    removeItem: function(itemId) {
        this.items = this.items.filter(item => item.id !== itemId);
        this.saveToStorage();
        this.renderCartCount();
        
        // Update de winkelwagen pagina
        if (document.querySelector('.cart-section')) {
            this.renderCartItems();
            this.updateCartSummary();
        }
    },
    
    // Update de hoeveelheid van een item
    updateQuantity: function(itemId, quantity) {
        const item = this.items.find(i => i.id === itemId);
        if (item) {
            item.quantity = Math.max(1, quantity); // Minimum 1
            this.saveToStorage();
            
            // Update de winkelwagen pagina
            if (document.querySelector('.cart-section')) {
                this.renderCartItems();
                this.updateCartSummary();
            }
        }
    },
    
    // Toon aantal items in winkelwagen
    renderCartCount: function() {
        const cartCountElements = document.querySelectorAll('.cart-count');
        const itemCount = this.items.reduce((total, item) => total + item.quantity, 0);
        
        cartCountElements.forEach(element => {
            element.textContent = itemCount;
            
            // Toon of verberg het aantal op basis van of er items zijn
            if (itemCount > 0) {
                element.style.display = 'flex';
            } else {
                element.style.display = 'none';
            }
        });
    },
    
    // Toon items in de winkelwagen op de winkelwagen pagina
    renderCartItems: function() {
        const cartItemsContainer = document.getElementById('cart-items');
        if (!cartItemsContainer) return;
        
        // Leeg de huidige inhoud
        cartItemsContainer.innerHTML = '';
        
        if (this.items.length === 0) {
            // Toon lege-winkelwagen bericht
            cartItemsContainer.innerHTML = `
                <div class="empty-cart-message">
                    <p>Je winkelwagen is leeg</p>
                    <a href="tools.html" class="btn btn-primary">Bekijk Tools</a>
                    <a href="e-learnings.html" class="btn btn-outline">Bekijk Cursussen</a>
                </div>
            `;
            return;
        }
        
        // Loop door items en maak HTML voor elk item
        this.items.forEach(item => {
            const itemElement = document.createElement('div');
            itemElement.className = 'cart-item';
            itemElement.innerHTML = `
                <div class="cart-item-image">
                    <img src="${item.img}" alt="${item.name}">
                </div>
                <div class="cart-item-details">
                    <h3>${item.name}</h3>
                    <span class="product-type">${item.type}</span>
                    <div class="quantity-controls">
                        <button class="quantity-btn decrease" data-item-id="${item.id}">-</button>
                        <span class="quantity">${item.quantity}</span>
                        <button class="quantity-btn increase" data-item-id="${item.id}">+</button>
                    </div>
                </div>
                <div class="cart-item-price">
                    <span>€${(item.price * item.quantity).toFixed(2).replace('.', ',')}</span>
                    <button class="remove-item-btn" data-item-id="${item.id}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6L6 18"></path>
                            <path d="M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            cartItemsContainer.appendChild(itemElement);
        });
        
        // Voeg event listeners toe voor knoppen
        this.addCartItemEventListeners();
    },
    
    // Voeg event listeners toe aan knoppen in de winkelwagen
    addCartItemEventListeners: function() {
        // Verwijder-knoppen
        document.querySelectorAll('.remove-item-btn').forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.getAttribute('data-item-id');
                Cart.removeItem(itemId);
            });
        });
        
        // Hoeveelheid verhogen
        document.querySelectorAll('.quantity-btn.increase').forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.getAttribute('data-item-id');
                const item = Cart.items.find(i => i.id === itemId);
                if (item) {
                    Cart.updateQuantity(itemId, item.quantity + 1);
                }
            });
        });
        
        // Hoeveelheid verlagen
        document.querySelectorAll('.quantity-btn.decrease').forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.getAttribute('data-item-id');
                const item = Cart.items.find(i => i.id === itemId);
                if (item && item.quantity > 1) {
                    Cart.updateQuantity(itemId, item.quantity - 1);
                }
            });
        });
    },
    
    // Update de samenvatting van de winkelwagen (subtotaal, BTW, totaal)
    updateCartSummary: function() {
        const subtotalElement = document.getElementById('cart-subtotal');
        const taxElement = document.getElementById('cart-tax');
        const totalElement = document.getElementById('cart-total');
        const checkoutBtn = document.getElementById('checkout-btn');
        
        if (!subtotalElement || !taxElement || !totalElement) return;
        
        // Bereken totaal (prijs is al inclusief BTW)
        const total = this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
        // BTW berekenen (21% van het totaal / 1.21)
        const tax = total - (total / 1.21);
        // Subtotaal (zonder BTW)
        const subtotal = total - tax;
        
        // Waarden bijwerken
        subtotalElement.textContent = `€${subtotal.toFixed(2).replace('.', ',')}`;
        taxElement.textContent = `€${tax.toFixed(2).replace('.', ',')}`;
        totalElement.textContent = `€${total.toFixed(2).replace('.', ',')}`;
        
        // Checkout knop in- of uitschakelen
        if (checkoutBtn) {
            if (this.items.length > 0) {
                checkoutBtn.disabled = false;
            } else {
                checkoutBtn.disabled = true;
            }
        }
    },
    
    // Afrekenen functie
    checkout: function() {
        // Hier zou je normaal doorsturen naar een betaalpagina
        alert('Je wordt doorgestuurd naar de betaalpagina...');
        // Voor demo doeleinden maken we de winkelwagen leeg
        this.items = [];
        this.saveToStorage();
        this.renderCartCount();
        this.renderCartItems();
        this.updateCartSummary();
    },
    
    // Winkelwagen leegmaken
    clearCart: function() {
        // Bevestiging vragen
        if (confirm('Weet je zeker dat je de winkelwagen wilt leegmaken?')) {
            this.items = [];
            this.saveToStorage();
            this.renderCartCount();
            
            // Update de winkelwagen pagina
            if (document.querySelector('.cart-section')) {
                this.renderCartItems();
                this.updateCartSummary();
                
                // De afrekenen knop uitschakelen
                const checkoutBtn = document.getElementById('checkout-btn');
                if (checkoutBtn) {
                    checkoutBtn.disabled = true;
                }
                
                // Toon bevestigingsmelding
                showNotification('Je winkelwagen is leeggemaakt.', 'info');
            }
        }
    },
    
    // Reset de winkelwagen volledig
    resetCart: function(showNotice = true) {
        this.items = [];
        this.saveToStorage();
        this.renderCartCount();
        
        if (document.querySelector('.cart-section')) {
            this.renderCartItems();
            this.updateCartSummary();
        }
        
        if (showNotice) {
            console.log('Winkelwagen is volledig gereset');
            if (typeof showNotification === 'function') {
                showNotification('Winkelwagen is gereset.', 'info');
            }
        }
    }
};

// Notificatie functie (hergebruikt van main.js, zorg dat deze ook hier beschikbaar is)
function showNotification(message, type = 'info', duration = 3000) {
    // Controleer of er al een notificatie container is
    let notificationContainer = document.querySelector('.notification-container');
    
    if (!notificationContainer) {
        // Maak container als die nog niet bestaat
        notificationContainer = document.createElement('div');
        notificationContainer.className = 'notification-container';
        document.body.appendChild(notificationContainer);
    }
    
    // Maak nieuwe notificatie
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span>${message}</span>
            <button class="notification-close">×</button>
        </div>
    `;
    
    // Voeg toe aan container
    notificationContainer.appendChild(notification);
    
    // Close knop
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', function() {
        closeNotification(notification);
    });
    
    // Automatisch sluiten na duration
    setTimeout(function() {
        closeNotification(notification);
    }, duration);
}

// Helper functie voor het sluiten van notificaties
function closeNotification(notification) {
    notification.classList.add('fade-out');
    setTimeout(function() {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

// We initialiseren de winkelwagen niet meer hier, dit gebeurt nu via main.js
// om dubbele initialisatie te voorkomen 