/**
 * Payment.js - Algemene betaalfuncties voor Slimmer met AI
 * Dit bestand dient als wrapper voor het Stripe betalingssysteem
 */

// Import StripePayment from the stripe-payment module
import './stripe-payment.js';

// Wacht tot het DOM geladen is
const Payment = {
  initialized: false,
  
  /**
   * Initialiseer het betalingssysteem
   */
  async init() {
    if (this.initialized) {
      console.log('Payment systeem is al geïnitialiseerd');
      return;
    }
    
    console.log('Payment systeem wordt geïnitialiseerd...');
    
    // Wacht tot StripePayment beschikbaar is
    return new Promise((resolve, reject) => {
      const checkStripePayment = () => {
        if (typeof window.StripePayment !== 'undefined') {
          console.log('StripePayment gevonden, initialiseren...');
          this.initialized = true;
          
          // Initialiseer StripePayment
          if (window.StripePayment.init) {
            window.StripePayment.init();
          }
          resolve();
        } else {
          // Probeer nogmaals na 100ms, max 50 pogingen (5 seconden)
          setTimeout(checkStripePayment, 100);
        }
      };
      
      checkStripePayment();
      
      // Timeout na 5 seconden
      setTimeout(() => {
        if (!this.initialized) {
          console.error('Timeout: StripePayment kon niet worden geladen');
          reject(new Error('StripePayment timeout'));
        }
      }, 5000);
    });
  },
  
  /**
   * Start een betaalproces
   * @param {Object} data - Betaalgegevens (optioneel)
   */
  async startPayment(data = null) {
    console.log('startPayment aangeroepen...');
    
    try {
      // Zorg dat Payment geïnitialiseerd is
      if (!this.initialized) {
        console.log('Payment niet geïnitialiseerd, initialiseren...');
        await this.init();
      }
      
      // Controleer of StripePayment beschikbaar is
      if (typeof window.StripePayment === 'undefined') {
        throw new Error('StripePayment is niet beschikbaar');
      }
      
      // Als er geen data is, probeer vanuit winkelwagen
      if (!data) {
        console.log('Geen data opgegeven, starten checkout vanuit winkelwagen...');
        
        // Controleer of er een checkout methode is
        if (window.StripePayment.handleCheckout) {
          return await window.StripePayment.handleCheckout();
        } else if (window.StripePayment.startCheckout) {
          return await window.StripePayment.startCheckout();
        } else {
          throw new Error('Geen checkout methode gevonden in StripePayment');
        }
      } else {
        // Gebruik opgegeven data
        return await window.StripePayment.handleCheckout(data);
      }
      
    } catch (error) {
      console.error('Fout bij starten betaling:', error);
      throw error;
    }
  }
};

// Exporteer voor gebruik in andere modules
export default Payment;

// Voor backwards compatibiliteit - maak ook beschikbaar op window
window.Payment = Payment;

// Auto-initialiseer wanneer DOM geladen is
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    Payment.init().catch(error => {
      console.error('Fout bij auto-initialisatie Payment:', error);
    });
  });
} else {
  // DOM is al geladen
  Payment.init().catch(error => {
    console.error('Fout bij auto-initialisatie Payment:', error);
  });
} 