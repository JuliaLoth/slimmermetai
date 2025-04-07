/**
 * Navbar.js - Herbruikbare navigatiebalk component
 * 
 * Gebruik:
 * 1. Importeer het script: <script src="components/Navbar.js"></script>
 * 2. Gebruik de component in je HTML:
 *    <slimmer-navbar active-page="index"></slimmer-navbar>
 * 
 * Props:
 * - active-page: id van de actieve pagina (bijv. "index", "tools", "e-learnings")
 * - user-logged-in: true als gebruiker is ingelogd (optioneel)
 * - user-name: naam van de ingelogde gebruiker (optioneel)
 * - user-avatar: URL naar avatar van de ingelogde gebruiker (optioneel)
 * - cart-count: aantal items in winkelwagen (optioneel)
 */

class SlimmerNavbar extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
    
    // Menu toggle bijhouden
    this._isMenuOpen = false;
  }

  connectedCallback() {
    this.render();
    this._addEventListeners();
  }

  static get observedAttributes() {
    return [
      'active-page',
      'user-logged-in',
      'user-name',
      'user-avatar',
      'cart-count'
    ];
  }

  attributeChangedCallback(name, oldValue, newValue) {
    // Vereenvoudigd: Render de component altijd opnieuw wanneer een geobserveerd attribuut verandert.
    if (this.shadowRoot && oldValue !== newValue) {
      console.log(`SlimmerNavbar: Re-rendering due to change in attribute '${name}' from '${oldValue}' to '${newValue}'`);
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
  
  _isAdmin() {
    // Check of gebruiker admin is - eenvoudige implementatie voor demo
    return localStorage.getItem('isAdmin') === 'true';
  }

  render() {
    const activePage = this.getAttribute('active-page') || '';
    const isLoggedIn = this.hasAttribute('user-logged-in');
    const userName = this.getAttribute('user-name') || 'Account';
    const userAvatar = this.getAttribute('user-avatar') || 'images/profile-placeholder.svg';
    const cartCount = this.getAttribute('cart-count') || '0';
    
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

    // Winkelwagen button HTML - herbruikbaar voor beide scenarios
    const cartButtonHtml = `
      <a href="winkelwagen.php" class="cart-button" aria-label="Winkelwagen">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <span class="cart-count" style="display: ${parseInt(cartCount) > 0 ? 'flex' : 'none'};">${cartCount}</span>
      </a>
    `;

    // Inloggen/Account dropdown HTML
    const authHtml = isLoggedIn 
      ? `
        <div class="auth-buttons">
          <div class="account-dropdown">
            <button class="account-btn" aria-expanded="false" aria-haspopup="true" aria-label="Account menu">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              Account
            </button>
            <div class="dropdown-menu" aria-hidden="true">
              <a href="dashboard.php">Dashboard</a>
              <a href="profiel.php">Mijn profiel</a>
              <a href="mijn-tools.php">Mijn tools</a>
              <a href="mijn-cursussen.php">Mijn cursussen</a>
              ${this._isAdmin() ? '<a href="admin/dashboard.php">Admin paneel</a>' : ''}
              <a href="#" class="logout-link" id="logout-btn">Uitloggen</a>
            </div>
          </div>
          ${cartButtonHtml}
        </div>
      `
      : `
        <div class="auth-buttons">
          <a href="login.php" class="account-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Account
          </a>
          ${cartButtonHtml}
        </div>
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
        
        /* Account dropdown */
        .account-dropdown {
          position: relative;
          z-index: 100;
        }
        
        .dropdown-menu {
          position: absolute;
          top: calc(100% + 5px);
          right: 0;
          background-color: white;
          box-shadow: 0 4px 12px rgba(0,0,0,0.1);
          border-radius: 8px;
          min-width: 200px;
          display: none;
          overflow: hidden;
          z-index: 101;
          border: 1px solid rgba(229, 231, 235, 0.6);
        }
        
        .dropdown-menu a {
          display: block;
          padding: 0.75rem 1rem;
          text-decoration: none;
          color: #374151;
          font-family: 'Glacial Indifference', sans-serif;
          transition: all 0.2s ease;
        }
        
        .dropdown-menu a:hover {
          background-color: #f3f4f6;
          color: #5852f2;
        }
        
        .dropdown-menu .logout-link {
          border-top: 1px solid #e5e7eb;
          color: #ef4444;
        }
        
        .dropdown-menu .logout-link:hover {
          background-color: #FEF2F2;
        }
        
        .account-dropdown.open .dropdown-menu {
          display: block;
          animation: fadeIn 0.2s ease forwards;
        }
        
        @keyframes fadeIn {
          from {
            opacity: 0;
            transform: translateY(-10px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }
        
        .dropdown-menu a[aria-current="page"] {
          background-color: #f9fafb;
          color: #5852f2;
          font-weight: 600;
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
        
        /* Primary Button (Aanmelden) */
        .btn {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          padding: 0.75rem 1.25rem;
          border-radius: 8px;
          font-weight: 600;
          transition: all 0.3s ease;
          text-decoration: none;
          font-family: 'Glacial Indifference', sans-serif;
          font-size: 1rem;
        }
        
        .btn-primary {
          background: var(--gradient-bg);
          color: white;
          border: none;
        }
        
        .btn-primary:hover {
          box-shadow: 0 4px 12px rgba(88, 82, 242, 0.25);
          transform: translateY(-2px);
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
                ${this._isMenuOpen 
                  ? '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>' 
                  : '<line x1="4" y1="12" x2="20" y2="12"></line><line x1="4" y1="6" x2="20" y2="6"></line><line x1="4" y1="18" x2="20" y2="18"></line>'}
              </svg>
            </button>
            
            <nav class="${navLinksClass}" role="navigation">
              ${navLinksHtml}
            </nav>
            
            ${authHtml}
          </div>
        </div>
      </header>
    `;
    
    // Event listeners voor dropdown toevoegen na het renderen
    this._addDropdownEventListeners();
  }
  
  _addDropdownEventListeners() {
    const accountDropdown = this.shadowRoot.querySelector('.account-dropdown');
    if (!accountDropdown) return;
    
    const accountBtn = accountDropdown.querySelector('.account-btn');
    const dropdownMenu = accountDropdown.querySelector('.dropdown-menu');
    const logoutBtn = accountDropdown.querySelector('#logout-btn');
    
    // Toggle dropdown bij klikken op button
    accountBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      accountDropdown.classList.toggle('open');
      
      // ARIA attributen bijwerken
      const isExpanded = accountDropdown.classList.contains('open');
      accountBtn.setAttribute('aria-expanded', isExpanded);
      dropdownMenu.setAttribute('aria-hidden', !isExpanded);
    });
    
    // Sluit dropdown als ergens anders geklikt wordt
    document.addEventListener('click', () => {
      accountDropdown.classList.remove('open');
      accountBtn.setAttribute('aria-expanded', 'false');
      dropdownMenu.setAttribute('aria-hidden', 'true');
    });
    
    // Voorkom sluiten bij klikken binnen dropdown
    dropdownMenu.addEventListener('click', (e) => {
      e.stopPropagation();
    });
    
    // Sluit dropdown bij klikken op een link in de dropdown
    const dropdownLinks = dropdownMenu.querySelectorAll('a:not(.logout-link)');
    dropdownLinks.forEach(link => {
      link.addEventListener('click', () => {
        accountDropdown.classList.remove('open');
        accountBtn.setAttribute('aria-expanded', 'false');
        dropdownMenu.setAttribute('aria-hidden', 'true');
      });
    });
    
    // Logout functionaliteit
    if (logoutBtn) {
      logoutBtn.addEventListener('click', (e) => {
        e.preventDefault();
        // Simuleer uitloggen en navigeer naar login pagina
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = 'login.php';
      });
    }
    
    // Update huidige pagina markering
    this._updateCurrentPageHighlight();
  }
  
  _updateCurrentPageHighlight() {
    const currentPath = window.location.pathname.split('/').pop() || 'index.php';
    const dropdownLinks = this.shadowRoot.querySelectorAll('.dropdown-menu a');
    
    dropdownLinks.forEach(link => {
      const linkPath = link.getAttribute('href');
      link.removeAttribute('aria-current');
      
      if (linkPath && currentPath.includes(linkPath)) {
        link.setAttribute('aria-current', 'page');
      }
    });
  }
  
  disconnectedCallback() {
    // Cleanup event listeners
    const mobileMenuBtn = this.shadowRoot.querySelector('.mobile-menu-button');
    if (mobileMenuBtn) {
      mobileMenuBtn.removeEventListener('click', this._toggleMobileMenu);
    }
    
    // Remove scroll event listener
    window.removeEventListener('scroll', this._handleScroll);
    
    // Remove account dropdown event listeners
    document.removeEventListener('click', this._closeAccountDropdown);
  }
}

// Registreer de component
customElements.define('slimmer-navbar', SlimmerNavbar); 
