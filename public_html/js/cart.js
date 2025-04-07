// Winkelwagen functionaliteit voor Slimmer met AI

// Cart object voor het beheren van winkelwagengegevens
const Cart = {
    items: [],
    initialized: false,
    
    // Initialisatie
    init: function(forceReload = false) {
        console.log('[Cart.init] Starting initialization...'); // ALLEREERSTE LOG
        // Voorkom dubbele initialisatie, tenzij forceReload is true
        if (this.initialized && !forceReload) {
            console.log('[Cart.init] Already initialized, exiting. Use forceReload=true if needed.');
            return;
        }
        
        console.log('[Cart.init] Initialization process continues...');
        
        // Laad uit storage in plaats van te resetten
        this.loadFromStorage();
        
        // Update cart tellertje
        this.renderCartCount();
        
        // Pagina-specifieke initialisaties
        if (document.querySelector('.cart-section')) {
            console.log('Op winkelwagen pagina, items worden weergegeven');
            this.renderCartItems();
            this.updateCartSummary();
            
            // Event listener voor "Leeg winkelwagen" knop
            const clearCartBtn = document.getElementById('clear-cart-btn');
            if (clearCartBtn) {
                clearCartBtn.addEventListener('click', this.clearCart.bind(this));
            }
        }
        
        // NIEUWE MANIER: Event Delegation op document body
        document.body.addEventListener('click', function(e) {
            // Controleer of het geklikte element (of een ouder) de knop is
            const button = e.target.closest('.add-to-cart-btn');
            
            if (button) {
                console.log('[CART EVENT - Delegated] Add to cart button clicked:', button); // LOG 1
                e.preventDefault(); // Voorkom standaard actie

                const productId = button.getAttribute('data-product-id');
                const productType = button.getAttribute('data-product-type');
                const productName = button.getAttribute('data-product-name');
                const productPrice = parseFloat(button.getAttribute('data-product-price'));
                const productImg = button.getAttribute('data-product-img');
                
                console.log('[CART EVENT - Delegated] Product data extracted:', { productId, productType, productName, productPrice, productImg }); // LOG 2

                if (!productId || !productName || isNaN(productPrice)) {
                    console.error('[CART EVENT - Delegated] Incomplete product data found on button:', button);
                    if (typeof showNotification === 'function') { // Check if function exists
                       showNotification('Kon product niet toevoegen: onvolledige data.', 'error');
                    } else {
                       console.error('showNotification function not available');
                    }
                    return; // Stop als data incompleet is
                }

                const itemToAdd = {
                    id: productId,
                    type: productType,
                    name: productName,
                    price: productPrice, // Prijs is nu inclusief BTW
                    img: productImg,
                    quantity: 1
                };

                console.log('[CART EVENT - Delegated] Calling Cart.addItem with:', itemToAdd); // LOG 3
                Cart.addItem(itemToAdd);

                console.log('[CART EVENT - Delegated] Cart.addItem call finished. Showing notification.'); // LOG 7 (Na addItem)
                // Toon bevestigingsmelding
                 if (typeof showNotification === 'function') { // Check if function exists
                    showNotification(`${productName} is toegevoegd aan je winkelwagen!`, 'success');
                 } else {
                    console.error('showNotification function not available');
                 }
            }
            // Geen else nodig, als er niet op de knop is geklikt, gebeurt er niets
        });
        
        // Event listener voor checkout knop
        const checkoutBtn = document.getElementById('checkout-btn');
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', this.checkout.bind(this));
        }
        
        this.initialized = true;
        
        // Log aantal items voor debugging
        console.log(`Cart geïnitialiseerd met ${this.items.length} items:`, this.items);
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
                console.log('Winkelwagen geladen uit localStorage:', this.items);
            } catch (e) {
                console.error('Fout bij laden van winkelwagen:', e);
                this.items = [];
            }
        } else {
            console.log('Geen winkelwagen gevonden in localStorage');
            this.items = [];
        }
    },
    
    // Bewaar cart gegevens in localStorage
    saveToStorage: function() {
        localStorage.setItem('slimmerAICart', JSON.stringify(this.items));
        console.log('Winkelwagen opgeslagen in localStorage');
    },
    
    // Voeg een item toe aan de winkelwagen
    addItem: function(item) {
        console.log('[addItem] Function started. Item to add:', item);
        // Controleer of het item al in de winkelwagen zit
        const existingItem = this.items.find(i => i.id === item.id);
        console.log('[addItem] Existing item check complete. Found:', existingItem);

        if (existingItem) {
            console.log('[addItem] Item exists, increasing quantity.');
            existingItem.quantity += item.quantity;
        } else {
            console.log('[addItem] Item is new, pushing to items array.');
            this.items.push(item);
        }
        console.log('[addItem] Current items array:', this.items);

        console.log('[addItem] Calling saveToStorage...');
        this.saveToStorage();
        console.log('[addItem] saveToStorage finished.');
        console.log('[addItem] Calling renderCartCount...');
        this.renderCartCount();
        console.log('[addItem] renderCartCount finished.');

        // Update de winkelwagen pagina als we daar zijn
        if (document.querySelector('.cart-section')) {
            console.log('[addItem] On cart page, calling renderCartItems and updateCartSummary.');
            this.renderCartItems();
            this.updateCartSummary();
        }
        console.log('[addItem] Function finished.');
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
        // Directe DOM elementen
        const cartCountElements = document.querySelectorAll('.cart-count:not(slimmer-navbar .cart-count)'); // Selecteer alleen tellers buiten de navbar
        const itemCount = this.items.reduce((total, item) => total + item.quantity, 0);
        
        // Update reguliere DOM elementen (indien aanwezig buiten navbar)
        cartCountElements.forEach(element => {
            element.textContent = itemCount;
            element.style.display = itemCount > 0 ? 'flex' : 'none';
        });
        
        // Update alleen het attribuut op de navbar component
        const navbarElement = document.querySelector('slimmer-navbar');
        if (navbarElement) {
            // Update het cart-count attribuut van de navbar
            navbarElement.setAttribute('cart-count', itemCount.toString());
            console.log(`Navbar cart-count attribuut bijgewerkt naar: ${itemCount}`);
            
            // Verwijder directe shadow DOM manipulatie
            /* 
            if (navbarElement.shadowRoot) {
                const shadowCartCounts = navbarElement.shadowRoot.querySelectorAll('.cart-count');
                shadowCartCounts.forEach(element => {
                    element.textContent = itemCount;
                    if (itemCount > 0) {
                        element.style.display = 'flex';
                    } else {
                        element.style.display = 'none';
                    }
                });
            }
            */
        }
    },
    
    // Toon items in de winkelwagen op de winkelwagen pagina
    renderCartItems: function() {
        const cartItemsContainer = document.getElementById('cart-items');
        if (!cartItemsContainer) {
            console.log('cart-items container niet gevonden');
            return;
        }
        
        console.log('Renderen van winkelwagen items:', this.items.length);
        
        // Leeg de huidige inhoud
        cartItemsContainer.innerHTML = '';
        
        if (this.items.length === 0) {
            // Toon lege-winkelwagen bericht
            cartItemsContainer.innerHTML = `
                <div class="empty-cart-message">
                    <p>Je winkelwagen is leeg</p>
                    <a href="tools.php" class="btn btn-primary">Bekijk Tools</a>
                    <a href="e-learnings.php" class="btn btn-outline">Bekijk Cursussen</a>
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

// We initialiseren de winkelwagen niet meer hier, dit gebeurt nu via main.js
// om dubbele initialisatie te voorkomen 
