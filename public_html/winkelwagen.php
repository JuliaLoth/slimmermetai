<?php
/**
 * Winkelwagen pagina van SlimmerMetAI.com
 */

// Stel pagina variabelen in
$page_title = 'Winkelwagen | Slimmer met AI';
$page_description = 'Bekijk uw winkelwagen en rond uw bestelling af voor Slimmer met AI tools en cursussen.';
$active_page = 'winkelwagen';

// Laad de header (deze laadt init.php, head, navbar en opent <main>)
require_once 'includes/header.php';
?>

<!-- Winkelwagen Hero sectie -->
<slimmer-hero 
    title="Winkelwagen" 
    subtitle="Bekijk je geselecteerde items en rond je bestelling af"
    background="image"
    image-url="images/hero-background.svg"
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

<slimmer-footer></slimmer-footer>

</main>

<!-- Specifieke scripts voor Stripe betaling -->
<script src="<?php echo asset_url('js/stripe-payment.js'); ?>"></script>
<script src="<?php echo asset_url('js/payment.js'); ?>"></script>
<script src="<?php echo asset_url('js/fix-checkout-button.js'); ?>"></script>

</body>
</html> 

