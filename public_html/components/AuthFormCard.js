class AuthFormCard extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
        this.render();
    }

    connectedCallback() {
        this._addEventListeners();
        // Standaard de eerste tab actief maken als er geen actieve is
        if (!this.shadowRoot.querySelector('.tab-btn.active')) {
            this.shadowRoot.querySelector('.tab-btn')?.classList.add('active');
            this.shadowRoot.querySelector('.tab-content')?.classList.add('active');
        }
    }

    _addEventListeners() {
        this.shadowRoot.querySelectorAll('.tab-btn').forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.getAttribute('data-tab');
                
                // Verwijder active class van alle knoppen en content
                this.shadowRoot.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                this.shadowRoot.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

                // Voeg active class toe aan geklikte knop
                button.classList.add('active');

                // Zoek en activeer de bijbehorende content via slot
                // We gaan ervan uit dat de geslotte content een ID heeft dat overeenkomt met data-tab
                const slottedContent = this.querySelector(`[slot="tab-content"][id="${tabId}"]`);
                if (slottedContent) {
                    // We kunnen de .active class niet direct aan de geslotte content toevoegen
                    // De PHP/HTML die de content levert moet zelf de .active class toevoegen/verwijderen
                    // Maar we kunnen wel een event sturen om de parent te informeren
                    this.dispatchEvent(new CustomEvent('tab-change', { detail: { tabId: tabId } }));
                    
                    // Simpele (minder ideale) manier: alle slots verbergen, dan de juiste tonen
                    this.querySelectorAll('[slot="tab-content"]').forEach(el => el.style.display = 'none');
                    slottedContent.style.display = 'block';
                    slottedContent.classList.add('active'); // Voeg active class toe voor animatie
                    
                } else {
                     // Zoek content binnen shadow DOM (als het geen slot is)
                    const internalContent = this.shadowRoot.querySelector(`.tab-content[id="${tabId}"]`);
                    if (internalContent) {
                         internalContent.classList.add('active');
                    }
                }
            });
        });
    }

    render() {
        this.shadowRoot.innerHTML = `
            <style>
                /* Stijlen specifiek voor AuthFormCard ZELF */
                :host { 
                    display: block; 
                }
                .auth-container { 
                    background-color: #fff;
                    border-radius: 12px;
                    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
                    padding: 3rem;
                    width: 100%;
                }
                .auth-tabs {
                    display: flex;
                    border-radius: 10px;
                    background-color: #f3f4f6;
                    padding: 0.5rem;
                    margin-bottom: 2rem;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
                }
                .tab-btn {
                    flex: 1;
                    background: none;
                    border: none;
                    padding: 1rem;
                    font-size: 1rem;
                    font-weight: 600;
                    color: #6b7280;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    border-radius: 8px;
                    margin: 0 0.25rem;
                    font-family: 'Glacial Indifference', sans-serif !important; 
                }
                .tab-btn:hover {
                    color: var(--primary-color, #5852f2);
                    background-color: rgba(88, 82, 242, 0.05);
                }
                .tab-btn.active {
                    background: linear-gradient(45deg, var(--primary-color, #5852f2), var(--primary-hover, #8e88ff));
                    color: white;
                    box-shadow: 0 4px 12px rgba(88, 82, 242, 0.2);
                }
                
                /* Responsive */
                @media (max-width: 768px) {
                    .auth-container {
                        padding: 1.5rem;
                    }
                    .tab-btn {
                         padding: 0.8rem;
                         font-size: 0.9rem;
                    }
                }
                 @media (max-width: 992px) {
                      .auth-container {
                         padding: 2rem;
                     }
                 }

            </style>
            <div class="auth-container">
                <div class="auth-tabs">
                    <slot name="tabs"></slot> 
                </div>
                <div class="tab-content-container">
                     <slot name="tab-content"></slot> 
                </div>
            </div>
        `;
    }
}

customElements.define('slimmer-auth-form-card', AuthFormCard); 