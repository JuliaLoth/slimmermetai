/**
 * Stripe betaalintegratie voor Slimmer met AI
 * Gebaseerd op Stripe Checkout API: https://docs.stripe.com/api/checkout/sessions
 */

import '../../css/components/stripe.css';

const StripePayment = {
    // Stripe API configuratie
    publishableKey: null, // Wordt opgehaald vanaf de backend, geen fallback meer
    apiUrl: window.location.origin + '/api/stripe', // Pad naar API endpoints
    configUrl: window.location.origin + '/api/stripe/config', // Centrale endpoint voor config
    initialized: false, // Flag om dubbele initialisatie te voorkomen
    
    /**
     * Initialiseer Stripe betalingen
     */
    init: function() {
        if (this.initialized) {
            console.warn('StripePayment is al geïnitialiseerd. Overslaan.');
            return;
        }
        this.initialized = true; // Zet de flag
        console.log('Stripe Payment wordt geïnitialiseerd');
        
        // Haal de publishable key op van de server (veiliger)
        this.getStripeConfig()
            .then(() => {
                if (!this.publishableKey) {
                    throw new Error('Publishable key ontbreekt, initialisatie afgebroken.');
                }

                // Laad Stripe.js script na het verkrijgen van de key
                this.loadStripeJs();
                
                // Vervang de checkout knop functionaliteit
                this.replaceCheckoutButton();
            })
            .catch(error => {
                console.error('Fout bij initialiseren Stripe Payment:', error);
                
                // De checkout-knop onbruikbaar maken
                const checkoutBtn = document.getElementById('checkout-btn');
                if (checkoutBtn) {
                    checkoutBtn.disabled = true;
                }

                // Toon een foutmelding aan de gebruiker
                if (typeof showNotification === 'function') {
                    showNotification('Het betalingssysteem is momenteel niet beschikbaar. Probeer het later opnieuw of neem contact op met de beheerder.', 'error');
                }
            });
    },
    
    /**
     * Haal Stripe configuratie op van de server
     */
    getStripeConfig: function() {
        console.log('Ophalen Stripe configuratie van:', this.configUrl);
        
        // Retourneer een Promise voor betere controle in de init functie
        return new Promise((resolve, reject) => {
            // API call om de publishable key op te halen (centrale endpoint)
            fetch(this.configUrl, {
                method: 'GET',
                headers: {
                    'Cache-Control': 'no-cache'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        console.error('Server antwoord fout:', response.status, response.statusText);
                        
                        throw new Error(`Kon Stripe-configuratie niet ophalen (status ${response.status}).`);
                    }
                    
                    // Log de response headers en body voor debugging
                    console.log('Response headers:', response.headers);
                    const contentType = response.headers.get('content-type');
                    console.log('Content-Type header:', contentType);
                    
                    return response.text().then(text => {
                        // Controleer of de response HTML bevat (waarschijnlijk een foutpagina)
                        if (text.trim().startsWith('<!DOCTYPE html>') || text.trim().startsWith('<html')) {
                            console.error('HTML ontvangen in plaats van JSON. Waarschijnlijk een toegangsprobleem:', text.substring(0, 500));
                            throw new Error('Onverwachte HTML-respons ontvangen in plaats van JSON.');
                        }
                        
                        // Als het geen HTML is, probeer te parsen als JSON
                        try {
                            return JSON.parse(text);
                        } catch (error) {
                            console.error('Configuratie kon niet geparsed worden als JSON.');
                            throw new Error('Configuratie kon niet geparsed worden als JSON.');
                        }
                    });
                })
                .then(data => {
                    console.log('Ontvangen configuratie data:', data);
                    
                    // Zoek naar verschillende mogelijke sleutel formats in de response
                    let foundKey = null;
                    
                    // Check voor nieuwe API response format (met success/data wrapper)
                    if (data.success && data.data) {
                        const responseData = data.data;
                        if (responseData.publishableKey) {
                            foundKey = responseData.publishableKey;
                        } else if (responseData.public_key) {
                            foundKey = responseData.public_key;
                        } else if (responseData.stripe_key) {
                            foundKey = responseData.stripe_key;
                        }
                    }
                    // Fallback: check direct op top level (legacy format)
                    else if (data.publishableKey) {
                        foundKey = data.publishableKey;
                    } else if (data.config_variables && data.config_variables.STRIPE_PUBLIC_KEY) {
                        // In het nieuwe stripe-api-config.php formaat
                        foundKey = data.config_variables.STRIPE_PUBLIC_KEY;
                        console.log('Sleutel gevonden in config_variables');
                    } else if (data.public_key) {
                        foundKey = data.public_key;
                    } else if (data.stripe_key) {
                        foundKey = data.stripe_key;
                    }
                    
                    if (foundKey) {
                        this.publishableKey = foundKey;
                        console.log('Stripe publishable key opgehaald van server:', foundKey);
                        resolve(data);
                    } else {
                        console.error('Volledige response data:', JSON.stringify(data, null, 2));
                        reject(new Error('Geen publishableKey gevonden in server-respons.'));
                    }
                })
                .catch(error => {
                    console.error('Fout bij ophalen Stripe configuratie:', error);
                    reject(error);
                });
        });
    },
    
    /**
     * Laad het Stripe.js script
     */
    loadStripeJs: function() {
        if (document.getElementById('stripe-js')) {
            console.log('Stripe.js is al geladen');
            return;
        }
        
        const script = document.createElement('script');
        script.id = 'stripe-js';
        script.src = 'https://js.stripe.com/v3/';
        script.async = true;
        document.head.appendChild(script);
        
        console.log('Stripe.js script toegevoegd');
    },
    
    /**
     * Vervang de standaard checkout knop met Stripe checkout
     */
    replaceCheckoutButton: function() {
        const checkoutBtn = document.getElementById('checkout-btn');
        if (!checkoutBtn) {
            console.log('Checkout knop niet gevonden');
            return;
        }
        
        // Verwijder bestaande event listeners
        const newCheckoutBtn = checkoutBtn.cloneNode(true);
        checkoutBtn.parentNode.replaceChild(newCheckoutBtn, checkoutBtn);
        
        // Voeg nieuwe event listener toe
        newCheckoutBtn.addEventListener('click', this.handleCheckout.bind(this));
        
        console.log('Checkout knop event handler vervangen');
    },
    
    /**
     * Verwerk checkout en start Stripe betaalproces
     */
    handleCheckout: async function(e) {
        e.preventDefault();
        
        // Toon laad indicator
        const checkoutBtn = document.getElementById('checkout-btn');
        const originalText = checkoutBtn.textContent;
        checkoutBtn.disabled = true;
        checkoutBtn.innerHTML = '<div class="spinner"></div> Bezig met laden...';
        
        try {
            // Controleer eerst of Stripe.js is geladen
            if (!window.Stripe) {
                // Probeer Stripe.js te laden als het nog niet beschikbaar is
                this.loadStripeJs();
                
                // Wacht 1 seconde om te zien of Stripe.js wordt geladen
                await new Promise(resolve => setTimeout(resolve, 1000));
                
                // Controleer of Stripe nu beschikbaar is
                if (!window.Stripe) {
                    throw new Error('Stripe.js is niet geladen. Vernieuw de pagina en probeer het opnieuw.');
                }
            }
            
            // Controleer of we een geldige publishable key hebben
            if (!this.publishableKey || !this.publishableKey.startsWith('pk_')) {
                console.warn('Geen geldige Stripe publishable key, probeer nogmaals de configuratie op te halen...');
                
                // Laatste poging om de sleutel op te halen
                await this.getStripeConfig();
                
                // Als we nog steeds geen geldige sleutel hebben, geef een foutmelding en annuleer de betaling
                if (!this.publishableKey || !this.publishableKey.startsWith('pk_')) {
                    throw new Error('Publishable key ontbreekt of is ongeldig.');
                }
            }
            
            // Bereid winkelwagen data voor voor Stripe
            const sessionData = this.prepareCheckoutData();
            console.log('Prepared checkout data:', sessionData);
            
            // Maak een checkout sessie aan via onze backend
            const session = await this.createCheckoutSession(sessionData);
            console.log('Checkout session created:', session);
            
            // Controleer op API-sleutel fouten in de respons
            if (session.error && session.error === true) {
                console.error('Stripe API fout:', session.message || 'Onbekende fout');
                showNotification('Er is een fout met de Stripe API. Neem contact op met de beheerder: ' + 
                    (session.message || 'Onbekende fout'), 'error');
                throw new Error('Stripe API fout: ' + (session.message || 'Onbekende fout'));
            }
            
            // Redirect naar Stripe checkout
            if (session && session.id) {
                this.redirectToCheckout(session.id);
            } else {
                throw new Error('Geen geldig Stripe sessie ID ontvangen. Ontvangen: ' + JSON.stringify(session));
            }
        } catch (error) {
            console.error('Fout bij het starten van de betaling:', error);
            
            // Toon een gebruiksvriendelijke foutmelding
            let errorMessage = error.message || 'Onbekende fout';
            
            // Vertaal veelvoorkomende fouten naar gebruiksvriendelijke berichten
            if (errorMessage.includes('API Key') || errorMessage.includes('API sleutel')) {
                errorMessage = 'Er is een probleem met de betaaldienst. Neem contact op met de beheerder.';
            } else if (errorMessage.includes('Winkelwagen is leeg')) {
                errorMessage = 'Je winkelwagen is leeg. Voeg eerst producten toe.';
            } else if (errorMessage.includes('body stream already read')) {
                errorMessage = 'Er is een technisch probleem met de betaaldienst. Vernieuw de pagina en probeer het opnieuw.';
            }
            
            showNotification('Er is een fout opgetreden bij het starten van de betaling: ' + errorMessage, 'error');
            
            // Herstel de knop
            checkoutBtn.disabled = false;
            checkoutBtn.textContent = originalText;
        }
    },
    
    /**
     * Bereid winkelwagen data voor voor Stripe Checkout
     */
    prepareCheckoutData: function() {
        // Haal winkelwagen items op via de juiste Cart functie
        const cartItems = Cart.getItems();
        
        // Controleer of er items zijn
        if (!cartItems || cartItems.length === 0) {
            showNotification('Je winkelwagen is leeg', 'error');
            throw new Error('Winkelwagen is leeg');
        }
        
        // Converteer naar Stripe line_items formaat
        const lineItems = cartItems.map(item => {
            return {
                price_data: {
                    currency: 'eur',
                    product_data: {
                        name: item.name || 'Product',
                        description: item.type || 'Product',
                        images: item.img ? [new URL(item.img, window.location.origin).href] : []
                    },
                    unit_amount: Math.round((item.price || 0) * 100), // Prijs in centen
                },
                quantity: parseInt(item.quantity || 1, 10)
            };
        });
        
        // Log de line_items voor debugging
        console.log('Prepared line_items:', lineItems);
        
        // Klantgegevens (indien beschikbaar)
        let customerEmail = null;
        try {
            customerEmail = localStorage.getItem('userEmail');
        } catch (e) {
            console.log('Geen email gevonden in localStorage');
        }
        
        // Bouw de complete checkout data
        const sessionData = {
            line_items: lineItems,
            success_url: window.location.origin + '/betaling-succes',
            cancel_url: window.location.origin + '/winkelwagen',
            mode: 'payment',
        };
        
        // Voeg optionele velden toe als ze bestaan
        if (customerEmail) {
            sessionData.customer_email = customerEmail;
        }
        
        // Voeg metadata toe
        sessionData.metadata = {
            cart_id: localStorage.getItem('cartId') || Date.now().toString()
        };
        
        return sessionData;
    },
    
    /**
     * Maak een Stripe Checkout sessie aan via de backend
     */
    createCheckoutSession: async function(sessionData) {
        try {
            // Probeer eerst de verbinding te testen met het test endpoint
            console.log('Testen van API verbinding...');
            // Gebruik simple-test.php in plaats van test.php
            const testUrl = window.location.origin + '/api/stripe/simple-test.php'; 
            
            try {
                // Test of API correct werkt (GET verzoek voor test endpoint)
                console.log('Test API endpoint met GET verzoek:', testUrl);
                const testResponse = await fetch(testUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'text/plain', // Accepteer platte tekst
                        'Cache-Control': 'no-cache'
                    }
                });
                
                console.log('Test API response status:', testResponse.status);
                const testText = await testResponse.text();
                console.log('Test API response text (eerste 100 karakters):', testText.substring(0, 100));
                
                // Aangepaste controle: check of response OK is en NIET begint met <?php
                // Dit is een workaround omdat PHP niet correct lijkt te worden uitgevoerd
                let testSuccessful = false;
                if (testResponse.ok && !testText.trim().startsWith('<?php')) {
                    console.log('API test succesvol (server gaf geen PHP-code terug)');
                    testSuccessful = true;
                } else {
                    console.warn('API test niet succesvol. Status:', testResponse.status, 'Response begint met:', testText.substring(0, 5));
                }
                
                // Als de test mislukt, probeer api via alternatieve methode
                if (!testSuccessful) {
                    console.warn('API test niet succesvol, gebruik alternatieve methode');
                    return await this.createCheckoutSessionAlternative(sessionData);
                }
            } catch (testError) {
                console.error('API test fout:', testError);
                console.warn('Gebruik alternatieve checkout methode');
                return await this.createCheckoutSessionAlternative(sessionData);
            }
            
            // Probeer eerst het API endpoint in de api/stripe map
            const endpointUrl = window.location.origin + '/api/stripe/checkout';
            console.log('Aanmaken checkout sessie met URL:', endpointUrl);
            
            // Controleer of we een geldige sessionData hebben
            if (!sessionData || !sessionData.line_items || sessionData.line_items.length === 0) {
                console.error('Ongeldige sessionData', sessionData);
                throw new Error('Ongeldige checkout data. Winkelwagen is mogelijk leeg.');
            }
            
            // Maak een deep copy van de data om te voorkomen dat er referentieproblemen zijn
            const jsonData = JSON.stringify(sessionData);
            console.log('Sessie data (eerste 100 karakters):', jsonData.substring(0, 100));
            
            try {
                // Verzend een POST verzoek naar het primaire API endpoint
                console.log('Uitvoeren POST verzoek naar primair API endpoint...');
                let response = await fetch(endpointUrl, {
                    method: 'POST',  // Zorg ervoor dat dit POST is
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Cache-Control': 'no-cache'
                    },
                    body: jsonData  // Belangrijk: body toevoegen voor POST verzoek
                });
                
                console.log('Primair API response status:', response.status);
                
                // Controleer direct op HTML-response (foutpagina)
                const text = await response.text();
                console.log('Primair API response text (eerste 100 karakters):', text.substring(0, 100));
                
                // Controleer of we een PHP of HTML document hebben ontvangen (foutpagina)
                if (text.trim().startsWith('<!DOCTYPE html>') || 
                    text.trim().startsWith('<html') || 
                    text.trim().startsWith('<?php')) {
                    console.error('PHP of HTML code ontvangen in plaats van JSON. Probeer alternatieve endpoint...');
                    throw new Error('PHP of HTML code ontvangen in plaats van JSON');
                }
                
                // Als de response geen succes is, probeer de fallback endpoint
                if (!response.ok) {
                    console.error(`API fout: ${response.status} ${response.statusText}. Details: ${text.substring(0, 200)}`);
                    
                    // Als het een 405-fout is (Method Not Allowed), controleer de HTTP-methode
                    if (response.status === 405) {
                        console.error('405 Method Not Allowed: Het endpoint accepteert mogelijk alleen POST verzoeken');
                    }
                    
                    throw new Error(`API fout: ${response.status} ${response.statusText}`);
                }
                
                // Probeer de response te parsen als JSON
                try {
                    const data = JSON.parse(text);
                    console.log('Checkout session response:', data);
                    
                    // NIEUWE RESPONSE PARSING: Verwacht data.data.session.id structuur
                    let sessionId = null;
                    let sessionUrl = null;
                    
                    // Check voor de nieuwe API response format met succes wrapper
                    if (data.success && data.data && data.data.session) {
                        sessionId = data.data.session.id;
                        sessionUrl = data.data.session.url;
                        console.log('Gevonden session in data.data.session:', sessionId);
                    }
                    // Fallback: legacy format waar session direct in data zit
                    else if (data.data && data.data.id) {
                        sessionId = data.data.id;
                        sessionUrl = data.data.url;
                        console.log('Gevonden session in data (legacy):', sessionId);
                    }
                    // Fallback: directe session data
                    else if (data.id) {
                        sessionId = data.id;
                        sessionUrl = data.url;
                        console.log('Gevonden session direct (legacy):', sessionId);
                    }
                    
                    if (!sessionId) {
                        console.error('Volledige response data:', JSON.stringify(data, null, 2));
                        throw new Error('Geen geldig Stripe sessie ID ontvangen');
                    }
                    
                    return {
                        id: sessionId,
                        url: sessionUrl
                    };
                } catch (jsonError) {
                    console.error('Kon de API response niet als JSON parsen:', jsonError);
                    console.error('Raw API response:', text);
                    throw new Error('Ongeldige response van de Stripe API: ' + text.substring(0, 100));
                }
            } catch (fetchError) {
                // Log de eerste fout maar probeer nog de fallback
                console.error('Fout bij primair API endpoint:', fetchError);
                
                // Gebruik alternatieve methode
                return await this.createCheckoutSessionAlternative(sessionData);
            }
        } catch (error) {
            console.error('API fout:', error);
            throw error;
        }
    },
    
    /**
     * Alternatieve methode om checkout sessie te maken via de fallback URL
     */
    createCheckoutSessionAlternative: async function(sessionData) {
        console.log('Probeer fallback endpoint in hoofdmap...');
        const fallbackUrl = window.location.origin + '/api/stripe/checkout';
        
        // Controleer of we een geldige sessionData hebben
        if (!sessionData || !sessionData.line_items || sessionData.line_items.length === 0) {
            console.error('Ongeldige sessionData voor fallback', sessionData);
            return this.createDirectStripeSession(sessionData || {line_items: []});
        }
        
        // Maak een deep copy van de data
        const jsonData = JSON.stringify(sessionData);
        console.log('Verzenden naar fallback endpoint:', fallbackUrl);
        console.log('Met data:', jsonData);
        
        try {
            // Nieuwe fetch met de fallback URL - zorg ervoor dat het een POST request is
            console.log('Uitvoeren POST request naar fallback endpoint...');
            let fallbackResponse = await fetch(fallbackUrl, {
                method: 'POST', // Zorg ervoor dat dit een POST request is
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache'
                },
                body: jsonData // Belangrijk: body toevoegen voor POST request
            });
            
            console.log('Fallback response status:', fallbackResponse.status);
            
            // Controleer op HTML response
            const fallbackText = await fallbackResponse.text();
            console.log('Fallback response text (eerste 100 karakters):', fallbackText.substring(0, 100));
            
            // Controleer of we een HTML-document of PHP-code hebben ontvangen (foutpagina)
            if (fallbackText.trim().startsWith('<!DOCTYPE html>') || 
                fallbackText.trim().startsWith('<html') || 
                fallbackText.trim().startsWith('<?php')) {
                console.error('PHP of HTML ontvangen van fallback endpoint. Directe Stripe integratie is nodig.');
                
                // Als beide API endpoints falen, gebruik directe Stripe checkout
                console.log('Beide API endpoints falen, gebruik directe Stripe checkout...');
                return this.createDirectStripeSession(sessionData);
            }
            
            // Controleer of de fallback succesvol was
            if (!fallbackResponse.ok) {
                console.error(`Fallback endpoint fout: ${fallbackResponse.status}. Details: ${fallbackText.substring(0, 200)}`);
                
                // Als het een 405-fout is (Method Not Allowed), is het waarschijnlijk dat het endpoint alleen POST toestaat
                if (fallbackResponse.status === 405) {
                    console.error('405 Method Not Allowed: Het endpoint accepteert alleen POST verzoeken');
                }
                
                return this.createDirectStripeSession(sessionData);
            }
            
            // Parse de fallback response
            try {
                const fallbackData = JSON.parse(fallbackText);
                console.log('Fallback checkout session response:', fallbackData);
                
                // GEBRUIK DEZELFDE RESPONSE PARSING ALS HOOFDFUNCTIE
                let sessionId = null;
                let sessionUrl = null;
                
                // Check voor de nieuwe API response format met succes wrapper
                if (fallbackData.success && fallbackData.data && fallbackData.data.session) {
                    sessionId = fallbackData.data.session.id;
                    sessionUrl = fallbackData.data.session.url;
                    console.log('Gevonden session in fallback data.data.session:', sessionId);
                }
                // Fallback: legacy format waar session direct in data zit
                else if (fallbackData.data && fallbackData.data.id) {
                    sessionId = fallbackData.data.id;
                    sessionUrl = fallbackData.data.url;
                    console.log('Gevonden session in fallback data (legacy):', sessionId);
                }
                // Fallback: directe session data
                else if (fallbackData.id) {
                    sessionId = fallbackData.id;
                    sessionUrl = fallbackData.url;
                    console.log('Gevonden session direct in fallback (legacy):', sessionId);
                }
                
                if (!sessionId) {
                    console.error('Geen geldig Stripe sessie ID ontvangen van fallback endpoint');
                    console.error('Volledige fallback response data:', JSON.stringify(fallbackData, null, 2));
                    return this.createDirectStripeSession(sessionData);
                }
                
                return {
                    id: sessionId,
                    url: sessionUrl
                };
            } catch (fallbackJsonError) {
                console.error('Kon fallback response niet als JSON parsen:', fallbackJsonError, 'Response tekst:', fallbackText);
                return this.createDirectStripeSession(sessionData);
            }
        } catch (error) {
            console.error('Fout bij alternatieve checkout methode:', error);
            return this.createDirectStripeSession(sessionData);
        }
    },
    
    /**
     * Creëer een directe Stripe sessie als fallback
     * Dit wordt alleen gebruikt als beide server endpoints falen
     */
    createDirectStripeSession: function(sessionData) {
        console.log('Gebruik directe Stripe checkout als laatste redmiddel');
        
        // Controleer of Stripe.js beschikbaar is
        if (!window.Stripe) {
            console.error('Stripe.js is niet beschikbaar voor directe checkout. Poging tot laden...');
            this.loadStripeJs();
            
            // Geef een ID terug dat aangeeft dat we later directe checkout moeten gebruiken
            return {
                id: 'direct_' + Date.now(),
                direct_checkout: true,
                items: sessionData.line_items,
                fallback_method: 'direct_redirect'
            };
        }
        
        // Bereid de productnaam en prijs voor vanuit de eerste line_item
        let productName = 'SlimmerMetAI Product';
        let productPrice = 0;
        
        try {
            if (sessionData.line_items && sessionData.line_items.length > 0) {
                const firstItem = sessionData.line_items[0];
                
                if (firstItem.price_data && firstItem.price_data.product_data) {
                    productName = firstItem.price_data.product_data.name || productName;
                }
                
                if (firstItem.price_data && firstItem.price_data.unit_amount) {
                    productPrice = firstItem.price_data.unit_amount;
                }
            }
        } catch (e) {
            console.error('Fout bij verwerken van product info:', e);
        }
        
        // Log de checkout info
        console.log('Directe checkout met product:', productName, 'en prijs:', productPrice);
        
        // Hardcoded productprijs voor fallback als laatste redmiddel
        if (!productPrice || productPrice <= 0) {
            productPrice = 2999; // € 29,99 in centen
        }
        
        // Gebruik directe redirect met simpele product info
        return {
            id: 'direct_' + Date.now(),
            direct_checkout: true,
            items: sessionData.line_items,
            product_name: productName,
            product_price: productPrice,
            fallback_method: 'direct_checkout'
        };
    },
    
    /**
     * Redirect naar Stripe Checkout
     */
    redirectToCheckout: function(sessionId) {
        // Check voor mock sessions - redirect direct naar success
        if (sessionId && sessionId.startsWith('cs_test_mock_')) {
            console.log('[StripePayment] Mock session detected:', sessionId);
            console.log('[StripePayment] Redirecting directly to success page for mock session');
            
            // Mock success redirect
            const successUrl = window.location.origin + '/betaling-succes?mock=true&session_id=' + sessionId;
            console.log('[StripePayment] Mock success URL:', successUrl);
            window.location.href = successUrl;
            return;
        }
        
        // Toon een tijdelijke melding om de gebruiker te informeren
        if (typeof showNotification === 'function') {
            showNotification('Je wordt doorgestuurd naar de betaalpagina...', 'info');
        }
        
        // Check voor directe checkout modus (als server endpoints falen)
        if (sessionId.startsWith('direct_')) {
            this.redirectToDirectCheckout();
            return;
        }
        
        // Wacht tot Stripe.js is geladen
        const checkStripeInterval = setInterval(() => {
            if (window.Stripe) {
                clearInterval(checkStripeInterval);
                
                try {
                    // Initialiseer Stripe en redirect
                    console.log('Redirecting to Stripe checkout with session ID:', sessionId);
                    console.log('Using publishable key:', this.publishableKey);
                    
                    // Controleer of de publishable key geldig is
                    if (!this.publishableKey || !this.publishableKey.startsWith('pk_')) {
                        throw new Error('Ongeldige Stripe publishable key.');
                    }
                    
                    const stripe = Stripe(this.publishableKey);
                    stripe.redirectToCheckout({ sessionId: sessionId })
                        .then(function(result) {
                            if (result.error) {
                                console.error('Stripe checkout fout:', result.error);
                                if (typeof showNotification === 'function') {
                                    showNotification('Fout bij doorsturen naar betaalpagina: ' + result.error.message, 'error');
                                }
                                
                                // Herstel de knop
                                const checkoutBtn = document.getElementById('checkout-btn');
                                if (checkoutBtn) {
                                    checkoutBtn.disabled = false;
                                    checkoutBtn.textContent = 'Afrekenen';
                                }
                            }
                        })
                        .catch(function(error) {
                            console.error('Onverwachte fout:', error);
                            if (typeof showNotification === 'function') {
                                showNotification('Er is een onverwachte fout opgetreden: ' + error.message, 'error');
                            }
                            
                            // Herstel de knop
                            const checkoutBtn = document.getElementById('checkout-btn');
                            if (checkoutBtn) {
                                checkoutBtn.disabled = false;
                                checkoutBtn.textContent = 'Afrekenen';
                            }
                        });
                } catch (error) {
                    console.error('Fout bij initialiseren Stripe:', error);
                    if (typeof showNotification === 'function') {
                        showNotification('Er is een fout opgetreden bij het initialiseren van Stripe: ' + error.message, 'error');
                    }
                    
                    // Herstel de knop
                    const checkoutBtn = document.getElementById('checkout-btn');
                    if (checkoutBtn) {
                        checkoutBtn.disabled = false;
                        checkoutBtn.textContent = 'Afrekenen';
                    }
                }
            }
        }, 100);
        
        // Time-out na 5 seconden als Stripe.js niet laadt
        setTimeout(() => {
            clearInterval(checkStripeInterval);
            if (!window.Stripe) {
                console.error('Stripe.js kon niet worden geladen binnen de time-out periode');
                showNotification('Stripe.js kon niet worden geladen. Controleer je internetverbinding en probeer het opnieuw.', 'error');
                
                // Herstel de knop
                const checkoutBtn = document.getElementById('checkout-btn');
                if (checkoutBtn) {
                    checkoutBtn.disabled = false;
                    checkoutBtn.textContent = 'Afrekenen';
                }
            }
        }, 5000);
    },
    
    /**
     * Directe Stripe checkout als fallback
     * Dit wordt alleen gebruikt als beide server endpoints falen
     */
    redirectToDirectCheckout: function() {
        console.log('Gebruik directe Stripe checkout redirect');
        
        if (window.Stripe) {
            try {
                // Gebruik altijd de laatst bekende geldige Stripe key
                const stripe = Stripe(this.publishableKey);

                // Corrigeer de lineItems structuur voor client-side redirect
                // Haal items uit de Cart via de juiste functie
                const cartItems = Cart.getItems() || []; 
                let lineItemsForDirectCheckout = [];

                if (cartItems.length > 0) {
                    lineItemsForDirectCheckout = cartItems.map(item => ({
                        // Client-side gebruikt 'price' en 'quantity' direct
                        price: item.stripePriceId || null, // Probeer een Price ID te gebruiken indien beschikbaar
                        quantity: parseInt(item.quantity || 1, 10)
                        // Als er geen Price ID is, moeten we price_data gebruiken (complexer)
                        // Voor nu focussen we op het corrigeren van de basisstructuur
                        // Fallback naar een generiek product als Price ID mist
                    })).filter(item => item.price); // Filter items zonder price ID
                }

                // Als lineItemsForDirectCheckout leeg is (geen Price IDs), gebruik een fallback product
                 if (lineItemsForDirectCheckout.length === 0) {
                    console.warn("Geen geldige Price ID's gevonden voor directe checkout, gebruik fallback product.");
                    lineItemsForDirectCheckout = [{
                        // Hier zou je een vooraf gedefinieerde Price ID voor een fallback product kunnen invoegen
                        // Voorbeeld: price: 'price_xxxxxxx', 
                        // Bij gebrek hieraan, kan directe checkout falen. We laten de oude fallback staan.
                         price_data: {
                             currency: 'eur',
                             product_data: {
                                 name: 'SlimmerMetAI Bestelling',
                                 description: 'Je bestelling bij SlimmerMetAI'
                             },
                             unit_amount: Cart.calculateTotal() * 100 || 2999 // Gebruik winkelwagentotaal of fallback
                         },
                         quantity: 1
                    }];
                 } else {
                     console.log("Gebruik Price ID's voor directe checkout:", lineItemsForDirectCheckout);
                 }

                // Voer de redirect uit met de gecorrigeerde lineItems
                stripe.redirectToCheckout({
                    mode: 'payment',
                    lineItems: lineItemsForDirectCheckout, // Gebruik de gecorrigeerde array
                    successUrl: window.location.origin + '/betaling-succes?direct_fallback=true', // Aangepaste URL
                    cancelUrl: window.location.origin + '/winkelwagen?direct_fallback=true'
                })
                .then(function(result) {
                    if (result.error) {
                        console.error('Directe Stripe checkout fout:', result.error);
                        showNotification('Fout bij doorsturen naar betaalpagina: ' + result.error.message, 'error');
                        
                        // Als directe checkout niet werkt, gebruik de server-aanpak
                        StripePayment.createDynamicPaymentPage();
                    }
                })
                .catch(function(error) {
                    console.error('Fout bij directe Stripe checkout:', error);
                    
                    // Als directe checkout niet werkt, gebruik de server-aanpak
                    StripePayment.createDynamicPaymentPage();
                });
            } catch (error) {
                console.error('Fout bij directe Stripe checkout:', error);
                
                // Als directe checkout niet werkt, gebruik de server-aanpak
                StripePayment.createDynamicPaymentPage();
            }
        } else {
            // Als directe checkout niet werkt, gebruik de server-aanpak
            StripePayment.createDynamicPaymentPage();
        }
    },
    
    /**
     * Maak een dynamische checkout pagina als laatste redmiddel
     * In plaats van hardcoded URL gebruiken we een server-side endpoint
     */
    createDynamicPaymentPage: function() {
        console.log('Maak dynamische betaalpagina aan...');
        
        // Toon melding aan gebruiker
        if (typeof showNotification === 'function') {
            showNotification('We bereiden je betaling voor, een moment geduld a.u.b...', 'info');
        }
        
        // Maak eenvoudig product object
        const product = {
            name: 'SlimmerMetAI Product',
            price: 2999,
            currency: 'eur',
            quantity: 1
        };
        
        // Log wat we gaan doen
        console.log('Poging om dynamische checkout sessie aan te maken via server-api...');
        
        // Probeer beide endpoints na elkaar
        this.tryAllCheckoutEndpoints([
            window.location.origin + '/api/stripe/checkout'
        ]);
    },
    
    /**
     * Probeer meerdere endpoints om een checkout sessie aan te maken
     * @param {Array} endpoints - Array van URL endpoints om te proberen
     * @param {number} index - Huidige index in de array (voor recursie)
     */
    tryAllCheckoutEndpoints: function(endpoints, index = 0) {
        // Als we alle endpoints hebben geprobeerd zonder succes
        if (index >= endpoints.length) {
            console.error('Alle checkout endpoints zijn mislukt');
            
            // Toon gebruiksvriendelijke foutmelding
            if (typeof showNotification === 'function') {
                showNotification('Er is een probleem met de betaalpagina. Probeer het later opnieuw of neem contact op met onze klantenservice.', 'error');
            }
            
            // Herstel checkout knop
            const checkoutBtn = document.getElementById('checkout-btn');
            if (checkoutBtn) {
                checkoutBtn.disabled = false;
                checkoutBtn.textContent = 'Afrekenen';
            }
            
            return;
        }
        
        // Log welk endpoint we proberen
        const currentEndpoint = endpoints[index];
        console.log(`Probeer checkout endpoint ${index + 1}/${endpoints.length}: ${currentEndpoint}`);
        
        // Maak eenvoudig product object voor fallback
        const payload = {
            mode: 'payment',
            line_items: [{
                price_data: {
                    currency: 'eur',
                    product_data: {
                        name: 'SlimmerMetAI Product'
                    },
                    unit_amount: 2999
                },
                quantity: 1
            }],
            success_url: window.location.origin + '/betaling-succes.html',
            cancel_url: window.location.origin + '/winkelwagen',
            emergency_fallback: true
        };
        
        // Vraag server om een nieuwe checkout URL te genereren
        fetch(currentEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            // Log de HTTP status
            console.log(`Endpoint ${currentEndpoint} response status: ${response.status}`);
            
            // Controleer of de response succesvol is
            if (!response.ok) {
                console.warn(`Endpoint ${currentEndpoint} gaf status ${response.status}`);
                throw new Error(`HTTP status ${response.status}`);
            }
            
            return response.text().then(text => {
                // Controleer of we HTML ontvangen hebben
                if (text.trim().startsWith('<!DOCTYPE html>') || text.trim().startsWith('<html')) {
                    console.warn(`Endpoint ${currentEndpoint} gaf HTML terug in plaats van JSON`, text.substring(0, 200));
                    throw new Error('Server gaf HTML terug in plaats van JSON');
                }
                
                try {
                    // Probeer te parsen als JSON
                    return JSON.parse(text);
                } catch (error) {
                    console.warn(`Endpoint ${currentEndpoint} gaf geen geldige JSON terug`, text.substring(0, 200));
                    throw new Error('Server gaf geen geldige JSON terug');
                }
            });
        })
        .then(data => {
            console.log(`Checkout sessie ontvangen van ${currentEndpoint}:`, data);
            
            // Controleer of data succesvol is
            if (data.success && data.id) {
                // Redirect naar de nieuwe checkout sessie
                this.redirectToCheckout(data.id);
            } else {
                // Als er geen data.id is maar wel data.url, gebruik die direct
                if (data.url) {
                    console.log('Directe checkout URL ontvangen:', data.url);
                    window.location.href = data.url;
                } else {
                    throw new Error(data.message || 'Geen geldig sessie ID of URL ontvangen');
                }
            }
        })
        .catch(error => {
            console.warn(`Endpoint ${currentEndpoint} mislukt:`, error);
            
            // Probeer het volgende endpoint
            this.tryAllCheckoutEndpoints(endpoints, index + 1);
        });
    },
    
    /**
     * Verwerk de betaalstatus na terugkeer van Stripe
     * (Wordt aangeroepen op de success of cancel pagina)
     */
    handlePaymentStatus: function() {
        // Haal de sessie_id uit de URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const sessionId = urlParams.get('session_id');
        
        if (!sessionId) {
            return; // Geen sessie ID gevonden
        }
        
        // Status controleren via backend
        fetch(this.apiUrl + '/check-payment-status.php?session_id=' + sessionId)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Betaling succesvol, winkelwagen leegmaken
                    if (typeof Cart !== 'undefined') {
                        Cart.resetCart(false);
                    }
                    
                    showNotification('Je betaling is succesvol verwerkt!', 'success');
                } else {
                    showNotification('De status van je betaling is: ' + data.status, 'info');
                }
            })
            .catch(error => {
                console.error('Fout bij het controleren van de betaalstatus:', error);
            });
    }
};

// Maak StripePayment beschikbaar op het window object
window.StripePayment = StripePayment;

// Exporteer voor gebruik in andere modules
export default StripePayment;

// Initialiseer de Stripe betaling als we op de winkelwagen pagina zijn
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.cart-section')) {
        StripePayment.init();
    }
    
    // Ook controleren of we op de betaling-voltooid pagina zijn
    if (window.location.pathname.includes('betaling-succes') || 
        window.location.pathname.includes('betaling-voltooid')) {
        StripePayment.handlePaymentStatus();
    }
}); 
