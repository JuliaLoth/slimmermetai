/**
 * Authenticatie functies voor Slimmer met AI
 * Integreert met de backend API voor gebruikersbeheer
 */

// Gebruik een relatieve URL om CSP-problemen met www/non-www te voorkomen
const API_URL = '/api';

// Debug mode om problemen op te sporen
const DEBUG = true;

// Auth state
let currentUser = null;
let authToken = null;

function debug(message) {
    if (DEBUG) {
        console.log(`[Auth Debug] ${message}`);
    }
}

// Initialiseer auth state
function initAuth() {
    debug('InitAuth aangeroepen');
    // Probeer gebruiker uit localStorage te laden
    const storedToken = localStorage.getItem('token');
    const storedUser = localStorage.getItem('user');
    
    if (storedToken && storedUser) {
        debug('Token en user gevonden in localStorage');
        authToken = storedToken;
        currentUser = JSON.parse(storedUser);
        return true;
    }
    
    debug('Geen token of user gevonden in localStorage');
    return false;
}

// Registreer een nieuwe gebruiker
async function register(userData) {
    try {
        const response = await fetch(`${API_URL}/auth/register.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': userData.csrf_token || '',
            },
            credentials: 'include',
            body: JSON.stringify(userData)
        });
        
        return await handleResponse(response);
    } catch (error) {
        console.error('Registratie fout:', error);
        return {
            success: false,
            message: 'Er is een fout opgetreden bij de registratie'
        };
    }
}

// Log gebruiker in
async function login(arg1, arg2) {
    // Ondersteun zowel login(email, password) als login({ email, password, rememberMe })
    let email, password, rememberMe = false;
    if (typeof arg1 === 'object' && arg1 !== null) {
        // Eerste argument is credentials object
        email = arg1.email || '';
        password = arg1.password || '';
        rememberMe = arg1.rememberMe || arg1.remember || false;
    } else {
        email = arg1;
        password = arg2;
    }

    debug(`Login poging voor: ${email}`);
    try {
        debug('Versturen van login verzoek naar API...');
        const response = await fetch(`${API_URL}/auth/login.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include', // Belangrijk voor refresh token cookie
            body: JSON.stringify({ email, password, remember: rememberMe })
        });
        
        debug(`API response status: ${response.status}`);
        const data = await handleResponse(response);
        debug(`API response data: ${JSON.stringify(data)}`);
        
        if (data.success && data.access_token) {
            debug('Login succesvol, token opslaan');
            // Sla gegevens op
            authToken = data.access_token;
            currentUser = data.user;
            
            localStorage.setItem('token', data.access_token);
            localStorage.setItem('user', JSON.stringify(data.user));
        } else {
            debug(`Login mislukt: ${data.message || 'Onbekende fout'}`);
        }
        
        return data;
    } catch (error) {
        debug(`Login fout: ${error.message}`);
        console.error('Login fout:', error);
        return {
            success: false,
            message: 'Er is een fout opgetreden bij het inloggen'
        };
    }
}

