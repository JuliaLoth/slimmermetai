// Hoofdscript voor de Slimmer met AI website

// Check JS beschikbaarheid voor fallback
document.documentElement.classList.remove('no-js');
document.documentElement.classList.add('js');

// Direct na het laden van de pagina
document.addEventListener('DOMContentLoaded', function() {
    // Preloader (verwijder de preloader direct)
    let preloader = document.querySelector('.preloader');
    if (preloader) {
        preloader.classList.add('fade-out');
        setTimeout(() => {
            preloader.style.display = 'none';
        }, 300);
    }
    
    // Direct alle elementen zichtbaar maken voor betere gebruikerservaring
    document.querySelectorAll('.card, .feature-card, .testimonial-card').forEach(element => {
        setTimeout(() => {
            element.classList.add('visible');
            element.style.opacity = "1";
            element.style.transform = "translateY(0)";
        }, 100);
    });
    
    // Hero content direct zichtbaar maken
    const heroContent = document.querySelector('.hero-content');
    if (heroContent) {
        heroContent.classList.add('animated');
        heroContent.style.opacity = "1";
        heroContent.style.transform = "translateY(0)";
    }
    
    // Zorg ervoor dat de navigatiebalk correct werkt
    updateNavigation();
    
    // Initialize mobile menu
    initializeMobileMenu();
    
    // Slider initialiseren
    initializeSlider();
    
    // Voeg winkelwagen functionaliteit toe
    initializeCart();
    
    // Voeg sticky header toe
    initializeStickyHeader();
    
    // Tooltip voor de prijzen
    initializeTooltips();
    
    // Iframes handler toevoegen
    handleIframes();
});

// Update navigatie om huidige pagina te markeren
function updateNavigation() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const navLinks = document.querySelectorAll('.nav-links a');
    
    navLinks.forEach(link => {
        const linkPage = link.getAttribute('href');
        
        // Reset alle links
        link.removeAttribute('aria-current');
        
        // Check voor index.html of / (homepage)
        if ((currentPage === 'index.html' || currentPage === '') && 
            (linkPage === 'index.html' || linkPage === '/')) {
            link.setAttribute('aria-current', 'page');
        }
        // Check voor andere pagina's
        else if (linkPage && currentPage.includes(linkPage)) {
            link.setAttribute('aria-current', 'page');
        }
    });
}

// Initialiseer het mobiele menu
function initializeMobileMenu() {
    const menuButton = document.querySelector('.mobile-menu-button');
    const navLinks = document.querySelector('.nav-links');
    
    if (menuButton && navLinks) {
        menuButton.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            this.classList.toggle('active');
            
            if (navLinks.classList.contains('active')) {
                this.setAttribute('aria-label', 'Menu sluiten');
            } else {
                this.setAttribute('aria-label', 'Menu openen');
            }
        });
        
        // Sluit menu bij klikken op een link
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
                menuButton.classList.remove('active');
                menuButton.setAttribute('aria-label', 'Menu openen');
            });
        });
    }
}

// Initialiseren van de slider
function initializeSlider() {
    const sliderContent = document.getElementById('slider-content');
    if (!sliderContent) return;
    
    const slides = sliderContent.querySelectorAll('.slider-item');
    const prevBtn = document.querySelector('.slider-btn.prev');
    const nextBtn = document.querySelector('.slider-btn.next');
    const sliderControls = document.querySelector('.slider-controls');
    
    if (slides.length === 0) return;
    
    let currentIndex = 0;
    let isAnimating = false; // Voorkom dubbele animaties
    
    // Eerste slide initieel zichtbaar maken
    slides[0].style.display = 'flex';
    slides[0].classList.add('active');
    
    // Zorg ervoor dat de control buttons altijd klikbaar zijn
    if (sliderControls) {
        sliderControls.style.position = 'relative';
        sliderControls.style.zIndex = '50';
    }
    
    // Functie om actieve slide te updaten
    function updateSlider(direction) {
        if (isAnimating) return;
        isAnimating = true;
        
        // Huidige slide exit-animatie geven
        const currentSlide = slides[currentIndex];
        currentSlide.classList.add('slide-exit');
        
        setTimeout(() => {
            // Reset alle slides
            slides.forEach((slide, index) => {
                slide.style.display = 'none';
                slide.classList.remove('active');
                slide.classList.remove('slide-exit');
                slide.style.pointerEvents = 'none';
            });
            
            // Nieuwe slide voorbereiden
            slides[currentIndex].style.display = 'flex';
            
            // Kleine vertraging voor visueel effect
            setTimeout(() => {
                slides[currentIndex].classList.add('active');
                slides[currentIndex].style.pointerEvents = 'auto';
                
                // Zorg ervoor dat buttons in de slide altijd klikbaar zijn
                const slideButtons = slides[currentIndex].querySelectorAll('.btn');
                slideButtons.forEach(btn => {
                    btn.style.position = 'relative';
                    btn.style.zIndex = '30';
                });
                
                isAnimating = false;
            }, 50);
        }, 500); // Half seconde wachten op exit-animatie
    }
    
    // Volgende slide tonen
    function nextSlide() {
        if (isAnimating) return;
        
        currentIndex++;
        if (currentIndex >= slides.length) {
            currentIndex = 0;
        }
        updateSlider('next');
    }
    
    // Vorige slide tonen
    function prevSlide() {
        if (isAnimating) return;
        
        currentIndex--;
        if (currentIndex < 0) {
            currentIndex = slides.length - 1;
        }
        updateSlider('prev');
    }
    
    // Event listeners voor de knoppen
    if (prevBtn) prevBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Voorkom dat de event doorgaat naar onderliggende elementen
        prevSlide();
    });
    
    if (nextBtn) nextBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Voorkom dat de event doorgaat naar onderliggende elementen
        nextSlide();
    });
    
    // Initiële update aanroepen om ervoor te zorgen dat alles correct is ingesteld
    slides.forEach((slide, index) => {
        if (index !== currentIndex) {
            slide.style.display = 'none';
            slide.classList.remove('active');
            slide.style.pointerEvents = 'none';
        }
    });
    
    // Automatisch wisselen van slides
    let sliderInterval = setInterval(nextSlide, 6000); // Iets langer door de animaties
    
    // Pauzeer automatisch wisselen bij hover
    if (sliderContent) {
        sliderContent.addEventListener('mouseenter', function() {
            clearInterval(sliderInterval);
        });
        
        sliderContent.addEventListener('mouseleave', function() {
            sliderInterval = setInterval(nextSlide, 6000);
        });
    }
}

