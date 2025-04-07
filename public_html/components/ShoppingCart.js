/**
 * ShoppingCart.js - Web component voor de winkelwagen functionaliteit
 * Versie: 1.0.0
 */

class ShoppingCart extends HTMLElement {
    constructor() {
        super();
        // Geen shadow DOM gebruiken, zodat we bestaande CSS kunnen hergebruiken
    }

    connectedCallback() {
        this.render();
        this.loadCartData();
        this.addEventListeners();
    }

    render() {
        this.innerHTML = `
            <div class="cart-container">
                <div class="cart-items" id="cart-items">
                    <!-- Items worden hier dynamisch toegevoegd -->
                </div>
                
                <div class="cart-summary">
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
                    <button id="checkout-btn" class="btn btn-primary btn-block" disabled>Afrekenen</button>
                    <button id="clear-cart-btn" class="btn btn-outline btn-block">Winkelwagen leegmaken</button>
                </div>
            </div>
        `;
    }

    loadCartData() {
        // Gebruik de bestaande Cart functionaliteit uit cart.js
        if (typeof Cart !== 'undefined') {
            // Force reinitialiseren om zeker te zijn dat items worden weergegeven
            Cart.init(true);
            
            // Direct renderCartItems en updateCartSummary aanroepen
            setTimeout(() => {
                Cart.renderCartItems();
                Cart.updateCartSummary();
            }, 100);
        } else {
            console.error('Cart object niet gevonden. Zorg ervoor dat cart.js is geladen.');
        }
    }

    addEventListeners() {
        // We hoeven geen event listeners toe te voegen, omdat deze al 
        // in de Cart.init() functie in cart.js worden toegevoegd
    }
}

// Registreer het component
customElements.define('slimmer-cart', ShoppingCart); 