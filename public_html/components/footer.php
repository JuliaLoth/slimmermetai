<?php
// Helper functie voor asset URLs als deze nog niet bestaat
if (!function_exists('asset_url')) {
    function asset_url($path) {
        return '//' . $_SERVER['HTTP_HOST'] . '/' . ltrim($path, '/');
    }
}
?>
<footer role="contentinfo">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-contact">
                <h3>Slimmer met AI</h3>
                <p>Praktische AI-tools en e-learnings voor Nederlandse professionals.</p>
                <div class="contact-info">
                    <a href="mailto:contact@slimmermetai.com" class="email">contact@slimmermetai.com</a>
                </div>
                <div class="social-links">
                    <a href="https://linkedin.com/" target="_blank" rel="noopener" aria-label="Bezoek onze LinkedIn pagina">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path>
                            <rect x="2" y="9" width="4" height="12"></rect>
                            <circle cx="4" cy="4" r="2"></circle>
                        </svg>
                    </a>
                    <a href="#" target="_blank" rel="noopener" aria-label="Bezoek onze Mastodon pagina">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                           <path d="M21.259 13.818c.3-.68.44-1.46.44-2.31 0-1.17-.21-2.23-.63-3.18-.42-.95-.98-1.75-1.67-2.41-.7-.65-1.53-1.13-2.5-1.44-.97-.3-1.99-.46-3.06-.46-1.3 0-2.5.24-3.6.71-.9.38-1.65.89-2.26 1.52-.6.63-.98 1.33-1.14 2.1-.15.77-.23 1.57-.23 2.4h3.17c0-.44.03-.84.09-1.19.06-.35.15-.68.29-.97.14-.3.3-.55.5-.77.2-.22.43-.39.69-.51.27-.13.55-.21.85-.26.3-.05.6-.07.9-.07.54 0 1.04.07 1.5.2.46.13.87.33 1.23.6.36.27.66.6 1.13 1.26.15.21.28.44.38.7.1.26.17.53.21.83.04.3.06.6.06.91 0 .4-.03.78-.08 1.13-.05.35-.13.68-.24.98-.1.3-.24.57-.4.8-.17.23-.36.42-.58.57-.22.15-.46.26-.72.33-.26.07-.53.1-.81.1-.25 0-.5-.02-.73-.06-.24-.04-.46-.1-.65-.18-.2-.09-.37-.19-.51-.3-.14-.12-.26-.25-.34-.4-.08-.15-.14-.3-.16-.45-.02-.15-.03-.3-.03-.45h-3.17c0 .87.15 1.67.44 2.39.29.72.7 1.32 1.23 1.8.53.48 1.15.82 1.85 1.03.7.2 1.42.31 2.16.31.85 0 1.66-.11 2.42-.34.76-.23 1.44-.58 2.04-1.05.6-.47 1.07-1.05 1.4-1.76.34-.7.5-1.49.5-2.35 0-.18-.01-.37-.03-.55zm-7.82 1.51c-.38 0-.75-.04-1.1-.13s-.68-.22-.97-.4c-.3-.18-.54-.4-.74-.66-.2-.26-.34-.56-.43-.9h1.73c.03.18.08.33.17.46s.2.23.34.3c.14.07.3.13.46.16.17.03.34.05.5.05.4 0 .76-.06 1.06-.18.3-.12.45-.3.45-.55 0-.08-.01-.15-.04-.21-.03-.06-.07-.12-.12-.17-.05-.05-.1-.09-.17-.13-.07-.04-.14-.07-.2-.09-.13-.04-.26-.08-.4-.11-.14-.03-.28-.06-.42-.08-.56-.1-.98-.23-1.26-.4-.28-.16-.42-.4-.42-.7 0-.21.06-.4.18-.55.12-.15.28-.27.48-.36.2-.09.43-.15.68-.19.25-.04.5-.06.75-.06.34 0 .68.03 1.01.1s.63.18.9.32c.27.14.5.3.67.5.17.2.3.42.36.66h-1.71c-.04-.17-.1-.3-.18-.4-.08-.1-.18-.18-.3-.23s-.26-.1-.4-.12c-.15-.02-.3-.03-.45-.03-.34 0-.63.05-.88.16-.25.11-.37.27-.37.5 0 .07.01.14.04.19.03.06.07.11.13.15.06.04.12.08.2.11.08.03.16.06.25.08.12.03.25.06.38.09.13.03.26.06.4.08.56.11.98.25 1.26.43.28.18.42.43.42.75 0 .24-.07.45-.21.63-.14.18-.32.33-.56.45-.24.12-.5.2-.8.25-.3.05-.62.08-.95.08z"/>
                        </svg>
                    </a>
                </div>
            </div>
            <div class="footer-nav-section">
                <h4>Navigatie</h4>
                <nav class="footer-nav" aria-label="Footer navigatie">
                    <a href="<?php echo asset_url('index.php'); ?>">Home</a>
                    <a href="<?php echo asset_url('tools.php'); ?>">Tools</a>
                    <a href="<?php echo asset_url('e-learnings.php'); ?>">E-learnings</a>
                    <a href="<?php echo asset_url('over-mij.php'); ?>">Over Mij</a>
                    <a href="<?php echo asset_url('nieuws.php'); ?>">Nieuws</a>
                </nav>
            </div>
            <div class="footer-account-section">
                <h4>Account</h4>
                <nav class="footer-account" aria-label="Account navigatie">
                    <a href="<?php echo asset_url('login.php'); ?>">Inloggen</a>
                    <a href="<?php echo asset_url('register.php'); ?>">Registreren</a>
                    <a href="<?php echo asset_url('mijn-cursussen.php'); ?>">Mijn Cursussen</a>
                    <a href="<?php echo asset_url('dashboard.php'); ?>">Dashboard</a>
                </nav>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 Slimmer met AI. Alle rechten voorbehouden.</p>
            <div class="footer-legal">
                <a href="<?php echo asset_url('privacybeleid.php'); ?>">Privacybeleid</a>
                <a href="<?php echo asset_url('algemene-voorwaarden.php'); ?>">Algemene Voorwaarden</a>
                <a href="<?php echo asset_url('cookies.php'); ?>">Cookies</a>
            </div>
        </div>
    </div>
</footer> 