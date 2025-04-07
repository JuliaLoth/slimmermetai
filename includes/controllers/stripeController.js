/**
 * Stripe controller
 * Bevat functies voor het verwerken van Stripe betalingen
 */

require('dotenv').config();
const stripe = require('stripe')(process.env.STRIPE_SECRET_KEY);
const db = require('../config/db');

/**
 * Maak een nieuwe Stripe Checkout sessie aan
 * @param {Object} req - Express request object
 * @param {Object} res - Express response object
 */
exports.createCheckoutSession = async (req, res) => {
    try {
        // Valideer verplichte velden
        const { line_items, success_url, cancel_url } = req.body;
        
        if (!line_items || !line_items.length) {
            return res.status(400).json({ 
                success: false,
                message: 'Winkelwagen is leeg. Voeg producten toe om af te rekenen.' 
            });
        }
        
        if (!success_url || !cancel_url) {
            return res.status(400).json({
                success: false,
                message: 'Ongeldige configuratie: success_url en cancel_url zijn verplicht.'
            });
        }

        // Maak Stripe Checkout sessie met metadata
        const session = await stripe.checkout.sessions.create({
            payment_method_types: ['card', 'ideal'], // Voeg iDEAL toe voor Nederlandse klanten
            line_items,
            mode: req.body.mode || 'payment',
            success_url: `${success_url}?session_id={CHECKOUT_SESSION_ID}`,
            cancel_url,
            customer_email: req.body.customer_email || (req.user ? req.user.email : null),
            client_reference_id: req.body.client_reference_id || `user_${req.user ? req.user.id : 'guest'}_${Date.now()}`,
            metadata: {
                ...req.body.metadata || {},
                user_id: req.user ? req.user.id : 'guest'
            },
            billing_address_collection: 'required',
            locale: 'nl', // Nederlandse taal instellen
            payment_intent_data: {
                metadata: {
                    // Voeg nuttige metadata toe aan de payment intent
                    client_reference_id: req.body.client_reference_id || `user_${req.user ? req.user.id : 'guest'}_${Date.now()}`,
                    ...(req.body.metadata || {})
                }
            }
        });
        
        // Sla de sessie op in onze database voor latere referentie
        await saveSessionToDatabase(session, req.user ? req.user.id : null);
        
        // Stuur sessie ID terug naar client
        return res.status(200).json({ 
            success: true, 
            id: session.id,
            url: session.url // URL voor redirect
        });
    } catch (error) {
        console.error('Stripe checkout sessie error:', error);
        return res.status(500).json({
            success: false,
            message: 'Er is een fout opgetreden bij het aanmaken van de checkout sessie.'
        });
    }
};

/**
 * Controleer de status van een betaling
 * @param {Object} req - Express request object
 * @param {Object} res - Express response object
 */
exports.checkPaymentStatus = async (req, res) => {
    try {
        const { session_id } = req.query;
        
        if (!session_id) {
            return res.status(400).json({
                success: false,
                message: 'Geen session_id opgegeven'
            });
        }
        
        // Haal de sessie op van Stripe
        const session = await stripe.checkout.sessions.retrieve(session_id);
        
        // Controleer betaalstatus
        let status = 'unknown';
        if (session.payment_status === 'paid') {
            status = 'success';
        } else if (session.payment_status === 'unpaid') {
            status = 'pending';
        } else if (session.status === 'expired') {
            status = 'expired';
        } else if (session.status === 'open') {
            status = 'open';
        }
        
        // Update betaalstatus in onze database
        await updatePaymentStatusInDatabase(session_id, status);
        
        // Stuur status terug naar client
        return res.status(200).json({
            success: true,
            status,
            payment_status: session.payment_status,
            session_status: session.status
        });
    } catch (error) {
        console.error('Fout bij controleren betaalstatus:', error);
        return res.status(500).json({
            success: false,
            message: 'Er is een fout opgetreden bij het controleren van de betaalstatus.'
        });
    }
};

