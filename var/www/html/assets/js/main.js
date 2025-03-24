document.addEventListener('DOMContentLoaded', function() {
    // Header scroll behavior
    const header = document.querySelector('header');
    const mobileMenuButton = document.querySelector('.mobile-menu-button');
    const navLinks = document.querySelector('.nav-links');
    const accountDropdownBtn = document.querySelector('.account-dropdown-btn');
    const accountDropdown = document.querySelector('.account-dropdown');
    const cookieNotice = document.querySelector('.cookie-notice');
    
    // Header sticky behavior
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            header.classList.add('sticky');
        } else {
            header.classList.remove('sticky');
        }
    });
    
    // Mobile menu toggle
    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', function() {
            mobileMenuButton.classList.toggle('active');
            navLinks.classList.toggle('active');
            
            // Prevent scrolling when menu is open
            if (navLinks.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });
    }
    
    // Account dropdown toggle
    if (accountDropdownBtn) {
        accountDropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            accountDropdown.classList.toggle('open');
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function closeDropdown(e) {
                if (!accountDropdown.contains(e.target)) {
                    accountDropdown.classList.remove('open');
                    document.removeEventListener('click', closeDropdown);
                }
            });
        });
    }
    
    // Cookie notice
    if (cookieNotice) {
        // Check if user has already made a choice
        if (!localStorage.getItem('cookieChoice')) {
            setTimeout(function() {
                cookieNotice.style.display = 'flex';
            }, 1000);
        }
        
        // Accept cookies
        document.getElementById('accept-cookies').addEventListener('click', function() {
            localStorage.setItem('cookieChoice', 'accepted');
            cookieNotice.style.display = 'none';
        });
        
        // Reject cookies
        document.getElementById('reject-cookies').addEventListener('click', function() {
            localStorage.setItem('cookieChoice', 'rejected');
            cookieNotice.style.display = 'none';
        });
    }
    
    // Set current year in copyright
    const currentYearElement = document.getElementById('current-year');
    if (currentYearElement) {
        currentYearElement.textContent = new Date().getFullYear();
    }
    
    // Password visibility toggle
    const passwordToggles = document.querySelectorAll('.password-toggle');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const passwordField = document.getElementById(this.getAttribute('data-password-id'));
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            // Toggle icon
            this.classList.toggle('show-password');
            const eye = this.querySelector('i');
            if (type === 'text') {
                eye.className = 'fas fa-eye-slash';
            } else {
                eye.className = 'fas fa-eye';
            }
        });
    });
    
    // Authentication tabs (for login/register page)
    const authTabs = document.querySelectorAll('.auth-tab');
    const authContents = document.querySelectorAll('.auth-content');
    
    if (authTabs.length > 0) {
        authTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const target = this.getAttribute('data-tab');
                
                // Update active tab
                authTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Show active content
                authContents.forEach(content => {
                    if (content.getAttribute('id') === target) {
                        content.style.display = 'block';
                    } else {
                        content.style.display = 'none';
                    }
                });
                
                // Update URL without page refresh
                const newUrl = window.location.pathname + (target !== 'login' ? '?tab=' + target : '');
                history.pushState({}, '', newUrl);
            });
        });
    }
    
    // Animated numbers
    function animateNumber(el) {
        const target = parseInt(el.getAttribute('data-count'));
        const duration = 1500;
        const start = 0;
        const increment = target / (duration / 16);
        
        let current = start;
        let counter = setInterval(function() {
            current += increment;
            el.textContent = Math.floor(current);
            
            if (current >= target) {
                clearInterval(counter);
                el.textContent = target;
            }
        }, 16);
    }
    
    // Animate numbers when in viewport
    const animatedNumbers = document.querySelectorAll('.animate-number');
    if (animatedNumbers.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateNumber(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        animatedNumbers.forEach(number => {
            observer.observe(number);
        });
    }
}); 