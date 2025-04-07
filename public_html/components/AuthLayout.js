class AuthLayout extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
        this.render(); // Roep render direct aan
    }

    render() {
        // Render functie voor AuthLayout zelf (met layout stijlen)
        this.shadowRoot.innerHTML = `
            <style>
                /* Stijlen specifiek voor AuthLayout component ZELF */
                .auth-section {
                    padding: 4rem 0;
                    background-color: var(--body-bg-color, #f9fafb);
                    margin-top: 80px; 
                }
                .container { 
                    max-width: 1200px;
                    padding: 0 20px;
                    margin: 0 auto;
                 }
                .auth-row {
                    display: grid;
                    grid-template-columns: 3fr 2fr;
                    gap: 2rem;
                    align-items: flex-start;
                 }
                @media (max-width: 992px) { 
                    .auth-row {
                        grid-template-columns: 1fr;
                        gap: 3rem; 
                    }
                     .auth-section {
                         padding: 3rem 0;
                     }
                }
                 @media (max-width: 768px) { 
                     .auth-section {
                         padding: 2rem 0;
                     }
                     .auth-row {
                         gap: 2rem;
                     }
                 }
            </style>
            <section class="auth-section">
                <div class="container">
                    <div class="auth-row">
                        <div class="form-column">
                            <slot name="form-card"></slot>
                        </div>
                        <div class="info-column">
                            <slot name="info-card"></slot>
                        </div>
                    </div>
                </div>
            </section>
        `;
    }
}

customElements.define('slimmer-auth-layout', AuthLayout); 