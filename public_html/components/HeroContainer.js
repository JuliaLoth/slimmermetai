/**
 * HeroContainer.js - Herbruikbare hero container component
 * 
 * Gebruik:
 * 1. Importeer het script: <script src="components/HeroContainer.js"></script>
 * 2. Gebruik de component:
 *    <slimmer-hero 
 *      title="Welkom bij Slimmer met AI" 
 *      subtitle="Praktische AI-tools voor Nederlandse professionals"
 *      background="gradient">
 *      <div slot="actions">
 *        <slimmer-button href="/pagina.php">Actie</slimmer-button>
 *      </div>
 *    </slimmer-hero>
 * 
 * Props:
 * - title: Titel van de hero sectie (verplicht)
 * - subtitle: Ondertitel (optioneel)
 * - background: "gradient" | "image" | "none" (standaard: gradient)
 * - image-url: URL naar achtergrondafbeelding (alleen bij background="image")
 * - centered: true voor gecentreerde tekst (optioneel)
 */

class SlimmerHero extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
    
    // Direct de basis HTML structuur opzetten
    this.shadowRoot.innerHTML = this.getBaseTemplate();
    
    // Cache belangrijke elementen
    this.heroContent = this.shadowRoot.querySelector('.hero-content');
    this.titleElement = this.shadowRoot.querySelector('h1');
    this.subtitleElement = this.shadowRoot.querySelector('p');
    this.actionsContainer = this.shadowRoot.querySelector('.hero-actions');
    
    // Preload achtergrondafbeelding
    if (this.getAttribute('background') === 'image') {
      this.preloadBackgroundImage();
    }
  }

  getBaseTemplate() {
    return `
      <style>
        :host {
          display: block;
          --primary-color: #5852f2;
          --accent-color: #db2777;
          --gradient-bg: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
        }
        
        /* Hero met achtergrond stijl, gebaseerd op index.html */
        .hero-with-background {
          padding: 10rem 0 6rem;
          text-align: center;
          background-color: transparent;
          position: relative;
          overflow: hidden;
          margin-top: 0;
          background-size: cover;
          background-position: center;
          background-repeat: no-repeat;
        }
        
        /* Gradient overlay */
        .hero-with-background::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: linear-gradient(135deg, rgba(88, 82, 242, 0.1) 0%, rgba(219, 39, 119, 0.1) 100%);
          z-index: 0;
        }
        
        .container {
          max-width: 1150px;
          padding: 0 20px;
          margin: 0 auto;
          position: relative;
          z-index: 1;
        }
        
        /* Witte container achter de inhoud */
        .hero-content {
          max-width: 800px;
          margin: 0 auto;
          padding: 3rem;
          background: rgba(255, 255, 255, 0.95);
          border-radius: 20px;
          box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
          position: relative;
          z-index: 1;
          text-align: left;
        }
        
        h1 {
          font-size: 3.5rem;
          line-height: 1.2;
          margin-bottom: 1.5rem;
          font-family: 'Glacial Indifference', sans-serif;
          font-weight: bold;
          color: var(--text-color);
          background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
          text-fill-color: transparent;
        }
        
        p {
          font-size: 1.25rem;
          color: #4b5563;
          margin-bottom: 2rem;
          line-height: 1.6;
        }
        
        .hero-actions {
          display: flex;
          gap: 1rem;
          justify-content: flex-start;
          margin-top: 2rem;
        }
        
        /* Responsive aanpassingen */
        @media (max-width: 768px) {
          .hero-with-background {
            padding: 8rem 0 4rem;
          }
          
          .hero-content {
            padding: 2rem;
            width: 90%;
          }
          
          h1 {
            font-size: 2.5rem;
          }
          
          p {
            font-size: 1.1rem;
          }
          
          .hero-actions {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
          }
        }
        
        @media (max-width: 480px) {
          h1 {
            font-size: 2rem;
          }
        }
      </style>
      
      <div class="hero-with-background">
        <div class="container">
          <div class="hero-content">
            <h1></h1>
            <p style="display: none;"></p>
            <div class="hero-actions">
              <slot name="actions"></slot>
            </div>
            <slot></slot>
          </div>
        </div>
      </div>
    `;
  }

  preloadBackgroundImage() {
    const imageUrl = this.getAttribute('image-url') || 'images/hero background def.svg';
    const img = new Image();
    img.onload = () => {
      this.shadowRoot.querySelector('.hero-with-background').style.backgroundImage = `url('${imageUrl}')`;
    };
    img.src = imageUrl;
  }

  connectedCallback() {
    this.updateContent();
  }

  static get observedAttributes() {
    return ['title', 'subtitle', 'background', 'image-url', 'centered'];
  }

  attributeChangedCallback(name, oldValue, newValue) {
    if (oldValue !== newValue) {
      this.updateContent();
    }
  }

  updateContent() {
    const title = this.getAttribute('title') || '';
    const subtitle = this.getAttribute('subtitle') || '';
    const centered = this.hasAttribute('centered');
    
    // Update alleen wat nodig is
    if (this.titleElement) {
      this.titleElement.textContent = title;
    }
    
    if (this.subtitleElement) {
      if (subtitle) {
        this.subtitleElement.textContent = subtitle;
        this.subtitleElement.style.display = 'block';
      } else {
        this.subtitleElement.style.display = 'none';
      }
    }
    
    if (this.heroContent) {
      this.heroContent.style.textAlign = centered ? 'center' : 'left';
    }
    
    if (this.actionsContainer) {
      this.actionsContainer.style.justifyContent = centered ? 'center' : 'flex-start';
    }
  }
}

// Registreer de component direct
customElements.define('slimmer-hero', SlimmerHero); 
