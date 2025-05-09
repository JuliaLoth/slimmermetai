<?php
// Definieer paden voor de productieomgeving
// Verwijderd: define('PUBLIC_INCLUDES', __DIR__ . '/includes');

// Verwijderd: Helper functie voor includes
// function include_public($file) {
//     return include PUBLIC_INCLUDES . '/' . $file;
// }

// Helper functie voor asset URLs (opnieuw toegevoegd)
function get_asset_url($path) {
    // Basis URL (pas aan indien nodig, bv. https)
    $base_url = '//' . $_SERVER['HTTP_HOST'];
    // Zorg dat het pad relatief is tot de web root
    $clean_path = ltrim($path, '/');
    return $base_url . '/' . $clean_path;
}

// Stel paginatitel en beschrijving in
$page_title = 'Registreren | Slimmer met AI'; // Correctie in titel
$page_description = 'Maak een gratis account aan bij Slimmer met AI.'; // Correctie in beschrijving
$active_page = 'register'; // Voeg active_page toe voor header.php

// Laad de standaard header, die de <slimmer-navbar> bevat
require_once 'includes/header.php';
?>
<!-- Voeg hier de inline styles toe -->
<style id="auth-content-styles">
  /* === Auth Content Stijlen (Direct in Head) === */
   h2 { 
      margin-bottom: 0.5rem;
      font-size: 1.8rem !important;
      font-weight: 600 !important;
      color: #1f2937;
      font-family: 'Glacial Indifference', sans-serif !important;
  }
   p {
      margin-bottom: 2rem;
      color: #6b7280;
      font-size: 1rem;
  }
  
  .auth-form .form-group { 
      margin-bottom: 1.5rem;
  }
  .auth-form .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500 !important;
      font-size: 1rem;
      color: #374151;
  }
  .auth-form input[type="text"], 
  .auth-form input[type="email"], 
  .auth-form input[type="password"] {
      width: 100%;
      padding: 0.8rem 1rem;
      border: 1px solid #e5e7eb;
      background-color: #f9fafb;
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s ease;
      font-family: 'Inter', sans-serif;
  }
  .auth-form input:focus {
      border-color: var(--primary-color, #5852f2);
      outline: none;
      box-shadow: 0 0 0 3px rgba(88, 82, 242, 0.1);
  }
  .password-container {
      position: relative;
  }
  .password-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #6b7280;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0;
      z-index: 2;
  }
  .password-toggle:hover {
      color: var(--primary-color, #5852f2);
  }
  .form-options {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.875rem;
      margin-bottom: 1.5rem;
      color: #6b7280;
  }
  .checkbox-group {
      display: flex;
      align-items: center;
  }
  .checkbox-group input[type="checkbox"] {
      width: auto;
      height: auto;
      margin-right: 0.5rem;
      border: 1px solid #d1d5db;
      border-radius: 4px;
      cursor: pointer;
  }
  .checkbox-group label {
      font-size: 0.875rem;
      line-height: 1.4;
      margin-bottom: 0;
      color: #6b7280;
      font-weight: normal !important;
  }
  .checkbox-group label a {
      color: var(--primary-color, #5852f2);
      text-decoration: none;
  }
  .checkbox-group label a:hover {
      text-decoration: underline;
  }
  .forgot-link {
      color: var(--primary-color, #5852f2);
      text-decoration: none;
      transition: color 0.3s ease;
      font-size: 0.875rem;
  }
  .forgot-link:hover {
      color: var(--primary-hover, #403aa0);
      text-decoration: underline;
  }
  .auth-form .btn-primary { 
      display: inline-block;
      background: linear-gradient(45deg, var(--primary-color, #5852f2), var(--primary-hover, #8e88ff));
      color: white !important;
      font-weight: 600 !important;
      padding: 0.9rem 1.5rem;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      transition: all 0.3s ease;
      text-align: center;
      width: 100%;
      font-size: 1.05rem;
      margin-top: 0.5rem;
      text-decoration: none;
      font-family: 'Glacial Indifference', sans-serif !important;
  }
  .auth-form .btn-primary:hover { 
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(88, 82, 242, 0.3);
      color: white !important;
      text-decoration: none;
  }
  .social-auth {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      margin: 2rem 0;
  }
  .social-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0.9rem;
      border-radius: 8px;
      font-weight: 600 !important;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 1rem;
      text-decoration: none;
      border: 1px solid #e5e7eb;
      font-family: 'Glacial Indifference', sans-serif !important;
  }
  .social-btn svg {
      margin-right: 0.75rem;
      width: 20px;
      height: 20px;
  }
  .social-btn.google {
      background: linear-gradient(45deg, var(--primary-color, #5852f2), var(--primary-hover, #8e88ff));
      color: white;
      border: none;
      box-shadow: 0 4px 15px rgba(88, 82, 242, 0.2);
  }
  .social-btn.google:hover {
      background: linear-gradient(45deg, #4e49d8, #7a75e6);
      transform: translateY(-2px);
      box-shadow: 0 7px 20px rgba(88, 82, 242, 0.4);
  }
  .social-btn.microsoft {
      background-color: #fff;
      color: #374151;
      border: 1px solid #e5e7eb;
  }
  .social-btn.microsoft:hover {
      background-color: #f9fafb;
      border-color: #d1d5db;
  }
  .auth-divider {
      display: flex;
      align-items: center;
      margin: 1.5rem 0;
      color: #6b7280;
  }
  .auth-divider::before, 
  .auth-divider::after {
      content: "";
      flex: 1;
      height: 1px;
      background-color: #e5e7eb;
  }
  .auth-divider span {
      padding: 0 1rem;
      font-size: 0.875rem;
  }
  .login-link { 
      text-align: center;
      margin-top: 1.5rem;
      font-size: 0.9rem;
  }
  .login-link a {
      color: var(--primary-color, #5852f2);
      font-weight: 500 !important;
      text-decoration: none;
  }
  .login-link a:hover {
      text-decoration: underline;
  }
  .auth-form .form-row { 
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
      margin-bottom: 1rem;
  }
  .auth-form .form-row .form-group {
      margin-bottom: 0;
  }
  .terms-checkbox {
       display: flex;
       align-items: flex-start;
       margin-bottom: 1.5rem;
   }
   .terms-checkbox input {
      width: auto;
      margin-right: 0.5rem;
      margin-top: 3px;
   }
   .terms-checkbox label {
       font-size: 0.9rem;
       line-height: 1.4;
   }
   .terms-checkbox label a {
   }
  
   /* Stijlen voor tab content */
   .tab-content { 
       display: none; 
       opacity: 0;
       transition: opacity 0.3s ease, transform 0.3s ease;
       transform: translateY(10px);
   }
   .tab-content.active {
       display: block !important; 
       opacity: 1;
       transform: translateY(0);
   }
  /* Responsive content styles */
  @media (max-width: 768px) {
       .auth-form .form-row {
          grid-template-columns: 1fr;
          gap: 0;
          margin-bottom: 0;
       }
       .auth-form .form-row .form-group {
          margin-bottom: 1.5rem;
       }
       .auth-form input[type="text"],
       .auth-form input[type="email"],
       .auth-form input[type="password"] {
           font-size: 0.95rem;
           padding: 0.75rem 0.9rem;
       }
       .auth-form .btn-primary, 
       .social-btn { 
           padding: 0.8rem 1.2rem;
           font-size: 1rem;
       }
  }
  /* Stijlen voor de lijst in de info kaart */
   ul.benefits-list li { 
       display: flex !important; 
       align-items: center;
       margin-bottom: 1.25rem;
       color: #4b5563;
       font-size: 0.95rem;
       font-weight: normal !important;
       font-family: 'Glacial Indifference', sans-serif !important;
   }
   ul.benefits-list li svg {
       flex-shrink: 0;
       width: 1.25rem;
       height: 1.25rem;
       margin-right: 0.75rem;
       color: var(--primary-color, #5852f2);
   }
   @media (max-width: 768px) {
       ul.benefits-list li {
           font-size: 0.9rem;
       }
   }
  /* === Einde Auth Content Stijlen === */

   /* Nieuwe Grid Layout voor Formulier en Info */
   #register-grid {
       display: grid;
       grid-template-columns: 2fr 1fr; /* Formulier breder dan info */
       gap: 2rem; /* Ruimte tussen kolommen */
       align-items: start; /* Lijn items aan de bovenkant uit */
       padding: 2rem 0; /* Padding boven/onder de grid */
       max-width: 1100px; /* Maximale breedte van de grid container */
       margin: 0 auto; /* Centreer de grid */
   }

   #register-form-wrapper {
       background: #fff;
       padding: 2.5rem;
       border-radius: 12px;
       box-shadow: 0 6px 20px rgba(0,0,0,0.07);
   }

   .benefits-card {
       background: #f9fafb; 
       padding: 2rem;
       border-radius: 12px;
       border: 1px solid #e5e7eb;
       position: sticky; /* Maak de kaart sticky */
       top: 100px; /* Startpositie vanaf de top (na navbar) */
   }

   .benefits-card h3 {
       font-size: 1.3rem;
       font-weight: 600;
       color: #1f2937;
       margin-bottom: 1.5rem;
       font-family: 'Glacial Indifference', sans-serif !important;
   }

   /* Responsive aanpassing voor de grid */
   @media (max-width: 992px) { /* Tablet en kleiner */
       #register-grid {
           grid-template-columns: 1fr; /* Eén kolom */
           padding: 1.5rem 0;
       }
       .benefits-card {
            position: static; /* Verwijder sticky op mobiel */
            margin-top: 2rem; /* Voeg ruimte toe boven de kaart */
       }
   }

   @media (max-width: 768px) { /* Mobiel */
       #register-form-wrapper {
           padding: 1.5rem;
       }
       .benefits-card {
           padding: 1.5rem;
       }
       .benefits-card h3 {
           font-size: 1.1rem;
       }
   }
  /* === Einde Auth Content Stijlen === */
</style>

<main id="main-content" role="main">

<!-- Hero sectie (blijft bovenaan) -->
<slimmer-hero 
    title="Maak je gratis account aan" 
    subtitle="Krijg toegang tot alle AI-tools, cursussen en houd je voortgang bij."
    background="image" 
    image-url="images/hero-background.svg" 
    centered>
    <!-- Geen actieknoppen nodig hier -->
</slimmer-hero>

<!-- Grid container voor formulier en info kaart ONDER hero -->
<div class="container"> <!-- Bootstrap container voor padding/max-width -->
    <div id="register-grid">
        <!-- Kolom 1: Registratie Formulier -->
        <div id="register-form-wrapper"> 
            <div id="register-form"> <!-- ID blijft voor JS -->
                <h2 style="text-align: center; margin-bottom: 1.5rem;">Account aanmaken</h2>
                <div id="errorMessage" class="error-message" style="display: none; color: #e74c3c; margin-bottom: 15px; padding: 10px; background-color: #fdeaea; border-radius: 5px;"></div>
                <div id="successMessage" class="success-message" style="display: none; color: #27ae60; margin-bottom: 15px; padding: 10px; background-color: #e8f8f0; border-radius: 5px;"></div>
                        
                <form id="registerForm" class="auth-form">
                    <?php 
                    if (class_exists('CsrfProtection')) {
                         $csrf = CsrfProtection::getInstance();
                         echo $csrf->generateTokenField(); 
                    }
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
                        <div class="password-container">
                           <input type="password" id="password" name="password" required minlength="8">
                           <button type="button" class="password-toggle" onclick="togglePasswordVisibility('password', this)">
                              <!-- Oog open icoon -->
                              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                 <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                 <circle cx="12" cy="12" r="3"/>
                              </svg>
                           </button>
                        </div>
                        <small style="font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem; display: block;">Minimaal 8 tekens, gebruik hoofdletters, kleine letters en cijfers.</small>
                    </div>
                            
                    <div class="form-group">
                        <label for="confirmPassword">Bevestig wachtwoord</label>
                        <div class="password-container">
                           <input type="password" id="confirmPassword" name="confirmPassword" required>
                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirmPassword', this)">
                               <!-- Oog open icoon -->
                               <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                  <circle cx="12" cy="12" r="3"/>
                               </svg>
                           </button>
                        </div>
                    </div>
                            
                    <div class="terms-checkbox">
                        <input type="checkbox" id="termsAgreement" name="termsAgreement" required>
                        <label for="termsAgreement">
                            Ik ga akkoord met de <a href="terms.php" target="_blank">algemene voorwaarden</a> en het <a href="privacy.php" target="_blank">privacybeleid</a>.
                        </label>
                    </div>
                            
                    <button type="submit" class="btn-primary" id="registerButton">Registreren</button>
                </form>
                        
                <div class="auth-divider"><span>OF</span></div>
                
                <!-- Eigen Google knop met website styling -->
                <div style="display: flex; justify-content: center; margin-bottom: 1rem;">
                    <button type="button" id="customGoogleSignInButton" class="social-btn google">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M21.99 12.18c0-.79-.07-1.58-.2-2.38H12v4.51h5.6c-.24 1.47-1.02 2.74-2.19 3.61v3.07h3.94c2.3-2.12 3.63-5.1 3.63-8.81z" fill="#ffffff"/>
                            <path d="M12 22c3.28 0 6.02-1.09 8.03-2.95l-3.94-3.07c-1.09.73-2.48 1.16-4.09 1.16-3.14 0-5.79-2.11-6.74-4.96H1.19v3.17C3.17 19.58 7.25 22 12 22z" fill="#ffffff"/>
                            <path d="M5.26 14.11c-.18-.53-.28-1.09-.28-1.66s.1-1.13.28-1.66V7.61H1.19C.45 9.02 0 10.46 0 12s.45 2.98 1.19 4.34l4.07-3.23z" fill="#ffffff"/>
                            <path d="M12 5.14c1.77 0 3.36.61 4.62 1.82l3.48-3.48C18.02 1.96 15.28 0 12 0 7.25 0 3.17 2.42 1.19 5.79l4.07 3.17c.95-2.85 3.6-4.96 6.74-4.96z" fill="#ffffff"/>
                        </svg>
                        Doorgaan met Google
                    </button>
                </div>

                <div class="login-link">
                    Heb je al een account? <a href="login.php">Log hier in</a>
                </div>
            </div>
        </div>

        <!-- Kolom 2: Voordelen Kaart -->
        <div class="benefits-card">
            <h3>Jouw voordelen met een account</h3>
            <ul class="benefits-list"> <!-- Gebruik bestaande class voor styling -->
                 <li>
                     Toegang tot alle AI-tools
                 </li>
                 <li>
                     Uitgebreide e-learning cursussen
                 </li>
                 <li>
                     Voortgang bijhouden & prestaties
                 </li>
                 <li>
                     Favorieten opslaan & persoonlijke lijsten
                 </li>
                 <li>
                     Exclusieve updates & aanbiedingen
                 </li>
            </ul>
        </div>
    </div>
</div>

</main> <!-- Sluit de main content sectie -->

<?php
// Footer wordt nu geladen als web component
?>
<slimmer-footer></slimmer-footer>

<!-- Verwijder oude footer include -->
<?php // require_once 'includes/footer.php'; ?>

<!-- Verwijder Font Awesome - neem aan dat dit in de globale header/css zit -->
<!-- <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script> -->
    
<!-- Scripts specifiek voor de registratiepagina -->
<!-- Zorg ervoor dat get_asset_url bestaat (moet in init.php of helpers zitten) -->
<!-- <script src="<?php echo get_asset_url('js/main.js'); ?>"></script> -->
<!-- <script src="<?php echo get_asset_url('js/cart.js'); ?>"></script> -->
<?php $auth_version = filemtime(__DIR__ . '/js/auth.js'); ?>
<script src="<?php echo get_asset_url('js/auth.js?v=' . $auth_version); ?>"></script>

<script>
// Wachtwoord zichtbaarheid toggle (hergebruikt van login pagina)
function togglePasswordVisibility(inputId, button) {
    const passwordInput = document.getElementById(inputId);
    const icon = button.querySelector('svg');
    // Zet de correcte SVG attributen
    icon.setAttribute('fill', 'none');
    icon.setAttribute('stroke', 'currentColor');
    icon.setAttribute('stroke-width', '2');
    icon.setAttribute('stroke-linecap', 'round');
    icon.setAttribute('stroke-linejoin', 'round');
    
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        // Verander icoon naar "oog dicht"
        icon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>`;
    } else {
        passwordInput.type = "password";
        // Verander icoon terug naar "oog open"
        icon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
    }
}

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
            
    // Init Auth (niet redirecten, gebruiker is hier om te registreren)
    auth.initAuth();
            
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
            // Maak een object met de gebruikersgegevens
            const userData = {
                firstName: formData.get('firstName'),
                lastName: formData.get('lastName'),
                email: formData.get('email'),
                password: formData.get('password'),
                csrf_token: formData.get('csrf_token'), // CSRF token blijft hier voorlopig
                termsAgreement: termsCheckbox.checked // Nieuw: stuur akkoord-veld mee
            };

            // Roep auth.register aan met het userData object
            const result = await auth.register(userData);
                    
            if (result.success) {
                // Toon success message
                showSuccess(result.message || 'Registratie succesvol! Controleer je e-mail om je account te verifiëren.');
                        
                // Reset formulier
                registerForm.reset();
                        
                // Redirect naar login pagina na 3 seconden
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 3000);
            } else {
                showError(result.message || 'Er is een fout opgetreden bij de registratie');
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

    // Maak globaal beschikbaar voor functies buiten deze scope (zoals showProcessingMessage)
    window.hideMessages = hideMessages;
});

// === Google Sign-In Functies ===

function handleCredentialResponse(response) {
  console.log("Encoded JWT ID token: " + response.credential);
  const idToken = response.credential;
  const csrfToken = document.querySelector('input[name="csrf_token"]') ? document.querySelector('input[name="csrf_token"]').value : ''; // Haal CSRF token op indien aanwezig

  // Toon feedback aan gebruiker
  showProcessingMessage('Bezig met Google login...'); 

  // Stuur het token naar de backend voor verificatie
  fetch('api/auth/google-token.php', { // Endpoint gecorrigeerd
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken // Stuur CSRF token mee indien aanwezig
    },
    body: JSON.stringify({ token: idToken })
  })
  .then(response => response.json())
  .then(data => {
    console.log('Backend response:', data);
    if (data.success) {
      showSuccess('Succesvol ingelogd met Google! Je wordt doorgestuurd...');
      // Stuur gebruiker door naar het dashboard of een andere relevante pagina
      window.location.href = data.redirectUrl || 'dashboard.php'; 
    } else {
      showError(data.message || 'Google login mislukt. Probeer het opnieuw.');
    }
  })
  .catch(error => {
    console.error('Error during Google Sign-In verification:', error);
    showError('Er is een fout opgetreden tijdens het verifiëren van de Google login.');
  });
}

// Functie om de Google knop te initialiseren
function initializeGoogleSignIn() {
  if (!window._gsiInitialised) {
    google.accounts.id.initialize({
      client_id: "625906341722-2eohq5a55sl4a8511h6s20dbsicuknku.apps.googleusercontent.com", // Jouw Google Client ID
      callback: handleCredentialResponse,
      use_fedcm_for_prompt: true // Opt-in voor FedCM, vereist voor toekomstige compatibiliteit
    });
    window._gsiInitialised = true;
  }

  // Voeg event listener toe aan onze eigen knop
  const customButton = document.getElementById('customGoogleSignInButton');
  if (customButton) {
      customButton.addEventListener('click', () => {
          // Toon FedCM/One-Tap prompt; oude moment-API callbacks zijn vanaf 2025 verwijderd.
          google.accounts.id.prompt();
      });
  } else {
      console.error('Custom Google Sign-In button not found.');
  }

  // Toon eventueel direct de One Tap prompt als de gebruiker al ingelogd is bij Google
  // google.accounts.id.prompt();
}

// Helper functie voor verwerkingsbericht (kan uitgebreid worden)
function showProcessingMessage(message) {
    // Eenvoudige implementatie, kan vervangen worden door een mooiere loader
    hideMessages(); // Verberg bestaande berichten
    const processingMessage = document.getElementById('successMessage'); // Hergebruik succesvak of maak een nieuwe
    if (processingMessage) {
        processingMessage.textContent = message;
        processingMessage.style.backgroundColor = '#eef2ff'; // Lichtblauwe achtergrond
        processingMessage.style.color = '#4f46e5'; // Indigo kleur
        processingMessage.style.display = 'block';
    }
}

// Roep initialisatie aan nadat de GSI client is geladen
// Dit gebeurt impliciet door het `defer` attribuut op het GSI script.
// We voegen een window.onload toe voor de zekerheid, vooral als er andere scripts draaien.
window.onload = function () {
  initializeGoogleSignIn();
};

</script>

<!-- Laad Google Identity Services Library -->
<script src="https://accounts.google.com/gsi/client" async defer></script>

<!-- De standaard footer component wordt geladen via require_once -->
<!-- dus we hoeven hier geen </body></html> -->

