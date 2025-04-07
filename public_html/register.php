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
$page_title = ' $args[0].ToString().ToUpper() egister | Slimmer met AI';
$page_description = 'Slimmer met AI -  $args[0].ToString().ToUpper() egister';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registreren - Slimmer met AI</title>
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
    <!-- Laad alle componenten -->
    <script src="<?php echo asset_url('components/ComponentsLoader.js'); ?>"></script>
    <style>
        .register-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .register-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .register-btn:hover {
            background-color: #3971e5;
        }
        .error-message {
            color: #e74c3c;
            margin-top: 15px;
            padding: 10px;
            background-color: #fdeaea;
            border-radius: 5px;
            display: none;
        }
        .success-message {
            color: #27ae60;
            margin-top: 15px;
            padding: 10px;
            background-color: #e8f8f0;
            border-radius: 5px;
            display: none;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .terms-checkbox input {
            margin-right: 10px;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <!-- Vervang de oude header door het nieuwe AccountNavbar component -->
    <slimmer-account-navbar active-page="login" cart-count="0"></slimmer-account-navbar>

    <!-- Main Content -->
    <main id="main-content" role="main">
        <!-- Hero balk toevoegen zoals andere pagina's -->
        <slimmer-hero 
            title="Word lid van Slimmer met AI" 
            subtitle="Maak een account aan om toegang te krijgen tot alle tools en e-learnings."
            background="image"
            image-url="images/hero background def.svg"
            centered>
        </slimmer-hero>
        
        <section class="register-section">
            <div class="container">
                <div class="register-container">
                    <div id="errorMessage" class="error-message"></div>
                    <div id="successMessage" class="success-message"></div>
                    
                    <form id="registerForm">
                        <?php 
                        // Voeg CSRF-token toe
                        $csrf = CsrfProtection::getInstance();
                        echo $csrf->generateTokenField(); 
                        ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName">Voornaam</label>
                                <input type="text" id="firstName" name="firstName" required>
                            </div>
                            <div class="form-group">
                                <label for="lastName">Achternaam</label>
                                <input type="text" id="lastName" name="lastName" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">E-mailadres</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Wachtwoord</label>
                            <input type="password" id="password" name="password" required minlength="8">
                            <small>Minimaal 8 tekens, gebruik hoofdletters, kleine letters en cijfers.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmPassword">Bevestig wachtwoord</label>
                            <input type="password" id="confirmPassword" name="confirmPassword" required>
                        </div>
                        
                        <div class="terms-checkbox">
                            <input type="checkbox" id="termsAgreement" name="termsAgreement" required>
                            <label for="termsAgreement">
                                Ik ga akkoord met de <a href="terms" target="_blank">algemene voorwaarden</a> en het <a href="privacy" target="_blank">privacybeleid</a>.
                            </label>
                        </div>
                        
                        <button type="submit" class="register-btn" id="registerButton">Registreren</button>
                    </form>
                    
                    <div class="login-link">
                        Heb je al een account? <a href="login">Log hier in</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Vervang de oude footer door het footer component -->
    <!-- Begin Footer -->
<?php require_once __DIR__ . '/../components/footer.php'; ?>
<!-- Einde Footer -->

    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    
    <!-- Main JavaScript -->
    <script src="<?php echo asset_url('js/main.js'); ?>"></script>
    <script src="<?php echo asset_url('js/cart.js'); ?>"></script>
    <script src="<?php echo asset_url('js/auth.js'); ?>"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const registerForm = document.getElementById('registerForm');
            const firstNameInput = document.getElementById('firstName');
            const lastNameInput = document.getElementById('lastName');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const termsCheckbox = document.getElementById('termsAgreement');
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');
            const registerButton = document.getElementById('registerButton');
            
            // Controleer of gebruiker al is ingelogd
            if (auth.initAuth()) {
                // Redirect naar dashboard of een andere beveiligde pagina
                window.location.href = 'dashboard.php';
                return;
            }
            
            // Registratieformulier
            registerForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Reset berichten
                hideMessages();
                
                // Valideer inputs
                if (!firstNameInput.value || !lastNameInput.value || !emailInput.value || !passwordInput.value || !confirmPasswordInput.value) {
                    showError('Vul alle verplichte velden in');
                    return;
                }
                
                if (!termsCheckbox.checked) {
                    showError('Je moet akkoord gaan met de algemene voorwaarden en het privacybeleid');
                    return;
                }
                
                if (passwordInput.value !== confirmPasswordInput.value) {
                    showError('Wachtwoorden komen niet overeen');
                    return;
                }
                
                if (passwordInput.value.length < 8) {
                    showError('Wachtwoord moet minimaal 8 tekens bevatten');
                    return;
                }
                
                // Register button loading state
                registerButton.textContent = 'Bezig...';
                registerButton.disabled = true;
                
                // Verkrijg formuliergegevens inclusief CSRF token
                const formData = new FormData(registerForm);
                
                try {
                    const response = await fetch('/api/auth/register.php', {
                        method: 'POST',
                        body: JSON.stringify({
                            firstName: firstNameInput.value,
                            lastName: lastNameInput.value,
                            email: emailInput.value,
                            password: passwordInput.value,
                            csrf_token: formData.get('csrf_token')
                        }),
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    if (response.success) {
                        // Toon success message
                        showSuccess('Registratie succesvol! Controleer je e-mail om je account te verifiëren.');
                        
                        // Reset formulier
                        registerForm.reset();
                        
                        // Redirect naar login pagina na 3 seconden
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 3000);
                    } else {
                        showError(response.message || 'Er is een fout opgetreden bij de registratie');
                    }
                } catch (error) {
                    console.error('Registratie error:', error);
                    showError('Er is een fout opgetreden bij de registratie. Probeer het later opnieuw.');
                } finally {
                    // Reset register button
                    registerButton.textContent = 'Registreren';
                    registerButton.disabled = false;
                }
            });
            
            // Helper functie om foutmelding te tonen
            function showError(message) {
                errorMessage.textContent = message;
                errorMessage.style.display = 'block';
                successMessage.style.display = 'none';
            }
            
            // Helper functie om successmelding te tonen
            function showSuccess(message) {
                successMessage.textContent = message;
                successMessage.style.display = 'block';
                errorMessage.style.display = 'none';
            }
            
            // Helper functie om berichten te verbergen
            function hideMessages() {
                errorMessage.style.display = 'none';
                successMessage.style.display = 'none';
            }
        });
    </script>
</body>
</html> 

