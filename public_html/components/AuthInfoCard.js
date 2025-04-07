class AuthInfoCard extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
        this.render();
    }

    render() {
        this.shadowRoot.innerHTML = `
            <style>
                /* Stijlen specifiek voor AuthInfoCard */
                :host { 
                    display: block; /* Zorg dat het component ruimte inneemt */
                    position: sticky; /* Maak sticky zodat het meescrollt */
                    top: 100px; /* Afstand van bovenkant (pas aan nav hoogte navbar) */
                }
                
                .auth-info-card {
                    background-color: #f9fafb;
                    border-radius: 12px;
                    padding: 2.5rem;
                    height: auto;
                    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
                }

                h3 {
                    color: #1f2937;
                    margin-bottom: 2rem;
                    font-size: 1.4rem !important;
                    position: relative;
                    font-weight: bold !important;
                    font-family: 'Glacial Indifference', sans-serif !important;
                }
                h3::after { /* Onderstreping titel */
                    content: "";
                    position: absolute;
                    bottom: -0.75rem;
                    left: 0;
                    width: 3rem;
                    height: 3px;
                    background: linear-gradient(45deg, var(--primary-color, #5852f2), var(--primary-hover, #8e88ff));
                    border-radius: 3px;
                }

                .benefits-list {
                    list-style: none;
                    padding: 0;
                    margin: 0;
                }
                
                 /* Responsive */
                 @media (max-width: 992px) {
                    :host {
                        position: static; /* Verwijder sticky op mobiel */
                        top: auto;
                    }
                    .auth-info-card {
                        padding: 2rem;
                    }
                 }
                  @media (max-width: 768px) {
                     .auth-info-card {
                        padding: 1.5rem;
                     }
                     h3 {
                        font-size: 1.3rem !important;
                     }
                 }

            </style>
            <div class="auth-info-card">
                 <h3><slot name="title">Jouw voordelen</slot></h3> 
                 <ul class="benefits-list">
                     <slot name="benefits"></slot> 
                 </ul>
            </div>
        `;
    }
}

customElements.define('slimmer-auth-info-card', AuthInfoCard); 