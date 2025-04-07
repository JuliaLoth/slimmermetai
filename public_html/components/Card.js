/**
 * Card.js - Herbruikbare card component
 * 
 * Gebruik:
 * 1. Importeer het script: <script src="components/Card.js"></script>
 * 2. Gebruik de component: 
 *    <slimmer-card image="/images/voorbeeld.jpg" title="Titel">
 *      <p>Inhoud van de card</p>
 *      <div slot="actions">
 *        <slimmer-button href="/pagina.php">Actie</slimmer-button>
 *      </div>
 *    </slimmer-card>
 * 
 * Props:
 * - image: Pad naar afbeelding (optioneel)
 * - title: Titel van de kaart (verplicht)
 * - link: URL voor als de hele kaart klikbaar moet zijn (optioneel)
 * - featured: true voor uitgelichte kaart (optioneel)
 * - comingSoon: true voor "binnenkort beschikbaar" (optioneel)
 */

class SlimmerCard extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    this.render();
  }

  static get observedAttributes() {
    return ['image', 'title', 'link', 'featured', 'coming-soon'];
  }

  attributeChangedCallback() {
    if (this.shadowRoot) {
      this.render();
    }
  }

  render() {
    const image = this.getAttribute('image');
    const title = this.getAttribute('title');
    const link = this.getAttribute('link');
    const featured = this.hasAttribute('featured');
    const comingSoon = this.hasAttribute('coming-soon');
    
    // Card klassen bepalen
    let className = 'card';
    if (featured) className += ' featured';
    if (comingSoon) className += ' coming-soon';
    
    // Wrapper element bepalen (link of div)
    const wrapperTag = link ? 'a' : 'div';
    const hrefAttr = link ? `href="${link}"` : '';
    
    // Coming soon overlay
    const comingSoonOverlay = comingSoon ? `
      <div class="coming-soon-overlay">
        <span class="coming-soon-text">Binnenkort beschikbaar</span>
      </div>
    ` : '';

    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
        }
        
        .card {
          background-color: white;
          border-radius: 12px;
          overflow: hidden;
          box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
          transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
          position: relative;
          height: 100%;
          display: flex;
          flex-direction: column;
          transform: translateY(0);
        }
        
        .card::after {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
          opacity: 0;
          transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
          z-index: -1;
          border-radius: 12px;
        }
        
        .card:hover {
          transform: translateY(-10px);
        }
        
        .card:hover::after {
          opacity: 1;
        }
        
        .card-image {
          width: 100%;
          height: 200px;
          overflow: hidden;
          position: relative;
        }
        
        .card-image img {
          width: 100%;
          height: 100%;
          object-fit: cover;
          transition: transform 0.5s ease;
        }
        
        .card:hover .card-image img {
          transform: scale(1.05);
        }
        
        .card-content {
          padding: 1.5rem;
          flex-grow: 1;
          display: flex;
          flex-direction: column;
        }
        
        .card h3 {
          margin: 0 0 0.75rem;
          font-family: 'Glacial Indifference', sans-serif;
          font-weight: bold;
          font-size: 1.5rem;
          color: #1f2937;
        }
        
        .card-header {
          display: flex;
          align-items: center;
          margin-bottom: 1rem;
          gap: 1rem;
        }
        
        .card-header h3 {
          margin: 0;
        }
        
        /* Styling voor card icons binnen de kaart header */
        ::slotted(.card-icon) {
          display: flex;
          align-items: center;
          min-width: 36px;
        }
        
        ::slotted(.card-icon svg) {
          width: 32px;
          height: 32px;
          color: var(--primary-color, #5852f2);
        }
        
        .card-text {
          margin: 0 0 1.5rem;
          color: #4b5563;
          flex-grow: 1;
        }
        
        .card-actions {
          margin-top: auto;
          display: flex;
          justify-content: flex-start;
          gap: 0.75rem;
        }
        
        .featured {
          border: 2px solid #5852f2;
        }
        
        .coming-soon {
          position: relative;
          overflow: hidden;
        }
        
        .coming-soon-overlay {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(31, 41, 55, 0.75);
          display: flex;
          justify-content: center;
          align-items: center;
          z-index: 2;
          transition: all 0.3s ease;
        }
        
        .coming-soon-text {
          color: white;
          font-family: 'Glacial Indifference', sans-serif;
          font-weight: bold;
          font-size: 1.5rem;
          background: linear-gradient(135deg, #5852f2 0%, #db2777 100%);
          padding: 0.5rem 1.5rem;
          border-radius: 8px;
          transform: rotate(-5deg);
          box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .card.coming-soon:hover .coming-soon-overlay {
          background-color: rgba(31, 41, 55, 0.85);
        }
        
        a {
          text-decoration: none;
          color: inherit;
          display: block;
          height: 100%;
        }
        
        ::slotted(p) {
          margin: 0 0 1rem;
          color: #4b5563;
        }
      </style>
      
      <${wrapperTag} class="${className}" ${hrefAttr}>
        ${comingSoonOverlay}
        ${image ? `
          <div class="card-image">
            <img src="${image}" alt="${title || 'Card afbeelding'}">
          </div>
        ` : ''}
        <div class="card-content">
          <div class="card-header">
            <slot name="icon"></slot>
            <h3>${title || ''}</h3>
          </div>
          <div class="card-text">
            <slot></slot>
          </div>
          <div class="card-actions">
            <slot name="actions"></slot>
          </div>
        </div>
      </${wrapperTag}>
    `;
  }
}

customElements.define('slimmer-card', SlimmerCard); 