/**
 * Verwerk Stripe webhook events (bijvoorbeeld betaalstatuswijzigingen)
 * @param {Object} req - Express request object
 * @param {Object} res - Express response object
 */
exports.handleWebhook = async (req, res) => {
    const sig = req.headers['stripe-signature'];
    let event;
    
    try {
        // Verifieer de webhook handtekening
        event = stripe.webhooks.constructEvent(
            req.body,
            sig,
            process.env.STRIPE_WEBHOOK_SECRET
        );
    } catch (err) {
        console.error('Webhook handtekening verificatie mislukt:', err.message);
        return res.status(400).send(`Webhook Error: ${err.message}`);
    }
    
    // Verwerk verschillende event types
    switch (event.type) {
        case 'checkout.session.completed':
            const session = event.data.object;
            // Verwerk een voltooide checkout
            await handleSuccessfulPayment(session);
            break;
            
        case 'payment_intent.succeeded':
            const paymentIntent = event.data.object;
            // Verwerk een succesvolle betaling
            await handleSuccessfulPaymentIntent(paymentIntent);
            break;
            
        case 'payment_intent.payment_failed':
            const failedPayment = event.data.object;
            // Verwerk een mislukte betaling
            await handleFailedPayment(failedPayment);
            break;
            
        default:
            // Onbekend event type
            console.log(`Onbekend event type: ${event.type}`);
    }
    
    // Stuur een 200 response om te bevestigen dat we het event hebben ontvangen
    res.status(200).json({ received: true });
};

/**
 * Haal gegevens op van een specifieke payment intent
 * @param {Object} req - Express request object
 * @param {Object} res - Express response object
 */
exports.getPaymentIntent = async (req, res) => {
    try {
        const paymentIntentId = req.params.id;
        
        // Haal de payment intent op van Stripe
        const paymentIntent = await stripe.paymentIntents.retrieve(paymentIntentId);
        
        // Controleer of deze betaling bij de ingelogde gebruiker hoort
        if (paymentIntent.metadata.user_id !== req.user.id && req.user.role !== 'admin') {
            return res.status(403).json({
                success: false,
                message: 'Je hebt geen toegang tot deze betaling'
            });
        }
        
        // Stuur de betaalgegevens terug
        return res.status(200).json({
            success: true,
            payment: {
                id: paymentIntent.id,
                amount: paymentIntent.amount / 100, // Convert from cents to euros
                status: paymentIntent.status,
                created: new Date(paymentIntent.created * 1000),
                payment_method: paymentIntent.payment_method_types,
                metadata: paymentIntent.metadata
            }
        });
    } catch (error) {
        console.error('Fout bij ophalen van payment intent:', error);
        return res.status(500).json({
            success: false,
            message: 'Er is een fout opgetreden bij het ophalen van de betaalgegevens'
        });
    }
};

/**
 * Haal betalingsgeschiedenis van een gebruiker op
 * @param {Object} req - Express request object
 * @param {Object} res - Express response object
 */
exports.getPaymentHistory = async (req, res) => {
    try {
        // Haal betalingen op uit onze database
        const payments = await getPaymentHistoryFromDatabase(req.user.id);
        
        return res.status(200).json({
            success: true,
            payments
        });
    } catch (error) {
        console.error('Fout bij ophalen van betalingsgeschiedenis:', error);
        return res.status(500).json({
            success: false,
            message: 'Er is een fout opgetreden bij het ophalen van je betalingsgeschiedenis'
        });
    }
};

/**
 * Helper functies
 */

/**
 * Sla een checkout sessie op in de database
 * @param {Object} session - Stripe checkout session object
 * @param {String|null} userId - ID van de gebruiker, of null voor gasten
 */
