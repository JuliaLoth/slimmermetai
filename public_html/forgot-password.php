<?php
require_once dirname(__DIR__) . '/includes/init.php';

// Helper functie voor asset URLs (bestaat in meerdere pagina's, hier opnieuw gedefinieerd voor standalone werking)
if (!function_exists('get_asset_url')) {
    function get_asset_url($path) {
        $base_url  = '//' . $_SERVER['HTTP_HOST'];
        $cleanPath = ltrim($path, '/');
        return $base_url . '/' . $cleanPath;
    }
}

// Meta-gegevens voor de pagina
$page_title       = 'Wachtwoord vergeten | Slimmer met AI';
$page_description = 'Vraag een wachtwoord reset aan voor je Slimmer met AI-account.';
$active_page      = 'forgot-password';

// Laad standaard header (bevat <slimmer-navbar>)
require_once 'includes/header.php';
?>

<!-- Inline stijlen, gebaseerd op login/register pagina -->
<style id="forgot-password-layout-styles">
  /* Container & card layout */
  #forgot-grid {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      padding: 2rem 0;
      max-width: 600px;
      margin: 0 auto;
  }
  #forgot-wrapper {
      background: #fff;
      padding: 2.5rem;
      border-radius: 12px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.07);
      width: 100%;
  }

  /* Typografie */
  #forgot-wrapper h2 {
      margin-bottom: 0.5rem;
      font-size: 1.8rem !important;
      font-weight: 600 !important;
      color: #1f2937;
      font-family: 'Glacial Indifference', sans-serif !important;
      text-align: center;
  }
  #forgot-wrapper p {
      margin-bottom: 1.5rem;
      color: #6b7280;
      font-size: 1rem;
      text-align: center;
  }

  /* Formulier elementen */
  #forgot-wrapper .form-group {
      margin-bottom: 1.5rem;
  }
  #forgot-wrapper .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500 !important;
      font-size: 1rem;
      color: #374151;
  }
  #forgot-wrapper input[type="email"] {
      width: 100%;
      padding: 0.8rem 1rem;
      border: 1px solid #e5e7eb;
      background-color: #f9fafb;
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s ease;
      font-family: 'Inter', sans-serif;
  }
  #forgot-wrapper input[type="email"]:focus {
      border-color: var(--primary-color, #5852f2);
      outline: none;
      box-shadow: 0 0 0 3px rgba(88, 82, 242, 0.1);
  }

  #forgot-wrapper .btn-primary {
      display: block;
      width: 100%;
      background: linear-gradient(45deg, var(--primary-color, #5852f2), var(--primary-hover, #8e88ff));
      color: #fff !important;
      font-weight: 600 !important;
      padding: 0.9rem 1.5rem;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 1.05rem;
      font-family: 'Glacial Indifference', sans-serif !important;
  }
  #forgot-wrapper .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(88, 82, 242, 0.3);
  }

  /* Berichten */
  #forgot-message.auth-message {
      display: none; /* standaard verborgen */
      margin-bottom: 1.5rem;
  }

  /* Link terug naar login */
  .back-to-login {
      margin-top: 1rem;
      text-align: center;
      font-size: 0.9rem;
  }
  .back-to-login a {
      color: var(--primary-color, #5852f2);
      text-decoration: none;
  }
  .back-to-login a:hover {
      text-decoration: underline;
  }
</style>

<!-- Hero component-->
<slimmer-hero
    title="Wachtwoord vergeten"
    subtitle="We helpen je graag je wachtwoord opnieuw in te stellen."
    background="image"
    image-url="<?php echo get_asset_url('images/hero-background.svg'); ?>"
    centered>
</slimmer-hero>

<!-- Hoofdcontent -->
<div class="container">
    <div id="forgot-grid">
        <div id="forgot-wrapper">
            <h2>Wachtwoord herstellen</h2>
            <p>Vul hieronder je e-mailadres in. Je ontvangt een link om je wachtwoord te resetten.</p>

            <div id="forgot-message" class="auth-message"></div>

            <form id="forgotPasswordForm">
                <?php
                // Toon CSRF-token indien beschikbaar
                if (class_exists('CsrfProtection')) {
                    $csrf = CsrfProtection::getInstance();
                    echo $csrf->generateTokenField();
                }
                ?>
                <div class="form-group">
                    <label for="forgot-email">E-mailadres</label>
                    <input type="email" id="forgot-email" name="email" required placeholder="jouw@email.nl">
                </div>

                <button type="submit" class="btn-primary" id="forgotButton">Verstuur instructies</button>
            </form>

            <div class="back-to-login">
                <a href="login.php">← Terug naar inloggen</a>
            </div>
        </div>
    </div>
</div>

<slimmer-footer></slimmer-footer>

<?php $auth_version = filemtime(__DIR__ . '/js/auth.js'); ?>
<script src="<?php echo get_asset_url('js/auth.js?v=' . $auth_version); ?>"></script>

<!-- Functionele JavaScript -->
<script>
// Fout/succes helpers
function showForgotMessage(type, message) {
    const msgDiv = document.getElementById('forgot-message');
    if (!msgDiv) return;
    msgDiv.textContent = message;
    msgDiv.className = 'auth-message auth-message--' + type;
    msgDiv.style.display = 'block';
}
function hideForgotMessage() {
    const msgDiv = document.getElementById('forgot-message');
    if (msgDiv) {
        msgDiv.style.display = 'none';
        msgDiv.textContent = '';
        msgDiv.className = 'auth-message';
    }
}

// Formulier submit handler
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('forgotPasswordForm');
    const emailInput = document.getElementById('forgot-email');
    const forgotButton = document.getElementById('forgotButton');

    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        hideForgotMessage();

        if (!emailInput.value) {
            showForgotMessage('error', 'Vul een geldig e-mailadres in.');
            return;
        }

        forgotButton.textContent = 'Bezig...';
        forgotButton.disabled = true;

        try {
            // Gebruik de auth.js helper
            const result = await auth.forgotPassword(emailInput.value);
            if (result.success) {
                showForgotMessage('success', result.message || 'Als dit e-mailadres bij ons bekend is, ontvang je binnen enkele minuten een e-mail.');
                form.reset();
            } else {
                showForgotMessage('error', result.message || 'Er is iets misgegaan. Probeer het later opnieuw.');
            }
        } catch (err) {
            console.error('Forgot password error:', err);
            showForgotMessage('error', 'Er is een onverwachte fout opgetreden. Probeer het later opnieuw.');
        } finally {
            forgotButton.textContent = 'Verstuur instructies';
            forgotButton.disabled = false;
        }
    });
});
</script>
<?php
// Sluit main en body tags (footer include bevat enkel footer markup)
?> 