/**
 * AccountNavbar.js - Herbruikbare navigatiebalk component met eenvoudige account toegang
 * 
 * Gebruik:
 * 1. Importeer het script: <script src="components/AccountNavbar.js"></script>
 * 2. Gebruik de component in je HTML:
 *    <slimmer-account-navbar active-page="index"></slimmer-account-navbar>
 * 
 * Props:
 * - active-page: id van de actieve pagina (bijv. "index", "tools", "e-learnings")
 * - cart-count: aantal items in winkelwagen (optioneel)
 */

class AccountNavbar extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
    
    // Menu toggle bijhouden
    this._isMenuOpen = false;
  }

  connectedCallback() {
    this.render();
    this._addEventListeners();
    this._updateCartCountFromStorage();
  }

  static get observedAttributes() {
    return [
      'active-page',
      'cart-count'
    ];
  }

  attributeChangedCallback() {
    if (this.shadowRoot) {
      this.render();
    }
  }

  _addEventListeners() {
    // Mobile menu toggle
    const mobileMenuBtn = this.shadowRoot.querySelector('.mobile-menu-button');
    if (mobileMenuBtn) {
      mobileMenuBtn.addEventListener('click', () => this._toggleMobileMenu());
    }

    // Scroll event voor sticky header
    window.addEventListener('scroll', () => {
      const header = this.shadowRoot.querySelector('header');
      if (header) {
        if (window.scrollY > 10) {
          header.classList.add('sticky');
        } else {
          header.classList.remove('sticky');
        }
      }
    });
  }
  
  _toggleMobileMenu() {
    this._isMenuOpen = !this._isMenuOpen;
    this.render();
  }

  _updateCartCountFromStorage() {
    try {
      const cart = JSON.parse(localStorage.getItem('cart') || '[]');
      const itemCount = cart.reduce((sum, item) => sum + item.quantity, 0);
      const cartCountSpan = this.shadowRoot.querySelector('.cart-count');
      if (cartCountSpan) {
        cartCountSpan.textContent = itemCount;
      }
    } catch (error) {
      console.error('Fout bij het bijwerken van de winkelwagenteller:', error);
    }
  }

  render() {
    const activePage = this.getAttribute('active-page') || '';
    
    // Navigatielinks met hun labels en paden
    const navLinks = [
      { id: 'index', label: 'Home', path: 'index.php' },
      { id: 'tools', label: 'Tools', path: 'tools.php' },
      { id: 'e-learnings', label: 'Cursussen', path: 'e-learnings.php' },
      { id: 'over-mij', label: 'Over Mij', path: 'over-mij.php' },
      { id: 'nieuws', label: 'Nieuws', path: 'nieuws.php' }
    ];
    
    // Menu klasse voor mobiel
    let navLinksClass = 'nav-links';
    if (this._isMenuOpen) {
      navLinksClass += ' active';
    }
    
    // Bouw navigatie links HTML
    const navLinksHtml = navLinks.map(link => {
      const isCurrent = link.id === activePage ? 'page' : 'false';
      return `
        <a href="${link.path}" aria-current="${isCurrent}">${link.label}</a>
      `;
    }).join('');

    // Winkelwagen button HTML - herbruikbaar
    const cartButtonHtml = `
      <a href="winkelwagen.php" class="cart-button" aria-label="Winkelwagen">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <span class="cart-count">0</span>
      </a>
    `;

    // Account knop HTML
    const accountButtonHtml = `
      <a href="login.php" class="account-btn">
        Account
      </a>
    `;

    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
          --primary-color: #5852f2;
          --primary-hover: #4a45d1;
          --accent-color: #db2777;
          --gradient-bg: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
        }
        
        /* Header en navigatie */
        header {
          background-color: #fff;
          box-shadow: 0 2px 4px rgba(0,0,0,0.1);
          position: fixed;
          width: 100%;
          top: 0;
          z-index: 1000;
          transition: transform 0.3s ease, background-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        header.sticky {
          box-shadow: 0 4px 10px rgba(0,0,0,0.1);
          background-color: rgba(255, 255, 255, 0.98);
        }
        
        .container {
          max-width: 1150px;
          padding: 0 20px;
          margin: 0 auto;
        }
        
        .navbar {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 1.2rem 0;
          max-width: 1200px;
          margin: 0 auto;
        }
        
        /* Logo styling - aangepast naar huidige site */
        .navbar .logo a {
          display: flex !important;
          align-items: center !important;
          text-decoration: none !important;
          color: var(--text-color) !important;
          font-weight: 600 !important;
          font-family: 'Glacial Indifference', sans-serif !important;
          transition: all 0.3s ease !important;
        }
        
        .navbar .logo a:hover {
          transform: scale(1.05) !important;
        }
        
        .navbar .logo img {
          margin-right: 12px !important;
          border-radius: 10px !important;
          transition: all 0.3s ease !important;
          width: 55px !important;
        }
        
        .navbar .logo-text {
          font-size: 1.6rem !important;
          font-weight: 600 !important;
          transition: all 0.3s ease !important;
          color: var(--text-color) !important;
        }
        
        .navbar .logo a:hover .logo-text {
          color: var(--accent-color) !important;
        }
        
        /* Navigatie links - aangepast naar huidige site */
        .nav-links {
          display: flex;
          align-items: center;
          gap: 2.5rem;
        }
        
        .nav-links a {
          color: #4b5563;
          text-decoration: none;
          font-weight: 500;
          font-size: 18px;
          transition: color 0.3s;
          position: relative;
          padding: 0.6rem 0;
          font-family: 'Glacial Indifference', sans-serif;
        }
        
        .nav-links a:hover {
          color: var(--primary-color);
        }
        
        .nav-links a[aria-current="page"] {
          color: var(--primary-color);
          font-weight: 600;
        }
        
        .nav-links a::after {
          content: '';
          position: absolute;
          bottom: 0;
          left: 0;
          width: 0;
          height: 2px;
          background: var(--gradient-bg);
          transition: width 0.3s ease;
        }
        
        .nav-links a:hover::after,
        .nav-links a[aria-current="page"]::after {
          width: 100%;
        }
        
        /* Account button en dropdown */
        .auth-buttons {
          display: flex;
          align-items: center;
          gap: 1rem;
        }
        
        .account-btn {
          display: inline-flex;
          align-items: center;
          padding: 0.6rem 1rem;
          border-radius: 8px;
          background-color: #f3f4f6;
          color: #374151;
          font-weight: 500;
          font-family: 'Glacial Indifference', sans-serif;
          cursor: pointer;
          transition: all 0.2s ease;
          border: none;
          gap: 0.5rem;
          font-size: 1rem;
          text-decoration: none;
        }
        
        .account-btn:hover {
          background-color: #e5e7eb;
        }
        
        .account-btn svg {
          opacity: 0.7;
        }
        
        /* Winkelwagen knop */
        .cart-button {
          display: flex;
          align-items: center;
          justify-content: center;
          position: relative;
          width: 40px;
          height: 40px;
          border-radius: 6px;
          background-color: #f3f4f6;
          color: #374151;
          transition: all 0.3s ease;
        }
        
        .cart-button:hover {
          background-color: #e5e7eb;
          color: #1f2937;
        }
        
        .cart-count {
          position: absolute;
          top: -8px;
          right: -8px;
          background-color: var(--accent-color);
          color: white;
          font-size: 12px;
          font-weight: 600;
          height: 20px;
          width: 20px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
        }
        
        /* Mobile styling en responsiveness */
        .mobile-menu-button {
          display: none;
          background: none;
          border: none;
          padding: 0.5rem;
          cursor: pointer;
          color: #4b5563;
        }
        
        @media (max-width: 1024px) {
          .navbar {
            padding: 1rem 0;
          }
          
          .nav-links {
            gap: 2rem;
          }
        }
        
        @media (max-width: 768px) {
          .mobile-menu-button {
            display: flex;
            z-index: 110;
          }
          
          .nav-links {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.98);
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            padding: 2rem;
            z-index: 100;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
          }
          
          .nav-links.active {
            transform: translateX(0);
          }
          
          .nav-links a {
            font-size: 20px;
          }
        }
      </style>
      
      <header>
        <div class="container">
          <div class="navbar">
            <div class="logo">
              <a href="index.php">
                <img src="images/Logo.svg" alt="Slimmer met AI logo">
                <span class="logo-text">Slimmer met AI</span>
              </a>
            </div>
            
            <button class="mobile-menu-button" aria-label="Menu" aria-expanded="${this._isMenuOpen}">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
              </svg>
            </button>
            
            <div class="${navLinksClass}">
              ${navLinksHtml}
            </div>
            
            <div class="auth-buttons">
              ${accountButtonHtml}
              ${cartButtonHtml}
            </div>
          </div>
        </div>
      </header>
    `;
  }
  
  disconnectedCallback() {
    // Cleanup event listeners
    const mobileMenuBtn = this.shadowRoot.querySelector('.mobile-menu-button');
    if (mobileMenuBtn) {
      mobileMenuBtn.removeEventListener('click', this._toggleMobileMenu);
    }
    
    // Remove scroll event listener
    window.removeEventListener('scroll', this._handleScroll);
  }
}

// Registreer de component
customElements.define('slimmer-account-navbar', AccountNavbar); 
