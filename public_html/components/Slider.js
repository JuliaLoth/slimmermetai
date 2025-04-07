class SlimmerSlider extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
        this.currentSlide = 0;
        this.slides = [];
        this.autoPlayInterval = null;
        this.isHovered = false;
    }

    connectedCallback() {
        this.render();
        this.initializeSlider();
        this.setupEventListeners();
        this.startAutoPlay();
    }

    render() {
        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: block;
                    width: 100%;
                    margin: 0 auto;
                }

                .slider-gradient-background {
                    background: linear-gradient(135deg, rgba(88, 82, 242, 0.05) 0%, rgba(219, 39, 119, 0.05) 100%);
                    border-radius: 15px;
                    padding: 2rem;
                    position: relative;
                    overflow: hidden;
                }

                .container {
                    max-width: 1200px;
                    margin: 0 auto;
                    padding: 0 1rem;
                }

                .slider-content {
                    position: relative;
                    min-height: 300px;
                    overflow: hidden;
                }

                .slider-item {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    padding: 2rem;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    text-align: center;
                    opacity: 0;
                    transform: translateX(-50px);
                    transition: opacity 0.7s ease-in-out, transform 0.7s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                    pointer-events: none;
                }

                .slider-item.active {
                    opacity: 1;
                    transform: translateX(0);
                    pointer-events: all;
                }

                .slider-item.slide-exit {
                    opacity: 0;
                    transform: translateX(50px);
                    transition: opacity 0.7s ease-in-out, transform 0.7s cubic-bezier(0.6, -0.28, 0.735, 0.045);
                }

                .slider-item h3 {
                    font-size: 2.5rem;
                    margin: 0 0 1rem 0;
                    color: var(--text-color);
                    font-family: 'Glacial Indifference', sans-serif;
                    font-weight: bold;
                }

                .slider-item p {
                    font-size: 1.1rem;
                    line-height: 1.6;
                    color: var(--text-color);
                    margin: 0 0 2rem 0;
                    max-width: 600px;
                }

                .slider-item .btn {
                    display: inline-block;
                    padding: 0.8rem 1.5rem;
                    border: 2px solid var(--primary-color);
                    border-radius: 8px;
                    color: var(--primary-color);
                    text-decoration: none;
                    font-weight: 500;
                    transition: all 0.3s ease;
                }

                .slider-item .btn:hover {
                    background: var(--primary-color);
                    color: white;
                }

                .slider-controls {
                    display: flex;
                    justify-content: center;
                    gap: 1rem;
                    margin-top: 2rem;
                }

                .slider-btn {
                    background: white;
                    border: none;
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    font-size: 1.5rem;
                    cursor: pointer;
                    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .slider-btn:hover {
                    background: var(--primary-color);
                    color: white;
                    transform: translateY(-2px);
                    box-shadow: 0 6px 15px rgba(88, 82, 242, 0.25);
                }

                @media (max-width: 768px) {
                    .slider-item h3 {
                        font-size: 2rem;
                    }

                    .slider-item p {
                        font-size: 1rem;
                    }

                    .slider-btn {
                        width: 40px;
                        height: 40px;
                        font-size: 1.2rem;
                    }
                }
            </style>
            <div class="slider-gradient-background">
                <div class="container">
                    <div class="slider-content" id="slider-content">
                        <div class="slider-item active">
                            <h3>Email Assistent Plus</h3>
                            <p>Onze meest geavanceerde email assistent met ondersteuning voor meerdere talen en integratie met populaire email-clients.</p>
                            <a href="tools.php#email-tool" class="btn">Meer Informatie</a>
                        </div>
                        <div class="slider-item">
                            <h3>Document Analyzer 2.0</h3>
                            <p>Analyseer contracten en juridische documenten met AI en krijg direct de belangrijkste punten uitgelicht.</p>
                            <a href="tools.php#document-tool" class="btn">Meer Informatie</a>
                        </div>
                        <div class="slider-item">
                            <h3>Content Generator Suite</h3>
                            <p>Genereer hoogwaardige content voor je website, blog of social media in seconden.</p>
                            <a href="tools.php#content-tool" class="btn">Meer Informatie</a>
                        </div>
                    </div>
                    <div class="slider-controls">
                        <button class="slider-btn prev" aria-label="Vorige slide">&larr;</button>
                        <button class="slider-btn next" aria-label="Volgende slide">&rarr;</button>
                    </div>
                </div>
            </div>
        `;
    }

    setupEventListeners() {
        const prevBtn = this.shadowRoot.querySelector('.prev');
        const nextBtn = this.shadowRoot.querySelector('.next');

        prevBtn.addEventListener('click', () => this.prevSlide());
        nextBtn.addEventListener('click', () => this.nextSlide());

        this.addEventListener('mouseenter', () => {
            this.isHovered = true;
            this.pauseAutoPlay();
        });

        this.addEventListener('mouseleave', () => {
            this.isHovered = false;
            this.startAutoPlay();
        });
    }

    initializeSlider() {
        this.slides = Array.from(this.shadowRoot.querySelectorAll('.slider-item'));
    }

    updateSlider() {
        this.slides.forEach((slide, index) => {
            if (index === this.currentSlide) {
                slide.classList.remove('slide-exit');
                slide.classList.add('active');
            } else {
                if (slide.classList.contains('active')) {
                    slide.classList.add('slide-exit');
                }
                slide.classList.remove('active');
            }
        });
    }

    nextSlide() {
        this.currentSlide = (this.currentSlide + 1) % this.slides.length;
        this.updateSlider();
    }

    prevSlide() {
        this.currentSlide = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
        this.updateSlider();
    }

    startAutoPlay() {
        if (!this.autoPlayInterval) {
            this.autoPlayInterval = setInterval(() => {
                if (!this.isHovered) {
                    this.nextSlide();
                }
            }, 5000);
        }
    }

    pauseAutoPlay() {
        if (this.autoPlayInterval) {
            clearInterval(this.autoPlayInterval);
            this.autoPlayInterval = null;
        }
    }

    disconnectedCallback() {
        this.pauseAutoPlay();
    }
}

customElements.define('slimmer-slider', SlimmerSlider);
