<?php
require_once dirname(__DIR__) . '/includes/init.php'; // Pad naar init.php buiten public_html

// Helper functie voor asset URLs (indien nodig, anders verwijderen als het globaal is)
function get_asset_url($path) {
    $base_url = '//' . $_SERVER['HTTP_HOST'];
    $clean_path = ltrim($path, '/');
    return $base_url . '/' . $clean_path;
}

// Definieer paden voor de productieomgeving
// Verwijderd: define('PUBLIC_INCLUDES', __DIR__ . '/includes');

// Verwijderd: Helper functie voor includes
// function include_public($file) {
//     return include PUBLIC_INCLUDES . '/' . $file;
// }

// Verwijderd: Helper functie voor asset URLs
// function asset_url($path) {
//     return '//' . $_SERVER['HTTP_HOST'] . '/' . ltrim($path, '/');
// }

// Stel paginatitel en beschrijving in
$page_title = 'Login | Slimmer met AI';
$page_description = 'Log in op je account om toegang te krijgen tot je AI-tools en e-learnings.';
$active_page = 'login'; // Voeg active_page toe voor header.php

// Laad de standaard header (bevat <slimmer-navbar>)
require_once 'includes/header.php';
?>

<!-- Stijlen specifiek voor Login pagina layout (gebaseerd op register.php) -->
<style id="login-register-layout-styles">

  /* === Grid Layout & Card Stijlen (Exact van register.php, IDs aangepast) === */
   #login-grid { /* Was #register-grid */
       display: grid;
       grid-template-columns: 2fr 1fr; /* Formulier breder dan info */
       gap: 2rem; /* Ruimte tussen kolommen */
       align-items: start; /* Lijn items aan de bovenkant uit */
       padding: 2rem 0; /* Padding boven/onder de grid */
       max-width: 1100px; /* Maximale breedte van de grid container */
       margin: 0 auto; /* Centreer de grid */
   }

   #login-form-wrapper { /* Was #register-form-wrapper */
       background: #fff;
       padding: 2.5rem;
       border-radius: 12px;
       box-shadow: 0 6px 20px rgba(0,0,0,0.07);
   }

   .benefits-card { /* Klasse blijft hetzelfde */
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

    /* Stijlen voor de lijst in de info kaart (Exact van register.php) */
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

   /* Responsive aanpassing voor de grid (Exact van register.php) */
   @media (max-width: 992px) { /* Tablet en kleiner */
       #login-grid {
           grid-template-columns: 1fr; /* Eén kolom */
           padding: 1.5rem 0;
       }
       .benefits-card {
            position: static; /* Verwijder sticky op mobiel */
            margin-top: 2rem; /* Voeg ruimte toe boven de kaart */
       }
   }

   @media (max-width: 768px) { /* Mobiel */
       #login-form-wrapper {
           padding: 1.5rem;
       }
       .benefits-card {
           padding: 1.5rem;
       }
       .benefits-card h3 {
           font-size: 1.1rem;
       }
        ul.benefits-list li {
           font-size: 0.9rem; /* Responsive font-size */
       }
   }
   /* === Einde Grid Layout & Card Stijlen === */

  /* === Stijlen voor Formulier elementen binnen de wrapper (deels van register.php) === */
   /* Deze stijlen zijn nodig omdat we niet de globale .auth-form etc. willen gebruiken */
   /* We nemen relevante stijlen over van register.php's inline style blok */

    #login-form-wrapper h2 {
      margin-bottom: 0.5rem;
      font-size: 1.8rem !important;
      font-weight: 600 !important;
      color: #1f2937;
      font-family: 'Glacial Indifference', sans-serif !important;
      text-align: center; /* Toegevoegd voor centreren zoals in login */
   }
    #login-form-wrapper p {
      margin-bottom: 2rem;
      color: #6b7280;
      font-size: 1rem;
      text-align: center; /* Toegevoegd voor centreren zoals in login */
   }

   /* Basis formulier groep styling */
   #login-form-wrapper .form-group {
      margin-bottom: 1.5rem;
  }
   #login-form-wrapper .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500 !important;
      font-size: 1rem;
      color: #374151;
  }
   #login-form-wrapper input[type="email"],
   #login-form-wrapper input[type="password"] {
      width: 100%;
      padding: 0.8rem 1rem;
      border: 1px solid #e5e7eb;
      background-color: #f9fafb;
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s ease;
      font-family: 'Inter', sans-serif;
  }
   #login-form-wrapper input:focus {
      border-color: var(--primary-color, #5852f2);
      outline: none;
      box-shadow: 0 0 0 3px rgba(88, 82, 242, 0.1);
  }
   #login-form-wrapper .password-container {
      position: relative;
  }
   #login-form-wrapper .password-toggle {
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
   #login-form-wrapper .password-toggle:hover {
      color: var(--primary-color, #5852f2);
  }
   #login-form-wrapper .form-options {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.875rem;
      margin-bottom: 1.5rem;
      color: #6b7280;
  }
   #login-form-wrapper .checkbox-group {
      display: flex;
      align-items: center;
  }
   #login-form-wrapper .checkbox-group input[type="checkbox"] {
      width: auto;
      height: auto;
      margin-right: 0.5rem;
      border: 1px solid #d1d5db;
      border-radius: 4px;
      cursor: pointer;
  }
   #login-form-wrapper .checkbox-group label {
      font-size: 0.875rem;
      line-height: 1.4;
      margin-bottom: 0;
      color: #6b7280;
      font-weight: normal !important;
  }
   #login-form-wrapper .forgot-link {
      color: var(--primary-color, #5852f2);
      text-decoration: none;
      transition: color 0.3s ease;
      font-size: 0.875rem;
  }
   #login-form-wrapper .forgot-link:hover {
      color: var(--primary-hover, #403aa0);
      text-decoration: underline;
  }

   /* Primaire knop styling */
   #login-form-wrapper .btn-primary {
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
   #login-form-wrapper .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(88, 82, 242, 0.3);
      color: white !important;
      text-decoration: none;
  }

  /* Divider styling */
   #login-form-wrapper .auth-divider {
      display: flex;
      align-items: center;
      margin: 1.5rem 0; /* Aangepast van 2rem 0 */
      color: #6b7280;
  }
   #login-form-wrapper .auth-divider::before,
   #login-form-wrapper .auth-divider::after {
      content: "";
      flex: 1;
      height: 1px;
      background-color: #e5e7eb;
  }
   #login-form-wrapper .auth-divider span {
      padding: 0 1rem;
      font-size: 0.875rem;
  }

  /* Social button styling (exact van register.php) */
  #login-form-wrapper .social-btn {
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
      width: auto; /* Override width: 100% als die ergens anders gezet wordt */
      margin: 0 auto; /* Centreer de knop */
      max-width: 300px; /* Max breedte voor de knop */
  }
  #login-form-wrapper .social-btn svg {
      margin-right: 0.75rem;
      width: 20px;
      height: 20px;
  }
  #login-form-wrapper .social-btn.google {
      background: linear-gradient(45deg, var(--primary-color, #5852f2), var(--primary-hover, #8e88ff));
      color: white;
      border: none;
      box-shadow: 0 4px 15px rgba(88, 82, 242, 0.2);
  }
  #login-form-wrapper .social-btn.google:hover {
      background: linear-gradient(45deg, #4e49d8, #7a75e6);
      transform: translateY(-2px);
      box-shadow: 0 7px 20px rgba(88, 82, 242, 0.4);
  }

  /* Link naar andere pagina */
   #login-form-wrapper .register-link, /* Nieuwe klasse voor duidelijkheid */
   #login-form-wrapper .login-link { /* Hergebruik van register stijl */
      text-align: center;
      margin-top: 1.5rem;
      font-size: 0.9rem;
  }
   #login-form-wrapper .register-link a,
   #login-form-wrapper .login-link a {
      color: var(--primary-color, #5852f2);
      font-weight: 500 !important;
      text-decoration: none;
  }
   #login-form-wrapper .register-link a:hover,
   #login-form-wrapper .login-link a:hover {
      text-decoration: underline;
  }

    /* Stijlen voor tabs (binnen de wrapper) */
    #login-form-wrapper .auth-tabs {
        display: flex;
        margin-bottom: 2rem;
        border-bottom: 1px solid #e5e7eb;
    }
    #login-form-wrapper .tab-btn {
        padding: 0.8rem 1.5rem;
        border: none;
        background: none;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 500;
        color: #6b7280;
        position: relative;
        transition: color 0.3s ease;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px; /* Overlap border */
    }
    #login-form-wrapper .tab-btn.active {
        color: var(--primary-color, #5852f2);
        border-bottom-color: var(--primary-color, #5852f2);
    }
    #login-form-wrapper .tab-btn:not(.active):hover {
        color: #374151;
    }
    #login-form-wrapper .tab-content {
       display: none;
       opacity: 0;
       transition: opacity 0.3s ease, transform 0.3s ease;
       transform: translateY(10px);
   }
   #login-form-wrapper .tab-content.active {
       display: block !important; /* Forceer display block */
       opacity: 1;
       transform: translateY(0);
   }

   /* Berichten styling (binnen de wrapper) */
    #login-form-wrapper .auth-message {
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-radius: 8px;
        font-size: 0.95rem;
    }
    #login-form-wrapper .auth-message.error {
        background-color: #fdeaea;
        color: #e74c3c;
        border: 1px solid #f5c6cb;
    }
    #login-form-wrapper .auth-message.success {
        background-color: #e8f8f0;
        color: #27ae60;
        border: 1px solid #c3e6cb;
    }
    #login-form-wrapper .auth-message.processing {
        background-color: #eef2ff;
        color: #4f46e5;
        border: 1px solid #c7d2fe;
    }

   /* Responsive aanpassingen voor form elementen binnen wrapper */
   @media (max-width: 768px) {
        #login-form-wrapper input[type="email"],
        #login-form-wrapper input[type="password"] {
           font-size: 0.95rem;
           padding: 0.75rem 0.9rem;
       }
        #login-form-wrapper .btn-primary,
        #login-form-wrapper .social-btn {
           padding: 0.8rem 1.2rem;
           font-size: 1rem;
       }
        #login-form-wrapper .social-btn {
             max-width: none; /* Volledige breedte op mobiel */
             width: 100%;
         }
   }

