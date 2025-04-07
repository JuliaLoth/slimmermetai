/**
 * Button.js - Herbruikbare button component
 * 
 * Gebruik:
 * 1. Importeer het script: <script src="components/Button.js"></script>
 * 2. Gebruik de component: <slimmer-button type="primary" href="/pagina.php">Tekst</slimmer-button>
 * 
 * Props:
 * - type: 'primary' | 'outline' (standaard: primary)
 * - href: URL voor link (optioneel)
 * - onClick: JavaScript functie (optioneel)
 * - size: 'small' | 'medium' | 'large' (standaard: medium)
 * - fullWidth: true | false (standaard: false)
 */

class SlimmerButton extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    this.render();
  }

  static get observedAttributes() {
    return ['type', 'href', 'size', 'full-width'];
  }

  attributeChangedCallback() {
    if (this.shadowRoot) {
      this.render();
    }
  }

  render() {
    const type = this.getAttribute('type') || 'primary';
    const href = this.getAttribute('href');
    const size = this.getAttribute('size') || 'medium';
    const fullWidth = this.hasAttribute('full-width');
    
    // CSS klassen bepalen
    let className = `btn btn-${type}`;
    
    if (size === 'small') className += ' btn-sm';
    if (size === 'large') className += ' btn-lg';
    if (fullWidth) className += ' btn-block';

    // Element bepalen (link of button)
    const element = href ? 'a' : 'button';
    const hrefAttr = href ? `href="${href}"` : '';
    
    // Event listeners
    const clickHandler = this.onClick.bind(this);

    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: inline-block;
        }
        
        :host([full-width]) {
          display: block;
          width: 100%;
        }
        
        .btn {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          padding: 0.75rem 1.5rem;
          border-radius: 8px;
          font-weight: 600;
          font-size: 1.05rem;
          text-decoration: none;
          transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
          position: relative;
          overflow: hidden;
          border: none;
          cursor: pointer;
          background-size: 200% auto;
          font-family: 'Glacial Indifference', sans-serif;
          letter-spacing: 0.02em;
          width: ${fullWidth ? '100%' : 'auto'};
        }
        
        .btn::before {
          content: '';
          position: absolute;
          top: 0;
          left: -100%;
          width: 100%;
          height: 100%;
          background: linear-gradient(
            120deg,
            transparent,
            rgba(255, 255, 255, 0.2),
            transparent
          );
          transition: 0.5s ease;
        }
        
        .btn:hover::before {
          left: 100%;
        }
        
        .btn-primary {
          background-image: linear-gradient(45deg, #5852f2, #8e88ff, #5852f2);
          background-size: 200% auto;
          color: white;
          box-shadow: 0 4px 15px rgba(88, 82, 242, 0.2);
        }
        
        .btn-primary:hover {
          background-position: right center;
          box-shadow: 0 7px 20px rgba(88, 82, 242, 0.4);
          transform: translateY(-2px);
        }
        
        .btn-outline {
          background-image: linear-gradient(45deg, transparent 50%, #5852f2 50%);
          background-size: 250% 100%;
          background-position: left bottom;
          color: #5852f2;
          border: 2px solid #5852f2;
        }
        
        .btn-outline:hover {
          background-position: right bottom;
          color: white;
          transform: translateY(-2px);
        }
        
        .btn-sm {
          padding: 0.5rem 1rem;
          font-size: 0.9rem;
        }
        
        .btn-lg {
          padding: 1rem 2rem;
          font-size: 1.2rem;
        }
        
        .btn-block {
          display: block;
          width: 100%;
        }
        
        button {
          appearance: none;
          background: transparent;
          padding: 0;
          border: 0;
          font: inherit;
          color: inherit;
        }
        
        a {
          text-decoration: none;
          color: inherit;
        }
        
        ::slotted(*) {
          margin: 0;
          padding: 0;
        }
      </style>
      <${element} class="${className}" ${hrefAttr}>
        <slot></slot>
      </${element}>
    `;
    
    // Event listeners toevoegen
    if (element === 'button') {
      this.shadowRoot.querySelector('button').addEventListener('click', clickHandler);
    }
  }
  
  onClick(event) {
    const customEvent = new CustomEvent('click', {
      bubbles: true,
      composed: true,
      detail: { originalEvent: event }
    });
    this.dispatchEvent(customEvent);
  }
  
  disconnectedCallback() {
    // Event listeners verwijderen
    if (this.shadowRoot) {
      const button = this.shadowRoot.querySelector('button');
      if (button) {
        button.removeEventListener('click', this.onClick);
      }
    }
  }
}

customElements.define('slimmer-button', SlimmerButton); 
