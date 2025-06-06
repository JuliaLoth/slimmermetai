<?php
/**
 * UNIFIED HEADER - SlimmerMetAI
 * 
 * Dit is de enige header implementatie die door alle pagina's gebruikt wordt.
 * Vervangt alle legacy navbar implementaties voor consistentie.
 */

// Helper functie voor actieve pagina detectie
$current_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$current_page = $current_path ?: 'home';

function isActivePage($page_id, $current_page) {
    if ($page_id === 'home' && ($current_page === '' || $current_page === 'home')) {
        return true;
    }
    return str_contains($current_page, $page_id);
}
?>

<header role="banner" class="unified-header">
    <div class="container">
        <nav class="navbar" role="navigation" aria-label="Hoofdnavigatie">
            <!-- Logo -->
            <div class="logo">
                <a href="/" aria-label="SlimmerMetAI Homepage">
                    <img src="/images/Logo.svg" alt="Slimmer met AI logo" width="55" height="55">
                    <span class="logo-text">Slimmer met AI</span>
                </a>
            </div>
            
            <!-- Mobile menu button -->
            <button class="mobile-menu-button" aria-label="Menu openen" aria-expanded="false" aria-controls="nav-links">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
            
            <!-- Navigation links -->
            <div class="nav-links" id="nav-links" role="menubar">
                <a href="/" 
                   role="menuitem" 
                   <?= isActivePage('home', $current_page) ? 'aria-current="page"' : '' ?>>
                   Home
                </a>
                <a href="/tools" 
                   role="menuitem"
                   <?= isActivePage('tools', $current_page) ? 'aria-current="page"' : '' ?>>
                   Tools
                </a>
                <a href="/ai-cursussen" 
                   role="menuitem"
                   <?= (isActivePage('e-learnings', $current_page) || isActivePage('ai-cursussen', $current_page)) ? 'aria-current="page"' : '' ?>>
                   AI Cursussen
                </a>
                <a href="/over-mij" 
                   role="menuitem"
                   <?= isActivePage('over-mij', $current_page) ? 'aria-current="page"' : '' ?>>
                   Over Mij
                </a>
                <a href="/nieuws" 
                   role="menuitem"
                   <?= isActivePage('nieuws', $current_page) ? 'aria-current="page"' : '' ?>>
                   Nieuws
                </a>
            </div>
            
            <!-- Auth & Cart section -->
            <div class="auth-section">
                <div class="auth-buttons">
                    <!-- Account button -->
                    <a href="/login" class="account-btn" aria-label="Account beheer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span class="account-text">Account</span>
                    </a>
                    
                    <!-- Cart button with unified styling -->
                    <a href="/winkelwagen" class="cart-button" aria-label="Winkelwagen bekijken">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="m1 1 4 4 14 1-1 3H6"></path>
                        </svg>
                        <span class="cart-count unified-cart-count" id="unified-cart-count" aria-live="polite">0</span>
                    </a>
                </div>
            </div>
        </nav>
    </div>
    
    <!-- Account dropdown voor ingelogde users -->
    <div class="account-dropdown" id="account-dropdown" style="display: none;" role="menu">
        <div class="dropdown-content">
            <a href="/dashboard" role="menuitem">Dashboard</a>
            <a href="/profiel" role="menuitem">Mijn profiel</a>
            <a href="/mijn-tools" role="menuitem">Mijn tools</a>
            <a href="/mijn-cursussen" role="menuitem">Mijn cursussen</a>
            <a href="#" id="logout-btn" class="logout-link" role="menuitem">Uitloggen</a>
        </div>
    </div>
</header>

<style>
/* UNIFIED HEADER CSS - Geïntegreerd voor snelle loading */
.unified-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background-color: #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.unified-header.scrolled {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    background-color: rgba(255, 255, 255, 0.98);
    -webkit-backdrop-filter: blur(10px);
    backdrop-filter: blur(10px);
}

.unified-header .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

.unified-header .navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.2rem 0;
    height: 80px;
}

/* Logo styling */
.unified-header .logo a {
    display: flex !important;
    align-items: center !important;
    text-decoration: none !important;
    color: #1f2937 !important;
    font-weight: 600 !important;
    font-family: 'Glacial Indifference', 'Inter', sans-serif !important;
    transition: all 0.3s ease !important;
}

.unified-header .logo a:hover {
    transform: scale(1.05) !important;
}

.unified-header .logo img {
    margin-right: 12px !important;
    border-radius: 10px !important;
    transition: all 0.3s ease !important;
}

.unified-header .logo-text {
    font-size: 1.6rem !important;
    font-weight: 600 !important;
    transition: all 0.3s ease !important;
    color: #1f2937 !important;
}

.unified-header .logo a:hover .logo-text {
    color: #db2777 !important;
}