</style>

<!-- De <main> tag is geopend in header.php -->

<!-- Hero sectie (blijft hetzelfde) -->
<slimmer-hero
    title="Jouw Slimmer met AI Portaal"
    subtitle="Log in op je bestaande account of maak een nieuwe aan voor toegang tot je gekochte tools en e-learnings."
    background="image"
    image-url="<?php echo get_asset_url('images/hero-background.svg'); ?>"
    centered>
    <!-- Geen actions nodig hier -->
</slimmer-hero>

<!-- Grid Container (Structuur exact van register.php, IDs aangepast) -->
<div class="container">
    <div id="login-grid"> <!-- Was #register-grid -->

        <!-- Kolom 1: Formulier Wrapper -->
        <div id="login-form-wrapper"> <!-- Was #register-form-wrapper -->

            <!-- Tabs (Blijven bovenaan binnen de wrapper) -->
             <div class="auth-tabs">
                 <button class="tab-btn active" data-tab="login-form">Inloggen</button>
                 <button class="tab-btn" data-tab="register-redirect">Registreren</button>
             </div>

             <!-- Login Tab Content -->
             <div id="login-form" class="tab-content active" data-tab-content="login-form">
                 <h2>Log in op je account</h2>
                 <p>Welkom terug! Voer je gegevens in.</p>

                 <!-- Berichten Div -->
                 <div id="login-message" class="auth-message" style="display: none;"></div>

                 <!-- E-mail Login Formulier (GEEN .auth-form klasse meer nodig hier) -->
                 <form id="emailLoginForm">
                     <?php
                     // CSRF token blijft belangrijk
                     if (class_exists('CsrfProtection')) {
                        $csrf = CsrfProtection::getInstance();
                        echo $csrf->generateTokenField();
                     }
                     ?>
                     <div class="form-group">
                         <label for="login-email">E-mailadres</label>
                         <input type="email" id="login-email" name="email" required placeholder="jouw@email.nl">
                     </div>
                     <div class="form-group">
                         <label for="login-password">Wachtwoord</label>
                         <div class="password-container">
                             <input type="password" id="login-password" name="password" required placeholder="Jouw wachtwoord">
                             <button type="button" class="password-toggle" onclick="togglePasswordVisibility('login-password', this)" aria-label="Wachtwoord tonen/verbergen">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"></circle></svg>
                             </button>
                         </div>
                     </div>
                     <div class="form-options">
                         <div class="checkbox-group">
                             <input type="checkbox" id="remember-me" name="remember-me">
                             <label for="remember-me">Onthoud mij</label>
                         </div>
                         <a href="forgot-password.php" class="forgot-link">Wachtwoord vergeten?</a>
                     </div>
                     <button type="submit" class="btn-primary" id="loginButton">Inloggen</button>
                 </form>

                  <!-- Divider (zoals in register.php) -->
                 <div class="auth-divider"><span>OF</span></div>

                 <!-- Container waarop Google Identity Services de knop rendert -->
                 <div id="googleSignInButton"></div>

             </div> <!-- /#login-form -->

             <!-- Registreer Tab Content (Blijft binnen wrapper) -->
             <div id="register-redirect" class="tab-content" data-tab-content="register-redirect">
                 <h2>Nieuw bij Slimmer met AI?</h2>
                 <p>Maak een account aan voor toegang.</p>
                 <a href="register.php" class="btn-primary" style="display: block; text-align: center;">Nu Registreren</a>
             </div>

        </div> <!-- /#login-form-wrapper -->

        <!-- Kolom 2: Voordelen Kaart (Structuur exact van register.php) -->
        <div class="benefits-card">
             <h3>Voordelen van een account</h3>
             <ul class="benefits-list">
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
        </div> <!-- /.benefits-card -->

    </div> <!-- /#login-grid -->