async function saveSessionToDatabase(session, userId) {
    try {
        const query = `
            INSERT INTO stripe_sessions 
            (session_id, user_id, amount_total, currency, payment_status, status, created_at, metadata)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
        `;
        
        await db.queryUsers(query, [
            session.id,
            userId || null,
            session.amount_total / 100, // Convert van centen naar euro's
            session.currency,
            session.payment_status,
            session.status,
            JSON.stringify(session.metadata || {})
        ]);
        
        console.log(`Checkout sessie ${session.id} opgeslagen in database`);
    } catch (error) {
        console.error('Fout bij opslaan van checkout sessie in database:', error);
    }
}

/**
 * Update de betaalstatus in de database
 * @param {String} sessionId - Stripe session ID
 * @param {String} status - Nieuwe status
 */
async function updatePaymentStatusInDatabase(sessionId, status) {
    try {
        const query = `
            UPDATE stripe_sessions
            SET payment_status = ?, updated_at = NOW()
            WHERE session_id = ?
        `;
        
        await db.queryUsers(query, [status, sessionId]);
        console.log(`Betaalstatus voor sessie ${sessionId} bijgewerkt naar ${status}`);
    } catch (error) {
        console.error('Fout bij bijwerken van betaalstatus in database:', error);
    }
}

/**
 * Verwerk een succesvolle betaling
 * @param {Object} session - Stripe checkout session object
 */
async function handleSuccessfulPayment(session) {
    try {
        // Update betaalstatus in database
        const query = `
            UPDATE stripe_sessions
            SET payment_status = 'paid', status = 'complete', updated_at = NOW()
            WHERE session_id = ?
        `;
        
        await db.queryUsers(query, [session.id]);
        
        // Voeg producten toe aan gebruikersaccount
        if (session.metadata && session.metadata.user_id && session.metadata.user_id !== 'guest') {
            await addPurchasedItemsToUserAccount(session);
        }
        
        console.log(`Succesvolle betaling verwerkt voor sessie ${session.id}`);
    } catch (error) {
        console.error('Fout bij verwerken van succesvolle betaling:', error);
    }
}

/**
 * Verwerk een succesvolle betaling via payment intent
 * @param {Object} paymentIntent - Stripe payment intent object
 */
async function handleSuccessfulPaymentIntent(paymentIntent) {
    try {
        console.log(`Succesvolle payment intent verwerkt: ${paymentIntent.id}`);
        // Implementatie afhankelijk van hoe je payment intents gebruikt
    } catch (error) {
        console.error('Fout bij verwerken van succesvolle payment intent:', error);
    }
}

/**
 * Verwerk een mislukte betaling
 * @param {Object} paymentIntent - Stripe payment intent object
 */
async function handleFailedPayment(paymentIntent) {
    try {
        // Loggen van mislukte betaling (voor monitoring)
        console.log(`Mislukte betaling: ${paymentIntent.id}, reden: ${paymentIntent.last_payment_error?.message || 'Onbekend'}`);
        
        // Hier zou je bijvoorbeeld een e-mail kunnen sturen naar de klant
    } catch (error) {
        console.error('Fout bij verwerken van mislukte betaling:', error);
    }
}

/**
 * Voeg gekochte items toe aan gebruikersaccount
 * @param {Object} session - Stripe checkout session object
 */
async function addPurchasedItemsToUserAccount(session) {
    // Deze functie is afhankelijk van je database-structuur
    // Hier zou je bijvoorbeeld gekochte tools/cursussen activeren
    console.log(`Items uit sessie ${session.id} toevoegen aan account van gebruiker ${session.metadata.user_id}`);
}

/**
 * Haal betalingsgeschiedenis op uit de database
 * @param {String} userId - Gebruikers-ID
 * @returns {Array} Array van betalingen
 */
async function getPaymentHistoryFromDatabase(userId) {
    try {
        const query = `
            SELECT session_id, amount_total, currency, payment_status, status, created_at, updated_at, metadata
            FROM stripe_sessions
            WHERE user_id = ?
            ORDER BY created_at DESC
        `;
        
        const payments = await db.queryUsers(query, [userId]);
        return payments;
    } catch (error) {
        console.error('Fout bij ophalen betalingsgeschiedenis uit database:', error);
        return [];
    }
} 