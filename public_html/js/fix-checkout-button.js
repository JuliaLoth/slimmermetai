/**
 * Fix voor de checkout button op de winkelwagen pagina
 * Dit bestand zorgt ervoor dat de Stripe betaalknoppen correct werken
 */

// Wacht tot het document volledig is geladen
document.addEventListener("DOMContentLoaded", function() {
    console.log("fix-checkout-button.js is geladen");
    
    // Wacht 2 seconden om ervoor te zorgen dat alle andere scripts geladen zijn
    setTimeout(activateCheckoutButton, 2000);
});

/**
 * Activeer de checkout knop en voeg event listeners toe
 */
function activateCheckoutButton() {
    console.log("Activeren van checkout knop...");
    
    // Vind de checkout knop
    var checkoutBtn = document.getElementById("checkout-btn");
    if (!checkoutBtn) {
        console.error("Checkout knop niet gevonden!");
        return;
    }
    
    console.log("Checkout knop gevonden:", checkoutBtn);
    
    // Als er items in de winkelwagen zijn, schakel de knop in
    if (typeof Cart !== "undefined" && Cart.items && Cart.items.length > 0) {
        checkoutBtn.disabled = false;
        console.log("Checkout knop ingeschakeld voor", Cart.items.length, "items");
    }
    
    // Verwijder alle bestaande event listeners (voor de zekerheid)
    var newCheckoutBtn = checkoutBtn.cloneNode(true);
    checkoutBtn.parentNode.replaceChild(newCheckoutBtn, checkoutBtn);
    checkoutBtn = newCheckoutBtn;
    
    // Voeg een nieuwe event listener toe
    checkoutBtn.addEventListener("click", function(e) {
        console.log("Checkout knop is geklikt!");
        
        // Voorkom standaard gedrag
        e.preventDefault();
        
        // Controleer of StripePayment beschikbaar is
        if (typeof StripePayment === "undefined") {
            console.error("StripePayment object is niet beschikbaar!");
            alert("Er is een probleem met het betalingssysteem. Vernieuw de pagina en probeer het opnieuw.");
            return;
        }
        
        console.log("StripePayment.handleCheckout aanroepen...");
        
        // Roep de Stripe betaalfunctie aan
        try {
            StripePayment.handleCheckout(e);
        } catch (error) {
            console.error("Fout bij aanroepen StripePayment.handleCheckout:", error);
            alert("Er is een fout opgetreden bij het starten van de betaling. Probeer het later opnieuw.");
        }
    });
    
    console.log("Event listener succesvol toegevoegd aan checkout knop");
}

// Functie om de betaalknop direct te activeren (voor handmatige uitvoering)
function fixCheckoutButtonNow() {
    console.log("Handmatig activeren van checkout knop...");
    activateCheckoutButton();
    return "Checkout knop is geactiveerd. Probeer nu op de 'Afrekenen' knop te klikken.";
}

// Voor direct gebruik in de console
window.fixCheckoutButtonNow = fixCheckoutButtonNow; 