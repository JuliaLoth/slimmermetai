/**
 * ComponentsLoader.js - Laad alle herbruikbare componenten in één keer
 * Versie: 1.0.8 - Update 25 maart 2025
 * 
 * Gebruik:
 * 1. Importeer het script in de head van je HTML:
 *    <script src="components/ComponentsLoader.js"></script>
 * 
 * Dit script zorgt ervoor dat alle componenten automatisch worden geladen.
 */

class ComponentsLoader {
  constructor() {
    this.componentsPath = '/components/';
    // Dynamische versie gebaseerd op timestamp voor development
    this.componentsVersion = '?v=' + new Date().getTime();
    
    // Preload kritieke assets
    this.preloadCriticalAssets();
    
    // Volgorde is belangrijk - HeroContainer eerst laden omdat deze bovenaan de pagina zichtbaar is
    this.components = [
      'HeroContainer.js', // Hero component - direct zichtbaar, moet eerst geladen worden
      'Button.js',      // Basic UI component
      'Card.js',        // Card component - gebruikt door veel pagina's
      'Footer.js',      // Footer component 
      'Navbar.js',      // Navbar component - afhankelijk van andere componenten
      'UserStats.js',   // UserStats component - statistieken voor gebruikers
      'ShoppingCart.js', // ShoppingCart component - winkelwagen functionaliteit (Wordt nu direct geladen)
      'AccountNavbar.js', // AccountNavbar component - vereenvoudigde navbar voor niet-ingelogde gebruikers
      'Testimonials.js',  // Testimonials component - voor gebruikerservaringen
      'Slider.js',
      // Nieuwe Auth componenten
      'AuthLayout.js',
      'AuthFormCard.js',
      'AuthInfoCard.js'
    ];
    
    this.loadedComponents = 0;
    this.init();
  }
  
  preloadCriticalAssets() {
    // Preload hero achtergrondafbeelding
    const preloadLink = document.createElement('link');
    preloadLink.rel = 'preload';
    preloadLink.as = 'image';
    preloadLink.href = '/images/hero%20background%20def.svg';  // URL encoded spaties
    document.head.appendChild(preloadLink);

    // Preload kritieke CSS
    const cssPreloadLink = document.createElement('link');
    cssPreloadLink.rel = 'preload';
    cssPreloadLink.as = 'style';
    cssPreloadLink.href = '/css/style.css';
    document.head.appendChild(cssPreloadLink);
  }
  
  init() {
    console.log('Slimmer met AI componenten worden geladen...');
    
    // Onmiddellijk beginnen met laden zonder DOMContentLoaded af te wachten
    // voor kritieke componenten zoals HeroContainer
    this.loadComponents();
  }
  
  loadComponents() {
    return new Promise((resolve) => {
      const loadComponentSequentially = (index) => {
        if (index >= this.components.length) {
          console.log('✅ Alle Slimmer met AI componenten zijn geladen.');
          this.injectAuthContentStylesIfNeeded();
          resolve();
          
          // Trigger eventueel een custom event dat componenten geladen zijn
          document.dispatchEvent(new CustomEvent('slimmer-components-loaded'));
          return;
        }
        
        const component = this.components[index];
        this.loadComponent(component)
          .then(() => {
            this.loadedComponents++;
            loadComponentSequentially(index + 1);
          })
          .catch(error => {
            console.error(`⚠️ Fout bij laden van component: ${component}`, error);
            // Toch doorgaan met volgende component
            loadComponentSequentially(index + 1);
          });
      };
      
      // Start het laden van de componenten
      loadComponentSequentially(0);
    });
  }
  
  loadComponent(componentName) {
    return new Promise((resolve, reject) => {
      const script = document.createElement('script');
      
      // Absolute path naar component met versienummer voor cache-busting
      script.src = this.componentsPath + componentName + this.componentsVersion;
      
      // Kritieke componenten zoals HeroContainer niet asynchroon laden
      script.async = componentName !== 'HeroContainer.js';
      
      script.onload = () => {
        console.log(`✓ Component geladen: ${componentName}`);
        resolve();
      };
      
      script.onerror = (e) => {
        reject(new Error(`Fout bij laden van component: ${componentName}`));
      };
      
      document.head.appendChild(script);
    });
  }

