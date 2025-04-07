// Hoofdscript voor de Slimmer met AI website

// Check JS beschikbaarheid voor fallback
document.documentElement.classList.remove('no-js');
document.documentElement.classList.add('js');

// Direct na het laden van de pagina
document.addEventListener('DOMContentLoaded', function() {
    console.log('[main.js] DOMContentLoaded fired.');
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
    console.log('[main.js] Calling updateNavigation()...');
    updateNavigation();
    
    // Initialize mobile menu
    console.log('[main.js] Calling initializeMobileMenu()...');
    initializeMobileMenu();
    
    // Slider initialiseren
    console.log('[main.js] Calling initializeSlider()...');
    initializeSlider();
    
    // Voeg winkelwagen functionaliteit toe
    console.log('[main.js] Calling initializeCart()...');
    initializeCart();
    console.log('[main.js] Returned from initializeCart().');
    
    // Voeg sticky header toe
    console.log('[main.js] Calling initializeStickyHeader()...');
    initializeStickyHeader();
    
    // Tooltip voor de prijzen
    console.log('[main.js] Calling initializeTooltips()...');
    initializeTooltips();
    
    // Iframes handler toevoegen
    console.log('[main.js] Calling handleIframes()...');
    handleIframes();

    console.log('[main.js] DOMContentLoaded listener finished.');
});

// Update navigatie om huidige pagina te markeren
function updateNavigation() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    const navLinks = document.querySelectorAll('.nav-links a');
    
    navLinks.forEach(link => {
        const linkPage = link.getAttribute('href');
        
        // Reset alle links
        link.removeAttribute('aria-current');
        
        // Check voor index.html of / (homepage)
        if ((currentPage === 'index.php' || currentPage === '') && 
            (linkPage === 'index.php' || linkPage === '/')) {
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
    
    if (!menuButton || !navLinks) {
        console.log('Mobiel menu elementen niet gevonden. Mobiel menu wordt niet geïnitialiseerd.');
        return;
    }
    
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

// Initialiseren van de slider
function initializeSlider() {
    // Probeer verschillende mogelijke slider-container id's
    let sliderContent = document.getElementById('slider-content');
    
    // Als dat niet werkt, zoek in de custom-componenten
    if (!sliderContent) {
        const slimmerSlider = document.querySelector('slimmer-slider');
        if (slimmerSlider) {
            sliderContent = slimmerSlider.shadowRoot ? 
                slimmerSlider.shadowRoot.querySelector('.slider-content') : 
                slimmerSlider.querySelector('.slider-content');
            
            // Als nog steeds niet gevonden, stop de functie
            if (!sliderContent) {
                console.log('Slider content niet gevonden in slimmer-slider component. Slider wordt niet geïnitialiseerd.');
                return;
            }
        } else {
            console.log('Geen slider elementen gevonden. Slider wordt niet geïnitialiseerd.');
            return;
        }
    }
    
    // Try-catch toevoegen voor extra beveiliging
    try {
        const sliderItems = sliderContent.querySelectorAll('.slider-item');
        
        // Check of er slider items zijn
        if (!sliderItems || sliderItems.length === 0) {
            console.log('Geen slider items gevonden. Slider wordt niet geïnitialiseerd.');
            return;
        }
        
        const prevBtn = document.querySelector('.slider-btn.prev');
        const nextBtn = document.querySelector('.slider-btn.next');
        let currentSlide = 0;
        let slideInterval;

        function showSlide(index) {
            // Verwijder active class van alle slides
            sliderItems.forEach(item => {
                item.classList.remove('active');
            });

            // Voeg active class toe aan huidige slide
            sliderItems[index].classList.add('active');
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % sliderItems.length;
            showSlide(currentSlide);
        }

        function prevSlide() {
            currentSlide = (currentSlide - 1 + sliderItems.length) % sliderItems.length;
            showSlide(currentSlide);
        }

        function startAutoPlay() {
            slideInterval = setInterval(nextSlide, 5000); // Verander slide elke 5 seconden
        }

        function stopAutoPlay() {
            clearInterval(slideInterval);
        }

        // Event listeners voor knoppen
        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', () => {
                prevSlide();
                stopAutoPlay();
                startAutoPlay();
            });

            nextBtn.addEventListener('click', () => {
                nextSlide();
                stopAutoPlay();
                startAutoPlay();
            });

            // Pauzeer autoplay bij hover
            sliderContent.addEventListener('mouseenter', stopAutoPlay);
            sliderContent.addEventListener('mouseleave', startAutoPlay);
        }

        // Start autoplay
        startAutoPlay();
    } catch (error) {
        console.error('Fout bij het initialiseren van de slider:', error);
    }
}

// Initialiseren van de winkelwagen
function initializeCart() {
    console.log('[initializeCart] Function started.');
    // Controleer of het Cart object bestaat (gedefinieerd in cart.js)
    if (typeof Cart === 'undefined') {
        console.error('[initializeCart] Cart object IS UNDEFINED. Winkelwagen wordt niet geïnitialiseerd.');
        return;
    } else {
        console.log('[initializeCart] Cart object IS defined.');
    }
    
    try {
        console.log('[initializeCart] Entering try block, calling Cart.init()...');
        // Gebruik de init methode van het Cart object
        Cart.init();
        console.log('[initializeCart] Returned from Cart.init().');
        
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
    } catch (error) {
        console.error('[initializeCart] Fout bij initialiseren van winkelwagen:', error);
    }
    console.log('[initializeCart] Function finished.');
}

// Maak de header sticky bij scrollen
function initializeStickyHeader() {
    const header = document.querySelector('header');
    if (!header) {
        console.log('Header element niet gevonden. Sticky header wordt niet geïnitialiseerd.');
        return;
    }
    
    let lastScrollTop = 0;
    
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
    
    if (!tooltipElements || tooltipElements.length === 0) {
        console.log('Geen tooltip elementen gevonden. Tooltips worden niet geïnitialiseerd.');
        return;
    }
    
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
    
    if (!iframes || iframes.length === 0) {
        console.log('Geen iframes gevonden. Iframe handler wordt niet geïnitialiseerd.');
        return;
    }
    
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