// Initialiseren van de winkelwagen
function initializeCart() {
    // Controleer of het Cart object bestaat (gedefinieerd in cart.js)
    if (typeof Cart !== 'undefined') {
        // NIET meer de winkelwagen leegmaken bij initialisatie
        // localStorage.removeItem('slimmerAICart');
        // localStorage.removeItem('cart');
        
        // Gebruik de init methode van het Cart object
        Cart.init();
        
        // NIET meer de winkelwagen resetten
        // Cart.resetCart(false);
        
        // Zorg dat het winkelwagenicoontje direct goed wordt bijgewerkt
        if (typeof Cart.renderCartCount === 'function') {
            Cart.renderCartCount();
        }
        
        // Extra check om te zorgen dat het aantal items correct wordt weergegeven
        setTimeout(() => {
            if (typeof Cart !== 'undefined' && typeof Cart.renderCartCount === 'function') {
                Cart.renderCartCount();
            }
        }, 500);
    } else {
        console.error('Cart object niet gevonden. Zorg ervoor dat cart.js voor main.js wordt geladen.');
    }
}

// Maak de header sticky bij scrollen
function initializeStickyHeader() {
    const header = document.querySelector('header');
    let lastScrollTop = 0;
    
    if (!header) return;
    
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > 100) {
            header.classList.add('sticky');
        } else {
            header.classList.remove('sticky');
        }
        
        lastScrollTop = scrollTop;
    });
}

// Initialiseer tooltips
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltipText = this.dataset.tooltip;
            
            const tooltip = document.createElement('div');
            tooltip.classList.add('tooltip');
            tooltip.textContent = tooltipText;
            
            document.body.appendChild(tooltip);
            
            const elementRect = this.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            
            tooltip.style.top = `${elementRect.top - tooltipRect.height - 10}px`;
            tooltip.style.left = `${elementRect.left + (elementRect.width / 2) - (tooltipRect.width / 2)}px`;
            
            tooltip.classList.add('show');
            
            this.addEventListener('mouseleave', function tooltipRemove() {
                this.removeEventListener('mouseleave', tooltipRemove);
                tooltip.remove();
            });
        });
    });
}

// Toon een notificatie
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.classList.add('notification', `notification-${type}`);
    
    const notificationContent = document.createElement('div');
    notificationContent.classList.add('notification-content');
    notificationContent.textContent = message;
    
    const closeButton = document.createElement('button');
    closeButton.classList.add('notification-close');
    closeButton.innerHTML = '&times;';
    closeButton.setAttribute('aria-label', 'Notificatie sluiten');
    
    closeButton.addEventListener('click', function() {
        closeNotification(notification);
    });
    
    notification.appendChild(notificationContent);
    notification.appendChild(closeButton);
    
    document.body.appendChild(notification);
    
    // Geef de browser tijd om de notificatie te renderen voordat animatie start
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Sluit automatisch na duration
    setTimeout(() => {
        closeNotification(notification);
    }, duration);
    
    return notification;
}

// Sluit een notificatie
function closeNotification(notification) {
    notification.classList.remove('show');
    notification.classList.add('hide');
    
    notification.addEventListener('transitionend', function() {
        notification.remove();
    });
}

// Functie om iframes te controleren en fallback te tonen indien nodig
function handleIframes() {
    const iframes = document.querySelectorAll('iframe');
    
    iframes.forEach(iframe => {
        // Stel een timeout in voor als de iframe niet binnen 5 seconden laadt
        const timeout = setTimeout(() => {
            if (!iframe.dataset.loaded) {
                // Als de iframe niet geladen is, zorg ervoor dat de fallback zichtbaar is
                const fallbackContainer = iframe.nextElementSibling;
                if (fallbackContainer && fallbackContainer.classList.contains('iframe-fallback')) {
                    fallbackContainer.style.display = 'block';
                }
            }
        }, 5000);
        
        // Als de iframe laadt, markeer deze als geladen
        iframe.addEventListener('load', function() {
            iframe.dataset.loaded = 'true';
            clearTimeout(timeout);
        });
        
        // Als er een error optreedt, toon de fallback
        iframe.addEventListener('error', function() {
            const fallbackContainer = iframe.nextElementSibling;
            if (fallbackContainer && fallbackContainer.classList.contains('iframe-fallback')) {
                fallbackContainer.style.display = 'block';
            }
        });
    });
} 