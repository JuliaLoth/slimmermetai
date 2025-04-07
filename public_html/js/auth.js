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
        const response = await fetch(`${API_URL}/auth/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
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