// Log gebruiker uit
async function logout() {
    try {
        if (authToken) {
            await fetch(`${API_URL}/auth/logout.php`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${authToken}`
                },
                credentials: 'include'
            });
        }
        
        // Clear auth state
        clearAuthData();
        
        return { success: true };
    } catch (error) {
        console.error('Logout fout:', error);
        
        // Clear auth state zelfs bij fouten
        clearAuthData();
        
        return {
            success: false,
            message: 'Er is een fout opgetreden bij het uitloggen'
        };
    }
}

// Vernieuw access token
async function refreshToken() {
    try {
        const response = await fetch(`${API_URL}/auth/refresh-token.php`, {
            method: 'POST',
            credentials: 'include'
        });
        
        const data = await handleResponse(response);
        
        if (data.success && data.token) {
            // Update opgeslagen token
            authToken = data.token;
            currentUser = data.user;
            
            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
        }
        
        return data;
    } catch (error) {
        console.error('Token vernieuwing fout:', error);
        return {
            success: false,
            message: 'Token vernieuwing mislukt'
        };
    }
}

// Haal huidige gebruiker op
async function getCurrentUser() {
    try {
        if (!authToken) {
            return {
                success: false,
                message: 'Niet ingelogd'
            };
        }
        
        const response = await fetch(`${API_URL}/auth/me.php`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        const data = await handleResponse(response);
        
        if (data.success && data.user) {
            // Update gebruiker
            currentUser = data.user;
            localStorage.setItem('user', JSON.stringify(data.user));
        }
        
        return data;
    } catch (error) {
        console.error('Fout bij ophalen gebruiker:', error);
        return {
            success: false,
            message: 'Kan gebruiker niet ophalen'
        };
    }
}

// Gebruikersprofiel bijwerken
async function updateProfile(profileData) {
    try {
        if (!authToken) {
            return {
                success: false,
                message: 'Niet ingelogd'
            };
        }
        
        const response = await fetch(`${API_URL}/users/profile`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify(profileData)
        });
        
        const data = await handleResponse(response);
        
        if (data.success && data.user) {
            // Update gebruiker
            currentUser = data.user;
            localStorage.setItem('user', JSON.stringify(data.user));
        }
        
        return data;
    } catch (error) {
        console.error('Fout bij bijwerken profiel:', error);
        return {
            success: false,
            message: 'Kan profiel niet bijwerken'
        };
    }
}

// Wachtwoord wijzigen
async function changePassword(currentPassword, newPassword) {
    try {
        if (!authToken) {
            return {
                success: false,
                message: 'Niet ingelogd'
            };
        }
        
        const response = await fetch(`${API_URL}/users/password`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({ currentPassword, newPassword })
        });
        
        return await handleResponse(response);
    } catch (error) {
        console.error('Fout bij wijzigen wachtwoord:', error);
        return {
            success: false,
            message: 'Kan wachtwoord niet wijzigen'
        };
    }
}

// Wachtwoord vergeten
async function forgotPassword(email) {
    try {
        const response = await fetch(`${API_URL}/auth/forgot-password.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email })
        });
        
        return await handleResponse(response);
    } catch (error) {
        console.error('Fout bij wachtwoord vergeten:', error);
        return {
            success: false,
            message: 'Kan wachtwoord reset niet aanvragen'
        };
    }
}

// Wachtwoord resetten
async function resetPassword(token, password) {
    try {
        const response = await fetch(`${API_URL}/auth/reset-password.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ token, password })
        });
        
        return await handleResponse(response);
    } catch (error) {
        console.error('Fout bij resetten wachtwoord:', error);
        return {
            success: false,
            message: 'Kan wachtwoord niet resetten'
        };
    }
}

// Google login
function googleLogin() {
    window.location.href = `${API_URL}/auth/google.php`;
}

// Email verifiëren
async function verifyEmail(token) {
    try {
        const response = await fetch(`${API_URL}/auth/verify-email.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ token })
        });
        
        return await handleResponse(response);
    } catch (error) {
        console.error('Fout bij verifiëren email:', error);
        return {
            success: false,
            message: 'Kan email niet verifiëren'
        };
    }
}

