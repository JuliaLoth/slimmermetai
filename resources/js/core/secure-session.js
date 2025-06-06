/**
 * Secure Session Management
 * Vervangt localStorage based sessie management door veilige server-side API calls
 */

class SecureSession {
    constructor() {
        this.user = null;
        this.authenticated = false;
        this.isInitialized = false;
    }

    async init() {
        if (this.isInitialized) {
            return this.authenticated;
        }

        try {
            const response = await fetch('/api/session', {
                method: 'GET',
                credentials: 'include', // Include HttpOnly cookies
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success && data.data.authenticated) {
                    this.user = data.data.user;
                    this.authenticated = true;
                } else {
                    this.authenticated = false;
                    this.user = null;
                }
            } else {
                this.authenticated = false;
                this.user = null;
            }
        } catch (error) {
            console.error('Session initialization failed:', error);
            this.authenticated = false;
            this.user = null;
        }

        this.isInitialized = true;
        return this.authenticated;
    }

    isAuthenticated() {
        return this.authenticated;
    }

    getCurrentUser() {
        return this.user;
    }

    async getUserProgress() {
        try {
            const response = await fetch('/api/user/progress', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const data = await response.json();
                return data.success ? data.data : null;
            }
        } catch (error) {
            console.error('Failed to fetch user progress:', error);
        }
        return null;
    }

    async saveUserProgress(progressData) {
        try {
            const response = await fetch('/api/user/progress', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(progressData)
            });

            if (response.ok) {
                const data = await response.json();
                return data.success;
            }
        } catch (error) {
            console.error('Failed to save user progress:', error);
        }
        return false;
    }

    async logout() {
        try {
            const response = await fetch('/api/auth/logout', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // Clear session state regardless of response
            this.authenticated = false;
            this.user = null;
            this.isInitialized = false;

            // Redirect to login
            window.location.href = '/login';
        } catch (error) {
            console.error('Logout failed:', error);
            // Force redirect even on error
            window.location.href = '/login';
        }
    }

    // Legacy compatibility methods (gradually phase out)
    getItem(key) {
        console.warn('SecureSession.getItem() is deprecated. Use specific methods instead.');
        
        switch (key) {
            case 'loggedIn':
                return this.authenticated ? 'true' : 'false';
            case 'currentUser':
                return this.user ? JSON.stringify(this.user) : null;
            case 'userEmail':
                return this.user ? this.user.email : null;
            default:
                return null;
        }
    }

    setItem(key, value) {
        console.warn('SecureSession.setItem() is deprecated. Data is now managed server-side.');
        // No-op for security
    }

    removeItem(key) {
        console.warn('SecureSession.removeItem() is deprecated. Use logout() method instead.');
        if (key === 'currentUser' || key === 'loggedIn') {
            this.logout();
        }
    }
}

// Global instance
window.secureSession = new SecureSession();

export default window.secureSession; 