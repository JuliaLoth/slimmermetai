/**
 * Testimonials.js - Herbruikbare testimonials component met slider
 * 
 * Gebruik:
 * <slimmer-testimonials>
 *   <slimmer-testimonial
 *     name="Jan Jansen"
 *     role="Marketing Manager"
 *     image="images/testimonial-1.svg"
 *     text="Door Slimmer met AI heb ik mijn werkprocessen significant kunnen verbeteren."
 *   ></slimmer-testimonial>
 *   <!-- Meer testimonials... -->
 * </slimmer-testimonials>
 */

class SlimmerTestimonial extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    const name = this.getAttribute('name') || '';
    const role = this.getAttribute('role') || '';
    const image = this.getAttribute('image') || '';
    const text = this.textContent.trim();

    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
          padding: 2rem;
          background: white;
          border-radius: 1rem;
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
          transition: transform 0.3s ease;
        }

        :host:hover {
          transform: translateY(-5px);
        }

        .testimonial-content {
          display: flex;
          flex-direction: column;
          align-items: center;
          text-align: center;
        }

        .testimonial-image {
          width: 80px;
          height: 80px;
          border-radius: 50%;
          margin-bottom: 1rem;
          object-fit: cover;
        }

        .testimonial-text {
          font-size: 1.1rem;
          line-height: 1.6;
          color: #4b5563;
          margin-bottom: 1.5rem;
          font-style: italic;
        }

        .testimonial-author {
          display: flex;
          flex-direction: column;
          align-items: center;
        }

        .testimonial-name {
          font-weight: bold;
          color: #1f2937;
          margin-bottom: 0.25rem;
        }

        .testimonial-role {
          color: #6b7280;
          font-size: 0.9rem;
        }
      </style>

      <div class="testimonial-content">
        <img class="testimonial-image" src="${image}" alt="${name}">
        <p class="testimonial-text">${text}</p>
        <div class="testimonial-author">
          <span class="testimonial-name">${name}</span>
          <span class="testimonial-role">${role}</span>
        </div>
      </div>
    `;
  }
}

class SlimmerTestimonials extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
    this.currentSlide = 0;
    this.slides = [];
    this.autoPlayInterval = null;
  }

  connectedCallback() {
    this.render();
    this.setupSlider();
  }

  render() {
    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
          position: relative;
          padding: 2rem 0;
          overflow: hidden;
        }

        .testimonials-container {
          display: flex;
          transition: transform 0.5s ease;
          gap: 2rem;
        }

        .testimonial-slide {
          flex: 0 0 100%;
          max-width: 100%;
        }

        .slider-controls {
          display: flex;
          justify-content: center;
          gap: 1rem;
          margin-top: 2rem;
        }

        .slider-button {
          background: var(--primary-color, #5852f2);
          color: white;
          border: none;
          width: 40px;
          height: 40px;
          border-radius: 50%;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: background-color 0.3s ease;
        }

        .slider-button:hover {
          background: var(--accent-color, #db2777);
        }

        .slider-dots {
          display: flex;
          justify-content: center;
          gap: 0.5rem;
          margin-top: 1rem;
        }

        .slider-dot {
          width: 10px;
          height: 10px;
          border-radius: 50%;
          background: #e5e7eb;
          cursor: pointer;
          transition: background-color 0.3s ease;
        }

        .slider-dot.active {
          background: var(--primary-color, #5852f2);
        }

        @media (min-width: 768px) {
          .testimonial-slide {
            flex: 0 0 50%;
            max-width: 50%;
          }
        }

        @media (min-width: 1024px) {
          .testimonial-slide {
            flex: 0 0 33.333%;
            max-width: 33.333%;
          }
        }
      </style>

      <div class="testimonials-container">
        <slot></slot>
      </div>
      <div class="slider-controls">
        <button class="slider-button prev">←</button>
        <button class="slider-button next">→</button>
      </div>
      <div class="slider-dots"></div>
    `;
  }

  setupSlider() {
    const container = this.shadowRoot.querySelector('.testimonials-container');
    const slides = Array.from(this.children);
    const dotsContainer = this.shadowRoot.querySelector('.slider-dots');
    const prevButton = this.shadowRoot.querySelector('.prev');
    const nextButton = this.shadowRoot.querySelector('.next');

    // Voeg dots toe
    slides.forEach((_, index) => {
      const dot = document.createElement('div');
      dot.className = `slider-dot ${index === 0 ? 'active' : ''}`;
      dot.addEventListener('click', () => this.goToSlide(index));
      dotsContainer.appendChild(dot);
    });

    // Event listeners voor knoppen
    prevButton.addEventListener('click', () => this.prevSlide());
    nextButton.addEventListener('click', () => this.nextSlide());

    // Start autoplay
    this.startAutoPlay();

    // Pause autoplay bij hover
    container.addEventListener('mouseenter', () => this.stopAutoPlay());
    container.addEventListener('mouseleave', () => this.startAutoPlay());
  }

  updateSlider() {
    const container = this.shadowRoot.querySelector('.testimonials-container');
    const dots = this.shadowRoot.querySelectorAll('.slider-dot');
    
    container.style.transform = `translateX(-${this.currentSlide * 100}%)`;
    
    dots.forEach((dot, index) => {
      dot.classList.toggle('active', index === this.currentSlide);
    });
  }

  goToSlide(index) {
    this.currentSlide = index;
    this.updateSlider();
    this.resetAutoPlay();
  }

  nextSlide() {
    const slides = Array.from(this.children);
    this.currentSlide = (this.currentSlide + 1) % slides.length;
    this.updateSlider();
    this.resetAutoPlay();
  }

  prevSlide() {
    const slides = Array.from(this.children);
    this.currentSlide = (this.currentSlide - 1 + slides.length) % slides.length;
    this.updateSlider();
    this.resetAutoPlay();
  }

  startAutoPlay() {
    this.autoPlayInterval = setInterval(() => this.nextSlide(), 5000);
  }

  stopAutoPlay() {
    clearInterval(this.autoPlayInterval);
  }

  resetAutoPlay() {
    this.stopAutoPlay();
    this.startAutoPlay();
  }
}

customElements.define('slimmer-testimonial', SlimmerTestimonial);
customElements.define('slimmer-testimonials', SlimmerTestimonials); 