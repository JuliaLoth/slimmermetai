<?php
// Definieer paden voor de productieomgeving
define('PUBLIC_INCLUDES', __DIR__ . '/includes');

// Helper functie voor includes
function include_public($file) {
    return include PUBLIC_INCLUDES . '/' . $file;
}

// Helper functie voor asset URLs
function asset_url($path) {
    return '//' . $_SERVER['HTTP_HOST'] . '/' . ltrim($path, '/');
}

// Stel paginatitel en beschrijving in
$page_title = ' $args[0].ToString().ToUpper() ogin success | Slimmer met AI';
$page_description = 'Slimmer met AI -  $args[0].ToString().ToUpper() ogin success';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen gelukt - SlimmerMetAI</title>
    <link rel="stylesheet" href="<?php echo asset_url('css/main.css'); ?>">
    <style>
        .login-success {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 50vh;
            text-align: center;
            padding: 2rem;
        }
        
        .success-icon {
            font-size: 5rem;
            color: #4caf50;
            margin-bottom: 1rem;
        }
        
        .loading-spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin-top: 2rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Begin Header -->
<?php include_public('header.php'); ?>
<!-- Einde Header -->
    
    <main>
        <section class="login-success">
            <div class="success-icon">✓</div>
            <h1>Inloggen met Google gelukt!</h1>
            <p>Je wordt nu doorgestuurd naar je account...</p>
            <div class="loading-spinner"></div>
        </section>
    </main>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="assets/images/logo-white.png" alt="SlimmerMetAI Logo">
                    <p>SlimmerMetAI helpt je om slim en effectief AI te gebruiken in je werk en leven.</p>
                </div>
                <div class="footer-links">
                    <div class="footer-links-column">
                        <h3>Navigatie</h3>
                        <ul>
                            <li><a href="index">Home</a></li>
                            <li><a href="tools">AI Tools</a></li>
                            <li><a href="e-learning">E-Learning</a></li>
                            <li><a href="blog">Blog</a></li>
                            <li><a href="contact">Contact</a></li>
                        </ul>
                    </div>
                    <div class="footer-links-column">
                        <h3>Account</h3>
                        <ul>
                            <li><a href="login">Inloggen</a></li>
                            <li><a href="login.php#signup">Registreren</a></li>
                            <li><a href="account">Mijn Account</a></li>
                        </ul>
                    </div>
                    <div class="footer-links-column">
                        <h3>Over ons</h3>
                        <ul>
                            <li><a href="about">Over SlimmerMetAI</a></li>
                            <li><a href="privacy">Privacy Policy</a></li>
                            <li><a href="terms">Algemene Voorwaarden</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 SlimmerMetAI. Alle rechten voorbehouden.</p>
            </div>
        </div>
    </footer>
    
    <script src="<?php echo asset_url('js/auth.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get token from URL
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');
            const userEncoded = urlParams.get('user');
            
            if (token && userEncoded) {
                try {
                    // Decode user data
                    const user = JSON.parse(decodeURIComponent(userEncoded));
                    
                    // Store token and user in localStorage
                    localStorage.setItem('token', token);
                    localStorage.setItem('user', JSON.stringify(user));
                    
                    // Redirect to account page after a short delay
                    setTimeout(() => {
                        // Check if there's a returnUrl in the URL
                        const returnUrl = urlParams.get('returnUrl');
                        
                        if (returnUrl && returnUrl.startsWith('/')) {
                            window.location.href = returnUrl;
                        } else {
                            window.location.href = '/account.php';
                        }
                    }, 1500);
                    
                } catch (error) {
                    console.error('Error processing login data:', error);
                    showError('Er is een fout opgetreden bij het verwerken van je inloggegevens. Probeer het opnieuw.');
                }
            } else {
                showError('Ontbrekende inloggegevens. Probeer opnieuw in te loggen.');
            }
            
            function showError(message) {
                const successSection = document.querySelector('.login-success');
                successSection.innerHTML = `
                    <div class="error-icon" style="font-size: 5rem; color: #e74c3c; margin-bottom: 1rem;">✗</div>
                    <h1>Oeps! Er ging iets mis.</h1>
                    <p>${message}</p>
                    <a href="/login" class="btn" style="margin-top: 2rem; display: inline-block; padding: 12px 24px; background-color: #3498db; color: white; text-decoration: none; border-radius: 4px;">Terug naar inlogpagina</a>
                `;
            }
        });
    </script>
</body>
</html> 

