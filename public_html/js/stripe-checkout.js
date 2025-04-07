/**
 * StripeCheckout
 * 
 * Een eenvoudige JavaScript klasse die de integratie met Stripe Checkout vereenvoudigt.
 * Deze klasse maakt API-verzoeken naar de backend om een checkout sessie te creëren
 * en stuurt de gebruiker door naar de Stripe Checkout pagina.
 */
class StripeCheckout {
    /**
     * Constructor
     * 
     * @param {string} apiUrl - De URL van de Stripe checkout API endpoint
     * @param {Object} options - Configuratie opties
     */
    constructor(apiUrl = '/api/stripe/create-checkout-session.php', options = {}) {
        this.apiUrl = apiUrl;
        this.options = Object.assign({
            successUrl: window.location.origin + '/betaling-succes.php?session_id={CHECKOUT_SESSION_ID}',
            cancelUrl: window.location.origin + '/winkelwagen?canceled=true',
            mode: 'payment',
            debug: false
        }, options);
        
        this.log('StripeCheckout geïnitialiseerd');
    }
    
    /**
     * Logger functie
     * 
     * @param {string} message - Het bericht om te loggen
     * @param {*} data - Optionele data om te loggen
     */
    log(message, data = null) {
        if (this.options.debug) {
            if (data) {
                console.log(`[StripeCheckout] ${message}`, data);
            } else {
                console.log(`[StripeCheckout] ${message}`);
            }
        }
    }
    
    /**
     * Verwerk een fout
     * 
     * @param {Error} error - De fout die is opgetreden
     * @returns {Object} - Foutobject
     */
    handleError(error) {
        const errorMessage = error.message || 'Er is een onbekende fout opgetreden.';
        this.log('Fout:', errorMessage);
        
        if (typeof this.options.onError === 'function') {
            this.options.onError(error);
        }
        
        return {
            error: true,
            message: errorMessage
        };
    }
    
    /**
     * Maak een checkout sessie aan en stuur de gebruiker door naar Stripe Checkout
     * 
     * @param {Array} lineItems - De producten voor in de winkelmand
     * @param {Object} metadata - Metadata voor de checkout sessie
     * @returns {Promise} - Promise die resolvet naar de checkout sessie of rejectet met een fout
     */
    async createCheckoutSession(lineItems, metadata = {}) {
        try {
            this.log('Aanvragen checkout sessie met producten:', lineItems);
            
            if (!lineItems || !Array.isArray(lineItems) || lineItems.length === 0) {
                throw new Error('Geen geldige producten opgegeven voor checkout.');
            }
            
            // Bereid de request data voor
            const requestData = {
                line_items: lineItems,
                mode: this.options.mode,
                success_url: this.options.successUrl,
                cancel_url: this.options.cancelUrl,
                metadata: metadata
            };
            
            // Voeg optioneel customer_email toe
            if (this.options.customerEmail) {
                requestData.customer_email = this.options.customerEmail;
            }
            
            this.log('Request data:', requestData);
            
            // Maak de checkout sessie aan
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(requestData)
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `API fout: ${response.status} ${response.statusText}`);
            }
            
            const sessionData = await response.json();
            this.log('Checkout sessie aangemaakt:', sessionData);
            
            // Redirect naar de Stripe Checkout pagina
            if (sessionData.url) {
                if (typeof this.options.onRedirect === 'function') {
                    this.options.onRedirect(sessionData);
                }
                
                window.location.href = sessionData.url;
            } else {
                throw new Error('Geen checkout URL ontvangen van de server.');
            }
            
            return sessionData;
            
        } catch (error) {
            return this.handleError(error);
        }
    }
    
    /**
     * Verwerk een product direct af via Stripe Checkout
     * 
     * @param {Object} product - Product informatie
     * @param {number} quantity - Aantal van het product
     * @param {Object} metadata - Metadata voor de checkout sessie
     * @returns {Promise} - Promise die resolvet naar de checkout sessie of rejectet met een fout
     */
    async checkoutProduct(product, quantity = 1, metadata = {}) {
        if (!product || !product.price_id) {
            return this.handleError(new Error('Geen geldig product opgegeven.'));
        }
        
        const lineItems = [{
            price: product.price_id,
            quantity: quantity
        }];
        
        return this.createCheckoutSession(lineItems, Object.assign({
            product_id: product.id || '',
            product_name: product.name || ''
        }, metadata));
    }
    
    /**
     * Verwerk de winkelwagen af via Stripe Checkout
     * 
     * @param {Array} cartItems - Producten in de winkelwagen
     * @param {Object} metadata - Metadata voor de checkout sessie
     * @returns {Promise} - Promise die resolvet naar de checkout sessie of rejectet met een fout
     */
    async checkoutCart(cartItems, metadata = {}) {
        if (!cartItems || !Array.isArray(cartItems) || cartItems.length === 0) {
            return this.handleError(new Error('Lege winkelwagen.'));
        }
        
        const lineItems = cartItems.map(item => ({
            price: item.price_id,
            quantity: item.quantity || 1
        }));
        
        return this.createCheckoutSession(lineItems, Object.assign({
            cart_id: metadata.cart_id || generateCartId(),
            cart_items_count: cartItems.length
        }, metadata));
    }
}

/**
 * Helper functie om een unieke cart ID te genereren
 * 
 * @returns {string} - Unieke cart ID
 */
function generateCartId() {
    return 'cart_' + Math.random().toString(36).substring(2, 15) + 
           Math.random().toString(36).substring(2, 15);
}

// Exporteer de klasse voor gebruik met modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = StripeCheckout;
} 