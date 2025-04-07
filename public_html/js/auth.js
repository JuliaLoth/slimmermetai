/**
 * Authenticatie functies voor Slimmer met AI
 * Integreert met de backend API voor gebruikersbeheer
 */

// API configuratie
const API_URL = 'https://slimmermetai.com/api'; // Productie API URL

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
        // Geen aparte CSRF token header nodig, backend verwacht het in de body
        const response = await fetch(`${API_URL}/auth/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
                // Geen X-CSRF-Token header hier
            },
            body: JSON.stringify(userData) // Stuur het volledige userData object inclusief csrf_token
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
async function login(email, password) {
    debug(`Login poging voor: ${email}`);
    try {
        debug('Versturen van login verzoek naar API...');
        const response = await fetch(`${API_URL}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include', // Belangrijk voor refresh token cookie
            body: JSON.stringify({ email, password })
        });
        
        debug(`API response status: ${response.status}`);
        const data = await handleResponse(response);
        debug(`API response data: ${JSON.stringify(data)}`);
        
        if (data.success && data.token) {
            debug('Login succesvol, token opslaan');
            // Sla gegevens op
            authToken = data.token;
            currentUser = data.user;
            
            localStorage.setItem('token', data.token);
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
            await fetch(`${API_URL}/auth/logout`, {
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
        const response = await fetch(`${API_URL}/auth/refresh-token`, {
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
        
        const response = await fetch(`${API_URL}/auth/me`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        const data = await handleResponse(response);
        
        if (data.success && data.data) {
            // Update gebruiker
            currentUser = data.data;
            localStorage.setItem('user', JSON.stringify(data.data));
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
            method: 'PUT',
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
        const response = await fetch(`${API_URL}/auth/forgot-password`, {
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
        const response = await fetch(`${API_URL}/auth/reset-password`, {
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
    window.location.href = `${API_URL}/auth/google`;
}

// Email verifiëren
async function verifyEmail(token) {
    try {
        const response = await fetch(`${API_URL}/auth/verify-email`, {
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
        const data = await response.json();
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
        // return; // Stop verdere initialisatie als we redirecten
    }

    const emailLoginForm = document.getElementById('emailLoginForm');
    const loginButton = document.getElementById('loginButton');
    const googleButton = document.getElementById('google-signin-button');
    const microsoftButton = document.getElementById('microsoft-signin-button');

    if (emailLoginForm && loginButton) {
        emailLoginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            showMessage('info', 'Bezig met inloggen...'); // Gebruik showMessage
            loginButton.textContent = 'Bezig...';
            loginButton.disabled = true;

            const formData = new FormData(emailLoginForm);
            const email = formData.get('email');
            const password = formData.get('password');
            // const rememberMe = formData.get('remember-me') === 'on'; // Onthoud mij logica nog implementeren
            const csrfToken = formData.get('csrf_token');

            try {
                // LET OP: De originele login functie verwachtte 4 argumenten
                // maar de auth.login hierboven verwacht er maar 2.
                // We passen de aanroep aan of de auth.login functie zelf.
                // Voor nu passen we de aanroep aan en negeren rememberMe & csrfToken hier.
                // Idealiter wordt CSRF token validatie server-side gedaan.
                const result = await auth.login(email, password);

                if (result.success) {
                    showMessage('success', 'Succesvol ingelogd! Je wordt doorverwezen...');
                    window.location.href = result.redirectUrl || 'dashboard.php';
                } else {
                    showMessage('error', result.message || 'Ongeldige e-mail of wachtwoord.');
                    loginButton.textContent = 'Inloggen';
                    loginButton.disabled = false;
                }
            } catch (error) {
                debug(`Login error: ${error.message}`);
                showMessage('error', 'Er is een technische fout opgetreden. Probeer het later opnieuw.');
                loginButton.textContent = 'Inloggen';
                loginButton.disabled = false;
            }
        });
    }

    if (googleButton) {
        googleButton.addEventListener('click', () => {
             debug('Google Sign-In button clicked!');
             showMessage('info', 'Google login wordt geïnitialiseerd...');
             // Gebruik de bestaande googleLogin functie die de redirect doet
             auth.googleLogin(); 
             // Foutafhandeling gebeurt na redirect of via callback
        });
    }

    if (microsoftButton) {
        microsoftButton.addEventListener('click', () => {
            debug('Microsoft Sign-In button clicked!');
            showMessage('info', 'Microsoft login wordt geïnitialiseerd...');
            // Voeg hier de aanroep naar Microsoft login toe (bv. auth.microsoftLogin())
            // Voor nu een placeholder:
            showMessage('error', 'Microsoft login is nog niet geïmplementeerd.');
            // auth.signInWithMicrosoft().catch(error => {
            //     debug(`Microsoft sign-in error: ${error.message}`);
            //     showMessage('error', 'Kon niet inloggen met Microsoft. Fout: ' + (error.message || error));
            // });
        });
    }

    // Luister naar tab change events van de form card (optioneel)
    const formCard = document.querySelector('slimmer-auth-form-card');
    if (formCard) {
        formCard.addEventListener('tab-change', (event) => {
            debug('Tab changed to: ' + event.detail.tabId);
            // Hier kun je eventueel de URL hash aanpassen of andere acties uitvoeren
        });
    }

    debug('Login pagina initialisatie voltooid.');

    // --- Tab Switching Logic --- 
    const tabsContainer = document.querySelector('.auth-tabs');
    if (tabsContainer) {
        const tabButtons = tabsContainer.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content[data-tab-content]'); // Selecteer op data-attribuut

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.getAttribute('data-tab');

                // Update button active state
                tabButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                // Update content active state
                tabContents.forEach(content => {
                    if (content.getAttribute('data-tab-content') === tabId) {
                        content.classList.add('active');
                    } else {
                        content.classList.remove('active');
                    }
                });
                
                 // Optioneel: Als de 'Registreren' tab wordt geklikt, direct doorverwijzen?
                // if (tabId === 'register-redirect') {
                //     window.location.href = 'register.php';
                // }
            });
        });
    }
    // --- End Tab Switching Logic ---
}

// Roep de init functie aan als dit script op de login pagina draait
// Controleer op het bestaan van het login formulier
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('emailLoginForm')) { // Controleer of het login formulier bestaat
        initLoginPage();
    }
    // Voeg hier eventueel initialisatie voor andere pagina's toe
    // Bijvoorbeeld voor de registratiepagina:
    // if (document.getElementById('registerForm')) {
    //     initRegisterPage(); 
    // }
}); 