<?php
/**
 * Winkelwagen pagina van SlimmerMetAI.com
 */

// Initialiseer de website met het standaard init script
require_once dirname(__DIR__) . '/includes/init.php';

// Stel pagina variabelen in
$page_title = 'Winkelwagen | Slimmer met AI';
$page_description = 'Bekijk uw winkelwagen en rond uw bestelling af voor Slimmer met AI tools en cursussen.';
$active_page = 'winkelwagen';

// Laad de header
require_once 'includes/header.php';
?>

<!-- Winkelwagen Hero sectie -->
<slimmer-hero 
    title="Winkelwagen" 
    subtitle="Bekijk je geselecteerde items en rond je bestelling af"
    background="image"
    image-url="images/hero background def.svg"
    centered>
</slimmer-hero>

<!-- Winkelwagen inhoud sectie -->
<section class="section cart-section">
    <div class="container">
        <!-- Winkelwagen component -->
        <div id="cart-items-container">
            <div id="cart-items">
                <!-- Hier worden de winkelwagen items dynamisch geladen -->
                <div class="empty-cart-message">
                    <p>Je winkelwagen wordt geladen...</p>
                </div>
            </div>
            
            <!-- Winkelwagen samenvatting -->
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

<!-- Aanbevolen producten sectie -->
<section class="section recommended-section">
    <div class="container">
        <h2>Aanbevolen voor jou</h2>
        <div class="products-grid">
            <!-- Aanbevolen product 1 -->
            <div class="product-card">
                <div class="product-image">
                    <img src="images/email-assistant.svg" alt="Email Assistent Plus">
                </div>
                <div class="product-details">
                    <h3>Email Assistent Plus</h3>
                    <span class="product-type">Tool</span>
                    <div class="product-price">€19,99</div>
                    <button 
                        class="btn btn-primary add-to-cart-btn" 
                        data-product-id="email-assistant" 
                        data-product-type="Tool" 
                        data-product-name="Email Assistent Plus" 
                        data-product-price="19.99" 
                        data-product-img="images/email-assistant.svg">
                        Toevoegen aan Winkelwagen
                    </button>
                </div>
            </div>
            
            <!-- Aanbevolen product 2 -->
            <div class="product-card">
                <div class="product-image">
                    <img src="images/meeting-summarizer.svg" alt="AI Basis Cursus">
                </div>
                <div class="product-details">
                    <h3>AI Basis: Van nul naar praktijk</h3>
                    <span class="product-type">E-learning</span>
                    <div class="product-price">€29,99</div>
                    <button 
                        class="btn btn-primary add-to-cart-btn" 
                        data-product-id="ai-basics" 
                        data-product-type="E-learning" 
                        data-product-name="AI Basis: Van nul naar praktijk" 
                        data-product-price="29.99" 
                        data-product-img="images/meeting-summarizer.svg">
                        Toevoegen aan Winkelwagen
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container" style="margin-top: 100px; padding: 20px;">
    <h1>Winkelwagen Fix Script</h1>
    <p>Dit script is uitgevoerd om het probleem met het winkelwagenicoontje op te lossen.</p>
    
    <div class="alert success">
        <p>De winkelwagen functionaliteit is succesvol gerepareerd!</p>
        <p>Het winkelwagenicoontje zou nu correct moeten werken na het toevoegen of verwijderen van items.</p>
    </div>
    
    <div class="buttons">
        <a href="winkelwagen.php" class="btn btn-primary">Naar Winkelwagen</a>
        <a href="index.php" class="btn btn-outline">Terug naar Home</a>
    </div>
</div>

<script>
// Script om het winkelwagenicoontje direct bij te werken
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Cart !== 'undefined') {
        // Force refresh van de cart count
        Cart.init(true);
        
        // Extra check voor het bijwerken van de navbar
        const navbar = document.querySelector('slimmer-navbar');
        if (navbar && Cart.items) {
            const itemCount = Cart.items.reduce((total, item) => total + item.quantity, 0);
            navbar.setAttribute('cart-count', itemCount.toString());
        }
        
        console.log('Winkelwagen fix script is uitgevoerd');
    } else {
        console.error('Cart object is niet beschikbaar');
    }
});
</script>

<?php
// Voeg extra scripts toe die specifiek voor de winkelwagen pagina nodig zijn
$extra_scripts = '
<script src="' . asset_url('js/cart.js') . '"></script>
<script src="' . asset_url('js/stripe-payment.js') . '"></script>
<script src="' . asset_url('js/payment.js') . '"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Wacht kort om ervoor te zorgen dat alles is geladen
        setTimeout(function() {
            if (typeof Cart !== "undefined") {
                // Force de winkelwagen om opnieuw te laden
                Cart.init(true);
                Cart.renderCartItems();
                Cart.updateCartSummary();
            }
            
            // Initialiseer de Stripe integratie
            if (typeof StripePayment !== "undefined") {
                StripePayment.init();
            }
        }, 500);
    });
</script>';

// Laad de footer
require_once 'includes/footer.php';
?> 