</div> <!-- /.container -->

<!-- Footer Component -->
<slimmer-footer></slimmer-footer>

<!-- Bestaand JavaScript blok (ongewijzigd) -->
<script>
// Functie om tabs te wisselen
function setupTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetTab = button.getAttribute('data-tab');

            // Update buttons state
            tabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            // Update content visibility
            tabContents.forEach(content => {
                if (content.getAttribute('data-tab-content') === targetTab) {
                    content.classList.add('active');
                } else {
                    content.classList.remove('active');
                }
            });
        });
    });
}

// Wachtwoord zichtbaarheid toggle
function togglePasswordVisibility(inputId, button) {
    const passwordInput = document.getElementById(inputId);
    const icon = button.querySelector('svg');
    icon.setAttribute('fill', 'none');
    icon.setAttribute('stroke', 'currentColor');
    icon.setAttribute('stroke-width', '2');
    icon.setAttribute('stroke-linecap', 'round');
    icon.setAttribute('stroke-linejoin', 'round');

    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        icon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>`;
    } else {
        passwordInput.type = "password";
        icon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
    }
}

// DOMContentLoaded event listener
document.addEventListener('DOMContentLoaded', function() {
    setupTabs(); // Initialiseer tabs

    const emailLoginForm = document.getElementById('emailLoginForm');
    const emailInput = document.getElementById('login-email');
    const passwordInput = document.getElementById('login-password');
    const rememberMeCheckbox = document.getElementById('remember-me');
    const loginMessage = document.getElementById('login-message');
    const loginButton = document.getElementById('loginButton');

    // Helper functies voor berichten
    function showError(message) {
        loginMessage.textContent = message;
        loginMessage.className = 'auth-message error';
        loginMessage.style.display = 'block';
    }
    function showSuccess(message) {
        loginMessage.textContent = message;
        loginMessage.className = 'auth-message success';
        loginMessage.style.display = 'block';
    }
    function showProcessingMessage(message) {
        loginMessage.textContent = message;
        loginMessage.className = 'auth-message processing';
        loginMessage.style.display = 'block';
    }
    function hideMessages() {
        loginMessage.style.display = 'none';
        loginMessage.textContent = '';
        loginMessage.className = 'auth-message';
    }

    // E-mail login formulier submit handler
    if (emailLoginForm) {
        emailLoginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            hideMessages();

            if (!emailInput.value || !passwordInput.value) {
                showError('Vul zowel e-mailadres als wachtwoord in.');
                return;
            }

            loginButton.textContent = 'Bezig...';
            loginButton.disabled = true;

            const formData = new FormData(emailLoginForm);
            const userData = {
                email: formData.get('email'),
                password: formData.get('password'),
                rememberMe: rememberMeCheckbox.checked,
                csrf_token: formData.get('csrf_token')
            };

            try {
                // Assuming auth.login exists and handles the API call
                const result = await auth.login(userData);

                if (result.success) {
                    showSuccess(result.message || 'Succesvol ingelogd! Je wordt doorgestuurd...');
                    // Redirect is likely handled within auth.js based on result.redirectUrl
                } else {
                    showError(result.message || 'Inloggen mislukt. Controleer je gegevens.');
                }
            } catch (error) {
                console.error('Login error:', error);
                showError('Er is een onverwachte fout opgetreden. Probeer het later opnieuw.');
            } finally {
                loginButton.textContent = 'Inloggen';
                loginButton.disabled = false;
            }
        });
    }

    // Google Sign-In Functies
    function initializeGoogleSignIn() {
        if (typeof google === 'undefined' || typeof google.accounts === 'undefined') {
             console.warn('Google Identity Services library not loaded yet.');
             setTimeout(initializeGoogleSignIn, 500);
             return;
         }
        try {
            if (window._gsiInitialised) {
                return; // al geïnitialiseerd, dubbele registratie voorkomen
            }
            google.accounts.id.initialize({
                client_id: "625906341722-2eohq5a55sl4a8511h6s20dbsicuknku.apps.googleusercontent.com",
                callback: handleCredentialResponse,
            });
            window._gsiInitialised = true;

            const buttonContainer = document.getElementById('googleSignInButton');
            if (buttonContainer) {
                // Laat GIS de knop zelf renderen met gewenste stijl
                google.accounts.id.renderButton(buttonContainer, {
                    type: 'standard',
                    theme: 'outline',
                    size: 'large',
                    shape: 'rectangular',
                    text: 'continue_with',
                    logo_alignment: 'left'
                });
                // Toon (optioneel) One-Tap prompt automatisch
                google.accounts.id.prompt((notification) => {
                    if (notification.isNotDisplayed() || notification.isSkippedMoment()) {
                        console.warn('Google One Tap prompt was not displayed or was skipped.');
                    }
                });
            } else {
                console.error('Google Sign-In container niet gevonden.');
            }
         } catch (error) {
             console.error("Google Sign-In initialization failed:", error);
             showError("Kon Google login niet initialiseren.");
         }
    }

    async function handleCredentialResponse(response) {
        console.log("Encoded JWT ID token: " + response.credential);
        const idToken = response.credential;
        const csrfToken = document.querySelector('input[name="csrf_token"]') ? document.querySelector('input[name="csrf_token"]').value : '';

        showProcessingMessage('Bezig met Google login...');

        try {
            const fetchResponse = await fetch('api/auth/google-token.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ token: idToken })
            });
            const data = await fetchResponse.json();
            console.log('Backend response:', data);

            if (data.success) {
                showSuccess(data.message || 'Succesvol ingelogd met Google! Je wordt doorgestuurd...');
                setTimeout(() => {
                     window.location.href = data.redirectUrl || 'dashboard.php';
                 }, 1500);
            } else {
                showError(data.message || 'Google login mislukt. Probeer het opnieuw of gebruik e-mail.');
            }
        } catch (error) {
            console.error('Error during Google Sign-In verification:', error);
            showError('Er is een fout opgetreden tijdens het verifiëren van de Google login.');
        }
    }

    initializeGoogleSignIn(); // Start Google Sign-In initialisatie

}); // Einde DOMContentLoaded

</script>

<!-- Laad Google Identity Services Library -->
<script src="https://accounts.google.com/gsi/client" async defer></script>


<!-- De </main> tag wordt gesloten in footer.php (of de slimmer-footer component) -->
</body>
</html>


