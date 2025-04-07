document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    function validateForm(form) {
        let isValid = true;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const passwordMinLength = 8;
        const requiredFields = form.querySelectorAll('[required]');
        
        // Reset previous validation messages
        form.querySelectorAll('.validation-error').forEach(el => el.remove());
        
        // Check required fields
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                showError(field, 'Dit veld is verplicht');
                isValid = false;
            }
        });
        
        // Validate email if exists
        const emailField = form.querySelector('input[type="email"]');
        if (emailField && emailField.value.trim() && !emailRegex.test(emailField.value.trim())) {
            showError(emailField, 'Voer een geldig e-mailadres in');
            isValid = false;
        }
        
        // Validate password if exists and not a forgot password form
        const passwordField = form.querySelector('input[type="password"]');
        if (passwordField && passwordField.value.trim() && 
            passwordField.id !== 'forgot-password-email' && 
            passwordField.value.length < passwordMinLength) {
            showError(passwordField, `Wachtwoord moet minimaal ${passwordMinLength} tekens bevatten`);
            isValid = false;
        }
        
        // Validate password confirmation if exists
        const confirmPasswordField = form.querySelector('#confirm_password');
        if (confirmPasswordField && passwordField && 
            confirmPasswordField.value.trim() && 
            confirmPasswordField.value !== passwordField.value) {
            showError(confirmPasswordField, 'Wachtwoorden komen niet overeen');
            isValid = false;
        }
        
        // Validate terms agreement if exists
        const termsCheckbox = form.querySelector('#terms');
        if (termsCheckbox && !termsCheckbox.checked) {
            showError(termsCheckbox, 'Je moet akkoord gaan met de voorwaarden');
            isValid = false;
        }
        
        return isValid;
    }
    
    function showError(field, message) {
        const errorElement = document.createElement('div');
        errorElement.className = 'validation-error';
        errorElement.textContent = message;
        errorElement.style.color = '#e11d48';
        errorElement.style.fontSize = '0.875rem';
        errorElement.style.marginTop = '0.25rem';
        
        field.classList.add('error');
        field.parentNode.appendChild(errorElement);
    }
    
    // Login form submission
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            } else {
                // Show loading state
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Inloggen...';
            }
        });
    }
    
    // Register form submission
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            } else {
                // Show loading state
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registreren...';
            }
        });
    }
    
    // Forgot password form submission
    const forgotPasswordForm = document.getElementById('forgot-password-form');
    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            } else {
                // Show loading state
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verzenden...';
            }
        });
    }
    
    // Reset password form submission
    const resetPasswordForm = document.getElementById('reset-password-form');
    if (resetPasswordForm) {
        resetPasswordForm.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            } else {
                // Show loading state
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Wachtwoord resetten...';
            }
        });
    }
    
    // Clear validation errors on input
    document.querySelectorAll('form input, form textarea, form select').forEach(element => {
        element.addEventListener('input', function() {
            this.classList.remove('error');
            const errorElement = this.parentNode.querySelector('.validation-error');
            if (errorElement) {
                errorElement.remove();
            }
        });
    });
    
    // Password strength indicator
    const passwordField = document.querySelector('input[type="password"]#password');
    const strengthIndicator = document.getElementById('password-strength');
    
    if (passwordField && strengthIndicator) {
        passwordField.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let feedback = '';
            
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
            if (password.match(/\d/)) strength += 1;
            if (password.match(/[^a-zA-Z\d]/)) strength += 1;
            
            switch (strength) {
                case 0:
                    feedback = 'Zeer zwak';
                    strengthIndicator.style.width = '25%';
                    strengthIndicator.style.backgroundColor = '#ef4444';
                    break;
                case 1:
                    feedback = 'Zwak';
                    strengthIndicator.style.width = '50%';
                    strengthIndicator.style.backgroundColor = '#f97316';
                    break;
                case 2:
                    feedback = 'Gemiddeld';
                    strengthIndicator.style.width = '75%';
                    strengthIndicator.style.backgroundColor = '#eab308';
                    break;
                case 3:
                    feedback = 'Sterk';
                    strengthIndicator.style.width = '85%';
                    strengthIndicator.style.backgroundColor = '#84cc16';
                    break;
                case 4:
                    feedback = 'Zeer sterk';
                    strengthIndicator.style.width = '100%';
                    strengthIndicator.style.backgroundColor = '#22c55e';
                    break;
            }
            
            strengthIndicator.setAttribute('data-strength', feedback);
        });
    }
}); 