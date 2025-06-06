<?php /** @var string $title */ ?>

<a href="#main-content" class="skip-link">Direct naar inhoud</a>

<section class="hero-with-background" aria-labelledby="login-heading">
    <div class="container">
        <div class="hero-content">
            <h1 id="login-heading">Mijn Account</h1>
            <p>Log in om toegang te krijgen tot je persoonlijke leeromgeving, tools en cursussen.</p>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="auth-tabs">
            <button type="button" class="tab-btn active" data-tab="login">Inloggen</button>
            <button type="button" class="tab-btn" data-tab="register">Account aanmaken</button>
            <button type="button" class="tab-btn" data-tab="forgot">Wachtwoord vergeten</button>
        </div>
        
        <div class="auth-row">
            <div class="auth-forms">
                <!-- Login Form -->
                <div id="login-tab" class="tab-content active">
                    <div class="auth-container">
                        <h2>Inloggen</h2>
                        
                        <div class="alert alert-error" id="login-error" style="display: none;"></div>
                        
                        <!-- Google Login Button -->
                        <div class="google-auth-section">
                            <a href="/api/auth/google.php?redirect=/dashboard" class="btn btn-google">
                                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                    <g fill="none" fill-rule="evenodd">
                                        <path d="M17.64 9.205c0-.639-.057-1.252-.164-1.841H9v3.481h4.844a4.14 4.14 0 0 1-1.796 2.716v2.259h2.908c1.702-1.567 2.684-3.875 2.684-6.615z" fill="#4285F4"/>
                                        <path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z" fill="#34A853"/>
                                        <path d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
                                        <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z" fill="#EA4335"/>
                                    </g>
                                </svg>
                                Doorgaan met Google
                            </a>
                            
                            <div class="auth-divider">
                                <span>of</span>
                            </div>
                        </div>
                        
                        <form class="auth-form" method="post" action="/auth/login" id="login-form">
                            <input type="hidden" name="csrf_token" value="">
                            
                            <div class="form-group">
                                <label for="login-email">E-mailadres</label>
                                <input type="email" id="login-email" name="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="login-password">Wachtwoord</label>
                                <div class="password-container">
                                    <input type="password" id="login-password" name="password" required>
                                    <button type="button" class="password-toggle" data-target="login-password">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M8 2C3.5 2 0 8 0 8s3.5 6 8 6 8-6 8-6-3.5-6-8-6zm0 10c-2.8 0-5.3-2.7-6.9-4C2.7 6.7 5.2 4 8 4s5.3 2.7 6.9 4c-1.6 1.3-4.1 4-6.9 4zm0-7a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm0 4.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Inloggen</button>
                        </form>
                    </div>
                </div>
                
                <!-- Register Form -->
                <div id="register-tab" class="tab-content">
                    <div class="auth-container">
                        <h2>Account aanmaken</h2>
                        
                        <div class="alert alert-success" id="register-success" style="display: none;">
                            Registratie succesvol! Controleer je e-mail om je account te activeren.
                        </div>
                        <div class="alert alert-error" id="register-error" style="display: none;"></div>
                        
                        <!-- Google Register Button -->
                        <div class="google-auth-section">
                            <a href="/api/auth/google.php?redirect=/dashboard" class="btn btn-google">
                                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                    <g fill="none" fill-rule="evenodd">
                                        <path d="M17.64 9.205c0-.639-.057-1.252-.164-1.841H9v3.481h4.844a4.14 4.14 0 0 1-1.796 2.716v2.259h2.908c1.702-1.567 2.684-3.875 2.684-6.615z" fill="#4285F4"/>
                                        <path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z" fill="#34A853"/>
                                        <path d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
                                        <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z" fill="#EA4335"/>
                                    </g>
                                </svg>
                                Registreren met Google
                            </a>
                            
                            <div class="auth-divider">
                                <span>of</span>
                            </div>
                        </div>
                        
                        <form class="auth-form" method="post" action="/auth/register" id="register-form">
                            <input type="hidden" name="csrf_token" value="">
                            
                            <div class="form-group">
                                <label for="register-name">Naam</label>
                                <input type="text" id="register-name" name="name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="register-email">E-mailadres</label>
                                <input type="email" id="register-email" name="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="register-password">Wachtwoord (minimaal 8 tekens)</label>
                                <div class="password-container">
                                    <input type="password" id="register-password" name="password" required minlength="8">
                                    <button type="button" class="password-toggle" data-target="register-password">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M8 2C3.5 2 0 8 0 8s3.5 6 8 6 8-6 8-6-3.5-6-8-6zm0 10c-2.8 0-5.3-2.7-6.9-4C2.7 6.7 5.2 4 8 4s5.3 2.7 6.9 4c-1.6 1.3-4.1 4-6.9 4zm0-7a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm0 4.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="agree_terms" required>
                                    <span>Ik ga akkoord met de <a href="/privacy" target="_blank">Voorwaarden en Privacybeleid</a></span>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Account aanmaken</button>
                        </form>
                    </div>
                </div>
                
                <!-- Forgot Password Form -->
                <div id="forgot-tab" class="tab-content">
                    <div class="auth-container">
                        <h2>Wachtwoord herstellen</h2>
                        
                        <div class="alert alert-success" id="forgot-success" style="display: none;">
                            Als dit e-mailadres bij ons bekend is, ontvang je binnen enkele minuten een e-mail met instructies om je wachtwoord te herstellen.
                        </div>
                        <div class="alert alert-error" id="forgot-error" style="display: none;"></div>
                        
                        <!-- Google Login Alternative -->
                        <div class="google-auth-section">
                            <p class="google-auth-hint">Heb je je account aangemaakt met Google? Dan kun je direct inloggen:</p>
                            <a href="/api/auth/google.php?redirect=/dashboard" class="btn btn-google">
                                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                    <g fill="none" fill-rule="evenodd">
                                        <path d="M17.64 9.205c0-.639-.057-1.252-.164-1.841H9v3.481h4.844a4.14 4.14 0 0 1-1.796 2.716v2.259h2.908c1.702-1.567 2.684-3.875 2.684-6.615z" fill="#4285F4"/>
                                        <path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z" fill="#34A853"/>
                                        <path d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
                                        <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z" fill="#EA4335"/>
                                    </g>
                                </svg>
                                Doorgaan met Google
                            </a>
                            
                            <div class="auth-divider">
                                <span>of herstel je wachtwoord</span>
                            </div>
                        </div>
                        
                        <form class="auth-form" method="post" action="/auth/forgot-password" id="forgot-form">
                            <input type="hidden" name="csrf_token" value="">
                            
                            <div class="form-group">
                                <label for="forgot-email">E-mailadres</label>
                                <input type="email" id="forgot-email" name="email" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Herstel wachtwoord</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="auth-info">
                <div class="auth-info-card">
                    <h3>Waarom een account?</h3>
                    <ul>
                        <li>Toegang tot exclusieve AI-tools</li>
                        <li>Volg e-learnings op je eigen tempo</li>
                        <li>Sla aangepaste prompts en templates op</li>
                        <li>Krijg als eerste toegang tot nieuwe features</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Auth page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
        input.value = csrfToken;
    });
    
    // Tab switching functionality
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Update URL without page reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.pushState({}, '', url);
            
            // Update active tab
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // Update tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(tabId + '-tab').classList.add('active');
        });
    });
    
    // Password toggle functionality
    const toggleButtons = document.querySelectorAll('.password-toggle');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M2.79 4.61a.5.5 0 0 0-.792.614l.615.795C1.592 7.015 1 8 1 8s1.5 3 7 3 7-3 7-3-.5-1.018-1.613-2.02l.634-.818a.5.5 0 0 0-.778-.63A6.968 6.968 0 0 0 13.5 6.5C13.168 5.296 11.5 3 8 3 5.338 3 3.289 4.427 2.79 4.61zm1.71 1.985a6.02 6.02 0 0 1 3.5-1.595C7.033 5 6 5.233 6 6.5c0 1.11.587 1.96 1.526 2.406A7.97 7.97 0 0 1 8 9c-4.196 0-5-2-5-2s.152-.427.42-.91c.268-.483.63-.948 1.08-1.493zm8.5 0c-.6-.49-.968-.911-1.146-1.106.468.308.878.807.878 1.606 0 .819-.67 1.558-1.685 1.913 1.356.067 2.453-.467 2.453-1.413 0-.33-.138-.645-.5-1z"/></svg>';
            } else {
                passwordInput.type = 'password';
                this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 2C3.5 2 0 8 0 8s3.5 6 8 6 8-6 8-6-3.5-6-8-6zm0 10c-2.8 0-5.3-2.7-6.9-4C2.7 6.7 5.2 4 8 4s5.3 2.7 6.9 4c-1.6 1.3-4.1 4-6.9 4zm0-7a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm0 4.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/></svg>';
            }
        });
    });
    
    // Check URL for tab parameter
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab');
    if (activeTab && ['login', 'register', 'forgot'].includes(activeTab)) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        
        document.querySelector(`[data-tab="${activeTab}"]`).classList.add('active');
        document.getElementById(activeTab + '-tab').classList.add('active');
    }
});
</script> 