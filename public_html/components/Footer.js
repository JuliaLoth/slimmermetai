/**
 * Footer.js - Herbruikbare footer component
 * 
 * Gebruik:
 * 1. Importeer het script: <script src="components/Footer.js"></script>
 * 2. Gebruik de component in je HTML:
 *    <slimmer-footer></slimmer-footer>
 */

class SlimmerFooter extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    this.render();
  }

  render() {
    const currentYear = new Date().getFullYear();
    console.log('>>> TESTLOG: Rendering SlimmerFooter component met versie check!');

    this.shadowRoot.innerHTML = `
      <style>
        /* :host en variabelen */
        :host {
          display: block;
          /* Definieer CSS variabelen voor consistentie, fallback naar standaardkleuren indien niet gedefinieerd */
          --footer-background-color: var(--global-background-light, #f8f9fa);
          --footer-text-color: var(--global-text-secondary, #6b7280);
          --footer-heading-color: var(--global-text-primary, #1f2937);
          --footer-link-color: var(--global-text-secondary, #6b7280);
          --footer-link-hover-color: var(--primary-color, #5852f2);
          --footer-border-color: var(--global-border-light, #e5e7eb);
          --primary-color: #5852f2; /* Behoud primaire kleur voor zekerheid */
          --accent-color: #db2777; /* Behoud accentkleur voor zekerheid */
          font-family: 'Inter', sans-serif; /* Standaard font */
        }

        /* Algemene footer styling */
        footer {
          background-color: var(--footer-background-color);
          color: var(--footer-text-color);
          padding: 3rem 0 1.5rem; /* Meer padding boven, minder onder */
          margin-top: auto; /* Zorgt dat footer onderaan blijft bij weinig content */
          border-top: 1px solid var(--footer-border-color);
          font-size: 0.95rem;
          line-height: 1.6;
        }

        .container {
          max-width: 1150px;
          padding: 0 1.5rem; /* Iets meer padding horizontaal */
          margin: 0 auto;
        }

        /* Grid layout voor content */
        .footer-content {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
          gap: 2rem 1.5rem; /* Row en column gap */
          margin-bottom: 2.5rem; /* Ruimte voor de bottom sectie */
        }

        .footer-column {
          display: flex;
          flex-direction: column;
        }

        /* Eerste kolom (logo en tekst) */
        .footer-logo {
          display: flex;
          align-items: center;
          margin-bottom: 1rem; /* Iets minder marge */
        }

        .footer-logo img {
          margin-right: 0.75rem; /* Iets minder marge */
          width: 28px; /* Iets kleiner logo */
          height: auto;
        }

        .footer-logo .logo-text {
          font-size: 1.1rem; /* Iets groter */
          font-weight: 700; /* Bold */
          color: var(--footer-heading-color);
          font-family: 'Glacial Indifference', sans-serif;
        }

        .footer-text {
          color: var(--footer-text-color);
          margin-bottom: 1rem; /* Iets minder marge */
          font-size: 0.95rem;
          line-height: 1.6;
        }

        /* Kolom titels */
        .footer-column h4 {
          font-family: 'Glacial Indifference', sans-serif;
          font-weight: bold;
          font-size: 1.05rem; /* Iets kleiner dan logo tekst */
          margin-top: 0;
          margin-bottom: 1rem; /* Consistentie */
          color: var(--footer-heading-color);
        }

        /* Lijsten met links */
        .footer-links {
          list-style: none;
          padding: 0;
          margin: 0;
        }

        .footer-links li {
          margin-bottom: 0.6rem; /* Minder ruimte tussen links */
        }

        .footer-links a {
          color: var(--footer-link-color);
          text-decoration: none;
          transition: color 0.2s ease;
          font-family: 'Inter', sans-serif; /* Gebruik standaard font voor links */
          font-size: 0.95rem;
        }

        .footer-links a:hover {
          color: var(--footer-link-hover-color);
          text-decoration: underline;
        }

        /* Footer bottom sectie (copyright en social links) */
        .footer-bottom {
          margin-top: 2.5rem; /* Iets minder marge */
          padding-top: 1.5rem;
          border-top: 1px solid var(--footer-border-color);
          display: flex;
          justify-content: space-between;
          align-items: center;
          flex-wrap: wrap;
          gap: 1rem;
        }

        .footer-bottom p { /* Copyright tekst */
          color: var(--footer-text-color);
          font-size: 0.9rem;
          margin: 0;
        }

        /* Social media links */
        .social-links {
          display: flex;
          gap: 0.8rem; /* Iets minder ruimte */
        }

        .social-links a {
          display: flex;
          align-items: center;
          justify-content: center;
          width: 32px; /* Iets kleiner */
          height: 32px;
          border-radius: 50%;
          background-color: #e5e7eb; /* Lichtere achtergrond */
          color: var(--footer-text-color);
          transition: all 0.2s ease;
        }
        
        .social-links a svg {
             width: 18px; /* Kleinere SVG */
             height: 18px;
        }


        .social-links a:hover {
          background-color: var(--footer-link-hover-color);
          color: white;
          transform: translateY(-2px); /* Subtielere hover animatie */
        }

        /* Responsive aanpassingen */
        @media (max-width: 768px) {
          footer {
            padding: 2.5rem 0 1.5rem;
          }
          .footer-content {
            grid-template-columns: 1fr; /* Stapel kolommen */
            gap: 1.5rem;
             margin-bottom: 2rem;
          }
           .footer-column {
             align-items: center; /* Centreer inhoud in de kolom */
             text-align: center;
           }
           .footer-logo {
             justify-content: center; /* Centreer logo */
           }
          .footer-bottom {
            flex-direction: column; /* Stapel copyright en social links */
             gap: 1.2rem;
          }
        }
      </style>
      
      <footer>
        <div class="container">
          <div class="footer-content">
            <!-- Kolom 1: Logo en beschrijving -->
            <div class="footer-column">
              <div class="footer-logo">
                <!-- Zorg dat het pad naar Logo.svg klopt vanuit de public_html map -->
                <img src="images/Logo.svg" alt="Slimmer met AI logo">
                <span class="logo-text">Slimmer met AI</span>
              </div>
              <p class="footer-text">Praktische AI-tools en e-learnings voor Nederlandse professionals.</p>
            </div>
            
            <!-- Kolom 2: Navigatie (Combineer Tools & Cursussen?) -->
             <div class="footer-column">
              <h4>Ontdek</h4>
              <ul class="footer-links">
                <li><a href="/index.php">Home</a></li>
                <li><a href="/tools.php">Tools</a></li>
                <li><a href="/e-learnings.php">E-learnings</a></li>
                <li><a href="/nieuws.php">Nieuws</a></li>
              </ul>
            </div>

             <!-- Kolom 3: Bedrijf & Account -->
            <div class="footer-column">
              <h4>Info</h4>
              <ul class="footer-links">
                <li><a href="/over-mij.php">Over Mij</a></li>
                <!-- Voeg eventueel link naar contactpagina toe -->
                <!-- Account links (optioneel in footer?) -->
                 <li><a href="/login.php">Inloggen</a></li>
                 <li><a href="/register.php">Registreren</a></li>
              </ul>
            </div>

             <!-- Kolom 4: Juridisch -->
             <div class="footer-column">
               <h4>Legal</h4>
               <ul class="footer-links">
                 <li><a href="/privacybeleid.php">Privacybeleid</a></li>
                 <li><a href="/algemene-voorwaarden.php">Algemene Voorwaarden</a></li>
                 <li><a href="/cookies.php">Cookiebeleid</a></li>
               </ul>
             </div>

          </div>
          
          <div class="footer-bottom">
            <p>&copy; ${currentYear} Slimmer met AI</p>
            <div class="social-links">
              <a href="https://nl.linkedin.com/in/julialoth" aria-label="LinkedIn" target="_blank" rel="noopener noreferrer">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path>
                  <rect x="2" y="9" width="4" height="12"></rect>
                  <circle cx="4" cy="4" r="2"></circle>
                </svg>
              </a>
              <!-- Vervang Twitter/X logo met Mastodon logo -->
              <a href="#" aria-label="Mastodon" target="_blank" rel="noopener noreferrer">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <!-- Correct Mastodon SVG path -->
                  <path d="M23.192 7.875c-.32-2.428-2.5-4.19-5.14-4.19h-8.04c-2.64 0-4.81 1.76-5.14 4.19-.04.23-.06.46-.06.7v6.85c0 .24.02.47.06.7.32 2.43 2.5 4.19 5.14 4.19h8.05c2.64 0 4.81-1.76 5.14-4.19.04-.23.06-.46.06-.7V8.575c0-.24-.02-.47-.06-.7zm-3.96 8.05c-.4.23-.83.39-1.29.48-.45.09-.92.14-1.4.14-.48 0-.95-.05-1.4-.14-.47-.09-.9-.25-1.29-.48V7.275h2.13v5.66c0 .5.21.74.63.74s.63-.25.63-.74V7.275h2.13v8.65zm-8.14 0c-.4.23-.83.39-1.29.48-.45.09-.92.14-1.4.14-.48 0-.95-.05-1.4-.14-.47-.09-.9-.25-1.29-.48V7.275h2.13v5.66c0 .5.21.74.63.74s.63-.25.63-.74V7.275h2.13v8.65z"/>
                </svg>
              </a>
            </div>
          </div>
        </div>
      </footer>
    `;
  }
}

customElements.define('slimmer-footer', SlimmerFooter); 
