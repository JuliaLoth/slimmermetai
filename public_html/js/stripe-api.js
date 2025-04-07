/**
 * Stripe API helper functies voor Slimmer met AI
 * 
 * Dit bestand bevat functies om de Stripe API aan te roepen.
 * Gebruik altijd de proxy-scripts om problemen met de API toegang te voorkomen.
 */

/**
 * Haal de Stripe configuratie op
 * @returns {Promise} Een Promise die resolved naar de Stripe config
 */
async function getStripeConfig() {
    try {
        // Gebruik het proxy script in de hoofdmap
        const response = await fetch('/stripe-config.php');
        
        if (!response.ok) {
            throw new Error(`API fout: ${response.status} ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Geen JSON response:', text.substring(0, 500));
            throw new Error('Verwacht JSON maar kreeg ander content type');
        }
        
        return await response.json();
    } catch (error) {
        console.error('Fout bij ophalen Stripe configuratie:', error);
        // Gebruik de API proxy als fallback
        return getStripeConfigFallback();
    }
}

/**
 * Fallback methode voor het ophalen van de Stripe configuratie
 * @returns {Promise} Een Promise die resolved naar de Stripe config
 */
async function getStripeConfigFallback() {
    try {
        // Gebruik de API proxy als fallback
        const response = await fetch('/api-proxy.php?endpoint=stripe_config');
        
        if (!response.ok) {
            throw new Error(`API fallback fout: ${response.status} ${response.statusText}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Fallback fout bij ophalen Stripe configuratie:', error);
        // Laatste redmiddel: hardcoded waarde terugsturen
        return {
            publishableKey: 'pk_test_51R9P5k4PGPB9w5n1not7EQ9Kh15WBNrFC0B09dHvKNN3Slf1dF32rFvQniEwPpeeAQstMGnLQFTblXXwN8QAGovO00S1D67hoD',
            fallback: true,
            error: error.message
        };
    }
}

/**
 * Maak een Stripe checkout sessie aan
 * @param {Object} checkoutData De checkout data
 * @returns {Promise} Een Promise die resolved naar de checkout sessie
 */
async function createStripeCheckoutSession(checkoutData) {
    try {
        // Gebruik het proxy script in de hoofdmap
        const response = await fetch('/stripe-checkout-session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(checkoutData)
        });
        
        if (!response.ok) {
            const text = await response.text();
            try {
                const errorData = JSON.parse(text);
                throw new Error(errorData.message || `API fout: ${response.status}`);
            } catch (e) {
                throw new Error(`API fout: ${response.status}, Details: ${text.substring(0, 200)}`);
            }
        }
        
        return await response.json();
    } catch (error) {
        console.error('Fout bij aanmaken checkout sessie:', error);
        throw error;
    }
}

/**
 * Voorbeeld van checkout data object
 * @type {Object}
 */
const exampleCheckoutData = {
    line_items: [
        {
            price_data: {
                currency: 'eur',
                product_data: {
                    name: 'Productnaam',
                    description: 'Productbeschrijving'
                },
                unit_amount: 1000 // 10.00 EUR
            },
            quantity: 1
        }
    ],
    mode: 'payment',
    success_url: window.location.origin + '/betaling-succes.php',
    cancel_url: window.location.origin + '/winkelwagen.php'
}; 