// Account verwijderen
async function deleteAccount() {
    try {
        if (!authToken) {
            return {
                success: false,
                message: 'Niet ingelogd'
            };
        }
        
        const response = await fetch(`${API_URL}/users/account`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        const data = await handleResponse(response);
        
        if (data.success) {
            // Clear auth state
            clearAuthData();
        }
        
        return data;
    } catch (error) {
        console.error('Fout bij verwijderen account:', error);
        return {
            success: false,
            message: 'Kan account niet verwijderen'
        };
    }
}

// Helper functies

// Verwerk API response
async function handleResponse(response) {
    debug(`Verwerken van response: ${response.status}`);
    try {
        let data;
        try {
            data = await response.json();
        } catch (jsonErr) {
            // Fallback: probeer tekst te lezen (bij HTML 404 etc.)
            const text = await response.text();
            debug(`Response is geen JSON, raw text: ${text.substring(0,100)}`);
            data = { message: text };
        }
        debug(`Response data: ${JSON.stringify(data)}`);
        
        if (!response.ok) {
            debug(`API fout: ${data.message || response.statusText}`);
            return {
                success: false,
                message: data.message || 'Er is een fout opgetreden'
            };
        }
        
        return {
            success: true,
            ...data
        };
    } catch (error) {
        debug(`Response verwerking fout: ${error.message}`);
        return {
            success: false,
            message: 'Kan response niet verwerken'
        };
    }
}

// Verwijder auth data
function clearAuthData() {
    authToken = null;
    currentUser = null;
    localStorage.removeItem('token');
    localStorage.removeItem('user');
}

// Controleer of gebruiker is ingelogd
function isLoggedIn() {
    return !!authToken;
}

// Controleer of gebruiker admin is
function isAdmin() {
    return currentUser && currentUser.role === 'admin';
}

// Exporteer de functies
window.auth = {
    initAuth,
    register,
    login,
    logout,
    refreshToken,
    getCurrentUser,
    updateProfile,
    changePassword,
    forgotPassword,
    resetPassword,
    googleLogin,
    verifyEmail,
    deleteAccount,
    isLoggedIn,
    isAdmin
}; 

// === Login Pagina Specifieke Functies ===

// Wachtwoord zichtbaarheid toggle
function togglePasswordVisibility(inputId, button) {
    const passwordInput = document.getElementById(inputId);
    if (!passwordInput) return;
    const icon = button.querySelector('svg');
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        if(icon) icon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>`;
    } else {
        passwordInput.type = "password";
        if(icon) icon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
    }
}

// Functie om berichten te tonen met CSS klassen
function showMessage(type, message) {
    const messageElement = document.getElementById('login-message'); // Gebruik een consistente ID
    if (!messageElement) return;

    messageElement.textContent = message;
    // Reset klassen en voeg de juiste toe
    messageElement.className = 'auth-message'; // Reset naar basisklasse
    if (type === 'success') {
        messageElement.classList.add('auth-message--success');
    } else if (type === 'error') {
        messageElement.classList.add('auth-message--error');
    } else if (type === 'info') {
        messageElement.classList.add('auth-message--info');
    }
    // Zorg dat het element zichtbaar is (indien het via CSS verborgen was)
    messageElement.style.display = 'block'; 
}

// Initialiseer event listeners en logica specifiek voor de login pagina
function initLoginPage() {
    debug('Initialiseren van login pagina specifieke logica...');

    // Maak togglePasswordVisibility globaal beschikbaar als het via onclick wordt aangeroepen
    window.togglePasswordVisibility = togglePasswordVisibility;

    // Check of gebruiker al ingelogd is en redirect indien nodig
    // (Je kunt initAuth hier integreren of apart houden afhankelijk van de flow)
    if (auth.isLoggedIn()) {
        // Optioneel: Redirect naar dashboard als al ingelogd
        // window.location.href = 'dashboard.php';
        // debug('Gebruiker is al ingelogd.');
    }

    // Event listeners voor tabbladen (indien aanwezig)
    setupTabSwitching();

    // Event listeners voor formulieren
    setupFormSubmissions();

    // Password visibility toggles
    setupPasswordToggles();

    // CSRF token injectie
    injectCSRFTokens();
}

// Tabblad switching tussen login, register, forgot password
function setupTabSwitching() {
    const tabs = document.querySelectorAll('.auth-tab');
    const forms = document.querySelectorAll('.auth-form');

    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const targetForm = this.getAttribute('data-target');

            // Update actieve tab
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Update zichtbare form
            forms.forEach(form => {
                if (form.id === targetForm) {
                    form.style.display = 'block';
                } else {
                    form.style.display = 'none';
                }
            });

            // Update URL (optional)
            const url = new URL(window.location);
            url.searchParams.set('tab', targetForm);
            window.history.replaceState(null, '', url);
        });
    });

    // Check URL parameters for initial tab
    const urlParams = new URLSearchParams(window.location.search);
    const initialTab = urlParams.get('tab');
    if (initialTab) {
        const tabButton = document.querySelector(`[data-target="${initialTab}"]`);
        if (tabButton) {
            tabButton.click();
        }
    }
}

// Formulier submissions
function setupFormSubmissions() {
    // Login form
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const email = formData.get('email');
            const password = formData.get('password');
            const rememberMe = formData.get('remember') === 'on';

            showMessage('info', 'Inloggen...');

            const result = await login({ email, password, rememberMe });
            
            if (result.success) {
                showMessage('success', 'Succesvol ingelogd! Je wordt doorgestuurd...');
                
                // Redirect naar dashboard of gewenste pagina
                setTimeout(() => {
                    if (result.redirect) {
                        window.location.href = result.redirect;
                    } else {
                        window.location.href = '/dashboard';
                    }
                }, 1000);
            } else {
                showMessage('error', result.message || 'Inloggen mislukt');
            }
        });
    }

    // Register form
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const userData = {
                name: formData.get('name'),
                email: formData.get('email'),
                password: formData.get('password'),
                confirm_password: formData.get('confirm_password'),
                csrf_token: formData.get('_token')
            };

            showMessage('info', 'Account aanmaken...');

            const result = await register(userData);
            
            if (result.success) {
                showMessage('success', 'Account succesvol aangemaakt! Je wordt doorgestuurd...');
                
                setTimeout(() => {
                    if (result.redirect) {
                        window.location.href = result.redirect;
                    } else {
                        window.location.href = '/dashboard';
                    }
                }, 1000);
            } else {
                showMessage('error', result.message || 'Registratie mislukt');
            }
        });
    }

    // Forgot password form
    const forgotForm = document.getElementById('forgot-password-form');
    if (forgotForm) {
        forgotForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const email = formData.get('email');

            showMessage('info', 'Wachtwoord reset aanvragen...');

            const result = await forgotPassword(email);
            
            if (result.success) {
                showMessage('success', 'Reset email verzonden! Controleer je inbox.');
            } else {
                showMessage('error', result.message || 'Reset aanvraag mislukt');
            }
        });
    }
}

// Password visibility toggles
function setupPasswordToggles() {
    const toggleBtns = document.querySelectorAll('.password-toggle');
    
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const inputId = this.getAttribute('data-target');
            togglePasswordVisibility(inputId, this);
        });
    });
}

// CSRF token injectie in formulieren
function injectCSRFTokens() {
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (!metaToken) return;
    
    const token = metaToken.getAttribute('content');
    const forms = document.querySelectorAll('.auth-form form');
    
    forms.forEach(form => {
        // Check of er al een CSRF token input is
        let tokenInput = form.querySelector('input[name="_token"]');
        
        if (!tokenInput) {
            // Maak een nieuwe CSRF token input
            tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            form.appendChild(tokenInput);
        }
        
        tokenInput.value = token;
    });
}

// Als we op de login pagina zijn, initialiseer dan de logica
if (document.addEventListener) {
    document.addEventListener('DOMContentLoaded', initLoginPage);
} else if (document.attachEvent) {
    document.attachEvent('onreadystatechange', function() {
        if (document.readyState === 'complete') {
            initLoginPage();
        }
    });
}

// ES6 Module Export voor compatibility met tests
const Auth = {
    init: initAuth,
    setToken: function(token) {
        authToken = token;
        localStorage.setItem('token', token);
    },
    getToken: function() {
        return authToken || localStorage.getItem('token');
    },
    setUser: function(user) {
        currentUser = user;
        localStorage.setItem('user', JSON.stringify(user));
    },
    getUser: function() {
        return currentUser;
    },
    isLoggedIn: isLoggedIn,
    login: login,
    register: register,
    logout: logout,
    togglePasswordVisibility: togglePasswordVisibility
};

export default Auth; 