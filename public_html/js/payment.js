/**
 * Payment.js - Algemene betaalfuncties voor Slimmer met AI
 * Dit bestand dient als wrapper voor het Stripe betalingssysteem
 */

// Controleer of StripePayment al geladen is
if (typeof StripePayment === 'undefined') {
  console.warn('StripePayment is nog niet geladen. Payment.js wacht op StripePayment...');
  
  // Probeer het Stripe-payment.js bestand te laden
  const script = document.createElement('script');
  script.src = '/js/stripe-payment.js';
  script.async = true;
  document.head.appendChild(script);
  
  // Wacht tot Stripe-payment.js is geladen
  script.onload = function() {
    console.log('Stripe-payment.js is geladen. Payment.js initialiseert...');
    initializePayment();
  };
  
  script.onerror = function() {
    console.error('Kon Stripe-payment.js niet laden. Betalingen werken mogelijk niet correct.');
  };
} else {
  console.log('StripePayment is al geladen. Payment.js initialiseert...');
  initializePayment();
}

/**
 * Initialiseer het betalingssysteem
 */
function initializePayment() {
  // Initialiseer Stripe betalingen wanneer document is geladen
  if (document.readyState === 'complete' || document.readyState === 'interactive') {
    setTimeout(function() {
      if (typeof StripePayment !== 'undefined' && StripePayment.init) {
        StripePayment.init();
      }
    }, 100);
  } else {
    document.addEventListener('DOMContentLoaded', function() {
      setTimeout(function() {
        if (typeof StripePayment !== 'undefined' && StripePayment.init) {
          StripePayment.init();
        }
      }, 100);
    });
  }
}

// Exporteer algemene betalingsfuncties
const Payment = {
  /**
   * Start een betaalproces
   * @param {Object} data - Betaalgegevens
   */
  startPayment: function(data) {
    if (typeof StripePayment !== 'undefined' && StripePayment.handleCheckout) {
      return StripePayment.handleCheckout(data);
    } else {
      console.error('StripePayment is niet beschikbaar. Kan betaalproces niet starten.');
      return Promise.reject(new Error('Betalingssysteem is niet beschikbaar'));
    }
  }
};

// Voor backwards compatibiliteit
window.Payment = Payment; 