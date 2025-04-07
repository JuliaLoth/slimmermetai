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
      // 'ShoppingCart.js', // ShoppingCart component - winkelwagen functionaliteit (Wordt nu direct geladen)
      'AccountNavbar.js', // AccountNavbar component - vereenvoudigde navbar voor niet-ingelogde gebruikers
      'Testimonials.js',  // Testimonials component - voor gebruikerservaringen
      'Slider.js'        // Slider component voor tools en content
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
}

// Direct uitvoeren bij laden van het script
window.slimmerComponentsLoader = new ComponentsLoader(); 