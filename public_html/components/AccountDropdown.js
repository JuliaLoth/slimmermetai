/**
 * AccountDropdown.js - Herbruikbare account dropdown component
 * Versie: 1.0.3 - Update 25 maart 2025
 * 
 * !!! LET OP: Deze component is nu direct geïntegreerd in de Navbar.js component !!!
 * !!! Dit bestand wordt behouden voor achterwaartse compatibiliteit, maar gebruik bij voorkeur de Navbar component !!!
 * 
 * Gebruik:
 * 1. Importeer het script: <script src="components/AccountDropdown.js"></script>
 * 2. Voeg de component toe aan je HTML:
 *    <slimmer-account-dropdown></slimmer-account-dropdown>
 * 
 * De component heeft de volgende configuraties:
 * - user-name: de naam van de gebruiker (standaard: "Account")
 */

class AccountDropdown extends HTMLElement {
  constructor() {
    super();
    
    // Maak shadow DOM
    this.attachShadow({ mode: 'open' });
    
    // Verkrijg attributen
    this.userName = this.getAttribute('user-name') || 'Account';
    
    // Render de component
    this.render();
    
    // Event listeners toevoegen na het renderen
    setTimeout(() => {
      this.addEventListeners();
    }, 0);
  }
  
  render() {
    this.shadowRoot.innerHTML = this.getTemplate();
  }
  
  getTemplate() {
    return `
        <style>
          :host {
            display: inline-block;
          }
          
          .account-dropdown {
            position: relative;
            z-index: 100;
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
          }
          
          .account-btn:hover {
            background-color: #e5e7eb;
          }

          .account-btn svg {
            opacity: 0.7;
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
        </style>
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
                <a href="#" class="logout-link" id="logout-btn">Uitloggen</a>
            </div>
        </div>
    `;
  }
  
  addEventListeners() {
    const accountDropdown = this.shadowRoot.querySelector('.account-dropdown');
    const accountBtn = this.shadowRoot.querySelector('.account-btn');
    const logoutBtn = this.shadowRoot.querySelector('#logout-btn');
    
    // Toggle dropdown bij klikken op button
    accountBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      accountDropdown.classList.toggle('open');
      
      // ARIA attributen bijwerken
      const isExpanded = accountDropdown.classList.contains('open');
      accountBtn.setAttribute('aria-expanded', isExpanded);
      this.shadowRoot.querySelector('.dropdown-menu').setAttribute('aria-hidden', !isExpanded);
    });
    
    // Sluit dropdown als ergens anders geklikt wordt
    document.addEventListener('click', () => {
      accountDropdown.classList.remove('open');
      accountBtn.setAttribute('aria-expanded', 'false');
      this.shadowRoot.querySelector('.dropdown-menu').setAttribute('aria-hidden', 'true');
    });
    
    // Voorkom sluiten bij klikken binnen dropdown
    accountDropdown.addEventListener('click', (e) => {
      e.stopPropagation();
    });
    
    // Sluit dropdown bij klikken op een link in de dropdown
    const dropdownLinks = this.shadowRoot.querySelectorAll('.dropdown-menu a');
    dropdownLinks.forEach(link => {
      link.addEventListener('click', () => {
        accountDropdown.classList.remove('open');
        accountBtn.setAttribute('aria-expanded', 'false');
        this.shadowRoot.querySelector('.dropdown-menu').setAttribute('aria-hidden', 'true');
      });
    });
    
    // Logout functionaliteit
    logoutBtn.addEventListener('click', (e) => {
      e.preventDefault();
      // Simuleer uitloggen en navigeer naar login pagina
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = 'login.php';
    });
    
    // Update huidige pagina markering
    this.updateCurrentPageHighlight();
  }
  
  updateCurrentPageHighlight() {
    const currentPath = window.location.pathname.split('/').pop() || 'index.php';
    const links = this.shadowRoot.querySelectorAll('.dropdown-menu a');
    
    links.forEach(link => {
      const linkPath = link.getAttribute('href');
      link.removeAttribute('aria-current');
      
      if (linkPath && currentPath.includes(linkPath)) {
        link.setAttribute('aria-current', 'page');
      }
    });
  }
  
  // Attributen observeren voor wijzigingen
  static get observedAttributes() {
    return ['user-name'];
  }
  
  // Reageren op attribuut wijzigingen
  attributeChangedCallback(name, oldValue, newValue) {
    if (name === 'user-name' && oldValue !== newValue) {
      this.userName = newValue;
      this.render();
      
      // Nodig om event listeners opnieuw toe te voegen na herrenderen
      setTimeout(() => {
        this.addEventListeners();
      }, 0);
    }
  }
}

// Registreer de component
customElements.define('slimmer-account-dropdown', AccountDropdown); 