/* Navigation links */
.unified-header .nav-links {
    display: flex;
    align-items: center;
    gap: 2.5rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.unified-header .nav-links a {
    color: #4b5563;
    text-decoration: none;
    font-weight: 500;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    position: relative;
    padding: 0.6rem 0;
    font-family: 'Glacial Indifference', 'Inter', sans-serif;
}

.unified-header .nav-links a:hover {
    color: #5852f2;
}

.unified-header .nav-links a[aria-current="page"] {
    color: #5852f2;
    font-weight: 600;
}

.unified-header .nav-links a::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background: linear-gradient(135deg, #5852f2 0%, #db2777 100%);
    transition: width 0.3s ease;
}

.unified-header .nav-links a:hover::after,
.unified-header .nav-links a[aria-current="page"]::after {
    width: 100%;
}

/* Auth section */
.unified-header .auth-section {
    display: flex;
    align-items: center;
}

.unified-header .auth-buttons {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.unified-header .account-btn {
    display: inline-flex !important;
    align-items: center !important;
    padding: 0.7rem 1rem !important;
    border-radius: 8px !important;
    background-color: #f3f4f6 !important;
    color: #374151 !important;
    font-weight: 500 !important;
    font-family: 'Glacial Indifference', 'Inter', sans-serif !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    border: none !important;
    gap: 0.5rem !important;
    font-size: 1rem !important;
    text-decoration: none !important;
}

.unified-header .account-btn:hover {
    background-color: #e5e7eb !important;
    color: #1f2937 !important;
}

/* UNIFIED CART BUTTON - Perfecte styling */
.unified-header .cart-button {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    position: relative !important;
    width: 44px !important;
    height: 44px !important;
    border-radius: 8px !important;
    background-color: #f3f4f6 !important;
    color: #374151 !important;
    transition: all 0.3s ease !important;
    text-decoration: none !important;
    border: none !important;
    cursor: pointer !important;
}

.unified-header .cart-button:hover {
    background-color: #e5e7eb !important;
    color: #1f2937 !important;
    transform: translateY(-1px) !important;
}

/* UNIFIED CART COUNT - Perfecte centrering en styling */
.unified-header .unified-cart-count {
    position: absolute !important;
    top: -8px !important;
    right: -8px !important;
    background-color: #db2777 !important;
    color: white !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    height: 20px !important;
    width: 20px !important;
    border-radius: 50% !important;
    display: grid !important;
    place-items: center !important;
    font-family: 'Inter', sans-serif !important;
    line-height: 1 !important;
    box-shadow: 0 2px 4px rgba(219, 39, 119, 0.3) !important;
    opacity: 0 !important;
    transform: scale(0.8) !important;
    transition: all 0.3s ease !important;
}

.unified-header .unified-cart-count.visible {
    opacity: 1 !important;
    transform: scale(1) !important;
}

/* Mobile menu button */
.unified-header .mobile-menu-button {
    display: none;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 44px;
    height: 44px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    z-index: 1010;
}

.unified-header .hamburger-line {
    display: block;
    width: 24px;
    height: 2px;
    background-color: #4b5563;
    margin: 3px 0;
    transition: all 0.3s ease;
    border-radius: 1px;
}

.unified-header .mobile-menu-button[aria-expanded="true"] .hamburger-line:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
}

.unified-header .mobile-menu-button[aria-expanded="true"] .hamburger-line:nth-child(2) {
    opacity: 0;
}

.unified-header .mobile-menu-button[aria-expanded="true"] .hamburger-line:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -6px);
}

/* Responsive design */
@media (max-width: 768px) {
    .unified-header .mobile-menu-button {
        display: flex;
    }
    
    .unified-header .nav-links {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255, 255, 255, 0.98);
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 2.5rem;
        padding: 2rem;
        z-index: 1005;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        margin: 0;
    }
    
    .unified-header .nav-links.mobile-open {
        transform: translateX(0);
    }
    
    .unified-header .nav-links a {
        font-size: 1.3rem;
        padding: 1rem 0;
    }
}

/* Focus states voor accessibility */
.unified-header .nav-links a:focus,
.unified-header .account-btn:focus,
.unified-header .cart-button:focus,
.unified-header .mobile-menu-button:focus {
    outline: 2px solid #5852f2;
    outline-offset: 2px;
}
</style>

<script>
// UNIFIED HEADER JAVASCRIPT - Geïntegreerd voor betere performance
document.addEventListener('DOMContentLoaded', function() {
    const header = document.querySelector('.unified-header');
    const mobileMenuButton = document.querySelector('.mobile-menu-button');
    const navLinks = document.querySelector('.nav-links');
    
    // Sticky header functionaliteit
    let lastScrollTop = 0;
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        lastScrollTop = scrollTop;
    });
    
    // Mobile menu functionaliteit
    if (mobileMenuButton && navLinks) {
        mobileMenuButton.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            
            this.setAttribute('aria-expanded', !isExpanded);
            navLinks.classList.toggle('mobile-open');
            
            if (!isExpanded) {
                this.setAttribute('aria-label', 'Menu sluiten');
                document.body.style.overflow = 'hidden';
            } else {
                this.setAttribute('aria-label', 'Menu openen');
                document.body.style.overflow = '';
            }
        });
        
        // Sluit menu bij klikken op een link
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('mobile-open');
                mobileMenuButton.setAttribute('aria-expanded', 'false');
                mobileMenuButton.setAttribute('aria-label', 'Menu openen');
                document.body.style.overflow = '';
            });
        });
    }
    
    // UNIFIED CART FUNCTIONALITEIT
    function updateUnifiedCartCount() {
        const cartData = localStorage.getItem('slimmerAICart');
        const cartCountElement = document.getElementById('unified-cart-count');
        
        if (cartCountElement) {
            if (cartData) {
                const cart = JSON.parse(cartData);
                const count = cart.reduce((total, item) => total + item.quantity, 0);
                
                cartCountElement.textContent = count;
                if (count > 0) {
                    cartCountElement.classList.add('visible');
                } else {
                    cartCountElement.classList.remove('visible');
                }
            } else {
                cartCountElement.textContent = '0';
                cartCountElement.classList.remove('visible');
            }
        }
    }
    
    // Initial cart count update
    updateUnifiedCartCount();
    
    // Listen voor cart updates
    window.addEventListener('storage', updateUnifiedCartCount);
    
    // Custom event listener voor cart updates binnen dezelfde tab
    window.addEventListener('cartUpdated', updateUnifiedCartCount);
    
    // Maak update functie globaal beschikbaar
    window.updateUnifiedCartCount = updateUnifiedCartCount;
    
    console.log('[Unified Header] Initialized successfully');
});
</script> 