  // Herstelde functie, injecteert nu alleen CONTENT styles
  injectAuthContentStylesIfNeeded() {
    if (document.body.classList.contains('page-login') || document.body.classList.contains('page-register')) {
      // Voorkom dubbel injecteren
      if (document.getElementById('slimmer-auth-content-styles-injected')) {
        console.log('[Loader] Auth content stijlen al geinjecteerd.');
        return;
      }

      console.log('[Loader] Login/Register pagina gedetecteerd, bezig met injecteren van minimale auth CONTENT stijlen...');
      const styleElement = document.createElement('style');
      styleElement.id = 'slimmer-auth-content-styles-injected';
      // Plak hier ALLEEN CSS regels die ABSOLUUT NODIG zijn voor de layout
      // en NIET conflicteren met globale stijlen (bv. specifieke auth layout containers)
      styleElement.textContent = `
          /* === Minimale Auth Content Stijlen (geinjecteerd door ComponentsLoader.js) === */
          /* H2, P, Input, Label, Button stijlen zijn VERWIJDERD - moeten uit style.css komen */
          
          .auth-form .form-group { /* Behoud form group margin */
              margin-bottom: 1.5rem;
          }
          .password-container {
              position: relative;
          }
          .password-toggle {
              position: absolute;
              right: 12px;
              top: 50%;
              transform: translateY(-50%);
              background: none;
              border: none;
              color: #6b7280; /* Kan mogelijk ook globaal */
              cursor: pointer;
              display: flex;
              align-items: center;
              justify-content: center;
              padding: 0;
              z-index: 2;
          }
          .password-toggle:hover {
              color: var(--primary-color, #5852f2);
          }
          .form-options {
              display: flex;
              justify-content: space-between;
              align-items: center;
              font-size: 0.875rem;
              margin-bottom: 1.5rem;
              color: #6b7280; /* Kan mogelijk ook globaal */
          }
          .checkbox-group {
              display: flex;
              align-items: center;
          }
          .checkbox-group input[type="checkbox"] {
              /* Inherit global styles? */
              margin-right: 0.5rem; 
          }
          .checkbox-group label {
             /* Inherit global styles? */
              font-size: 0.875rem;
              line-height: 1.4;
              margin-bottom: 0;
              color: #6b7280;
              font-weight: normal; /* Behoud normale gewicht indien nodig */
          }
          .checkbox-group label a {
              color: var(--primary-color, #5852f2);
              text-decoration: none;
          }
          .checkbox-group label a:hover {
              text-decoration: underline;
          }
          .forgot-link {
              color: var(--primary-color, #5852f2);
              text-decoration: none;
              transition: color 0.3s ease;
              font-size: 0.875rem;
          }
          .forgot-link:hover {
              color: var(--primary-hover, #403aa0);
              text-decoration: underline;
          }
          .social-auth {
              /* Button stijlen verwijderd */
              display: flex;
              flex-direction: column;
              gap: 0.75rem;
              margin: 2rem 0;
          }
           /* SOCIAL BUTTON STIJLEN VERWIJDERD - moeten .btn en varianten gebruiken */
          
          .auth-divider {
              display: flex;
              align-items: center;
              margin: 1.5rem 0;
              color: #6b7280;
          }
          .auth-divider::before, 
          .auth-divider::after {
              content: "";
              flex: 1;
              height: 1px;
              background-color: #e5e7eb;
          }
          .auth-divider span {
              padding: 0 1rem;
              font-size: 0.875rem;
          }
          .login-link { /* Link onderaan registratieformulier */
              text-align: center;
              margin-top: 1.5rem;
              font-size: 0.9rem;
          }
          .login-link a {
              color: var(--primary-color, #5852f2);
              font-weight: 500; /* Behoud gewicht indien nodig */
              text-decoration: none;
          }
          .login-link a:hover {
              text-decoration: underline;
          }
          .auth-form .form-row { /* Specifiek voor registratie layout? */
              display: grid;
              grid-template-columns: 1fr 1fr;
              gap: 1rem;
              margin-bottom: 1rem;
          }
          .auth-form .form-row .form-group {
              margin-bottom: 0;
          }
          .terms-checkbox {
               display: flex;
               align-items: flex-start;
               margin-bottom: 1.5rem;
           }
           .terms-checkbox input {
               /* Inherit */
               margin-right: 0.5rem;
               margin-top: 3px;
           }
           .terms-checkbox label {
               font-size: 0.9rem;
               line-height: 1.4;
           }
           /* Stijlen specifiek voor de tab-content divs zelf */
           .tab-content { 
               display: none; /* Verberg standaard */
               opacity: 0;
               transition: opacity 0.3s ease, transform 0.3s ease;
               transform: translateY(10px);
           }
           .tab-content.active {
               display: block;
               opacity: 1;
               transform: translateY(0);
           }

          /* Responsive aanpassingen voor layout */
          @media (max-width: 768px) {
               .auth-form .form-row {
                  grid-template-columns: 1fr;
                  gap: 0;
                  margin-bottom: 0;
               }
               .auth-form .form-row .form-group {
                  margin-bottom: 1.5rem;
               }
               /* Input/Button grootte aanpassingen VERWIJDERD */
          }
          /* === Einde Minimale Auth Content Stijlen === */
      `;
      document.head.appendChild(styleElement);
      console.log('[Loader] Minimale Auth CONTENT stijlen succesvol geinjecteerd.');
    } else {
       // console.log('[Loader] Geen login/register pagina, geen auth content stijlen nodig.');
    }
  }
}

// Direct uitvoeren bij laden van het script
window.slimmerComponentsLoader = new ComponentsLoader(); 