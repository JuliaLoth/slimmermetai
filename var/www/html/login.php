<?php
// Laad configuratie
require_once '../config/config.php';

// Laad classes
require_once 'includes/Auth.php';
require_once 'includes/Security.php';

// Initialiseer Auth en Security
$auth = Auth::getInstance();
$security = Security::getInstance();

// Controleer of de gebruiker al is ingelogd
if ($auth->isLoggedIn()) {
    // Redirect naar dashboard
    header('Location: dashboard.php');
    exit;
}

// Verwerk login formulier
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Controleer CSRF token
    $security->validateCsrfToken($_POST['csrf_token'] ?? '');
    
    // Controleer rate limiting
    $rateLimitCheck = $security->checkRateLimit('login', 5, 300);
    if ($rateLimitCheck['limited']) {
        $loginError = $rateLimitCheck['message'];
    } else {
        // Valideer en sanitize input
        $email = $security->sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Inloggen
        $user = $auth->login($email, $password);
        
        if ($user) {
            // Reset rate limiting bij succes
            $security->resetRateLimit('login');
            
            // Redirect naar dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            $loginError = $auth->getError() ?: 'Ongeldige inloggegevens';
        }
    }
}

// Verwerk wachtwoord vergeten formulier
$forgotSuccess = false;
$forgotError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot'])) {
    // Controleer CSRF token
    $security->validateCsrfToken($_POST['csrf_token'] ?? '');
    
    // Controleer rate limiting
    $rateLimitCheck = $security->checkRateLimit('forgot', 3, 900);
    if ($rateLimitCheck['limited']) {
        $forgotError = $rateLimitCheck['message'];
    } else {
        // Valideer en sanitize input
        $email = $security->sanitize($_POST['email'] ?? '');
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result = $auth->forgotPassword($email);
            $forgotSuccess = true;
        } else {
            $forgotError = 'Vul een geldig e-mailadres in';
        }
    }
}

// Verwerk registratie formulier
$registerSuccess = false;
$registerError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // Controleer CSRF token
    $security->validateCsrfToken($_POST['csrf_token'] ?? '');
    
    // Controleer rate limiting
    $rateLimitCheck = $security->checkRateLimit('register', 3, 3600);
    if ($rateLimitCheck['limited']) {
        $registerError = $rateLimitCheck['message'];
    } else {
        // Valideer en sanitize input
        $userData = [
            'name' => $security->sanitize($_POST['name'] ?? ''),
            'email' => $security->sanitize($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'agree_terms' => isset($_POST['agree_terms']) ? 1 : 0
        ];
        
        // Validatie
        if (empty($userData['name'])) {
            $registerError = 'Vul je naam in';
        } elseif (empty($userData['email']) || !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $registerError = 'Vul een geldig e-mailadres in';
        } elseif (empty($userData['password']) || strlen($userData['password']) < 8) {
            $registerError = 'Wachtwoord moet minimaal 8 tekens lang zijn';
        } elseif (!$userData['agree_terms']) {
            $registerError = 'Je moet akkoord gaan met de voorwaarden';
        } else {
            // Registreer gebruiker
            $userId = $auth->register($userData);
            
            if ($userId) {
                $registerSuccess = true;
                
                // Reset rate limiting bij succes
                $security->resetRateLimit('register');
            } else {
                $registerError = $auth->getError() ?: 'Registratie mislukt, probeer het later opnieuw';
            }
        }
    }
}

// Bepaal actieve tab
$activeTab = 'login';
if (isset($_GET['tab'])) {
    if ($_GET['tab'] === 'register') {
        $activeTab = 'register';
    } elseif ($_GET['tab'] === 'forgot') {
        $activeTab = 'forgot';
    }
}
?>
<!DOCTYPE html>
<html lang="nl" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account | Slimmer met AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="Log in op je account om toegang te krijgen tot je AI-tools en e-learnings.">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* Directe stijl overrides voor login pagina */
        .auth-tabs {
            display: flex;
            border-radius: 10px;
            background-color: #f3f4f6;
            padding: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .tab-btn {
            flex: 1;
            background: none;
            border: none;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 600;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 0 0.25rem;
        }
        
        .tab-btn:hover {
            color: #5852f2;
            background-color: rgba(88, 82, 242, 0.05);
        }
        
        .tab-btn.active {
            background: linear-gradient(45deg, #5852f2, #8e88ff);
            color: white;
            box-shadow: 0 4px 12px rgba(88, 82, 242, 0.2);
        }
        
        .tab-content {
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .tab-content.active {
            display: block;
            opacity: 1;
        }
        
        .auth-container {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            padding: 2.5rem;
            margin-bottom: 2rem;
        }
        
        .auth-row {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 2rem;
            align-items: start;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .auth-form .form-group {
            margin-bottom: 1.5rem;
        }
        
        .auth-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .auth-form input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            background-color: #f9fafb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .auth-form input:focus {
            border-color: #5852f2;
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
        }
        
        .password-toggle:hover {
            color: #5852f2;
        }
        
        .btn-primary {
            display: inline-block;
            background: linear-gradient(45deg, #5852f2, #8e88ff);
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            width: 100%;
            font-size: 1rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(88, 82, 242, 0.2);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #065f46;
        }
        
        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #b91c1c;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main id="main-content" role="main">
        <section class="page-hero">
            <div class="container">
                <div class="hero-content">
                    <h1>Mijn Account</h1>
                    <p>Log in om toegang te krijgen tot je persoonlijke leeromgeving, tools en cursussen.</p>
                </div>
            </div>
        </section>
        
        <section class="section">
            <div class="container">
                <div class="auth-tabs">
                    <button type="button" class="tab-btn <?php echo $activeTab === 'login' ? 'active' : ''; ?>" data-tab="login">Inloggen</button>
                    <button type="button" class="tab-btn <?php echo $activeTab === 'register' ? 'active' : ''; ?>" data-tab="register">Account aanmaken</button>
                    <button type="button" class="tab-btn <?php echo $activeTab === 'forgot' ? 'active' : ''; ?>" data-tab="forgot">Wachtwoord vergeten</button>
                </div>
                
                <div class="auth-row">
                    <div class="auth-forms">
                        <!-- Login Form -->
                        <div id="login-tab" class="tab-content <?php echo $activeTab === 'login' ? 'active' : ''; ?>">
                            <div class="auth-container">
                                <h2>Inloggen</h2>
                                
                                <?php if (!empty($loginError)): ?>
                                    <div class="alert alert-error"><?php echo $security->escape($loginError); ?></div>
                                <?php endif; ?>
                                
                                <form class="auth-form" method="post" action="login.php">
                                    <?php echo $security->getCsrfInput(); ?>
                                    <input type="hidden" name="login" value="1">
                                    
                                    <div class="form-group">
                                        <label for="login-email">E-mailadres</label>
                                        <input type="email" id="login-email" name="email" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="login-password">Wachtwoord</label>
                                        <div class="password-container">
                                            <input type="password" id="login-password" name="password" required>
                                            <button type="button" class="password-toggle" data-target="login-password">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                    <path d="M8 2C3.5 2 0 8 0 8s3.5 6 8 6 8-6 8-6-3.5-6-8-6zm0 10c-2.8 0-5.3-2.7-6.9-4C2.7 6.7 5.2 4 8 4s5.3 2.7 6.9 4c-1.6 1.3-4.1 4-6.9 4zm0-7a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm0 4.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn-primary">Inloggen</button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Register Form -->
                        <div id="register-tab" class="tab-content <?php echo $activeTab === 'register' ? 'active' : ''; ?>">
                            <div class="auth-container">
                                <h2>Account aanmaken</h2>
                                
                                <?php if ($registerSuccess): ?>
                                    <div class="alert alert-success">
                                        Registratie succesvol! Controleer je e-mail om je account te activeren.
                                    </div>
                                <?php elseif (!empty($registerError)): ?>
                                    <div class="alert alert-error"><?php echo $security->escape($registerError); ?></div>
                                <?php endif; ?>
                                
                                <form class="auth-form" method="post" action="login.php?tab=register">
                                    <?php echo $security->getCsrfInput(); ?>
                                    <input type="hidden" name="register" value="1">
                                    
                                    <div class="form-group">
                                        <label for="register-name">Naam</label>
                                        <input type="text" id="register-name" name="name" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="register-email">E-mailadres</label>
                                        <input type="email" id="register-email" name="email" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="register-password">Wachtwoord (minimaal 8 tekens)</label>
                                        <div class="password-container">
                                            <input type="password" id="register-password" name="password" required minlength="8">
                                            <button type="button" class="password-toggle" data-target="register-password">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                    <path d="M8 2C3.5 2 0 8 0 8s3.5 6 8 6 8-6 8-6-3.5-6-8-6zm0 10c-2.8 0-5.3-2.7-6.9-4C2.7 6.7 5.2 4 8 4s5.3 2.7 6.9 4c-1.6 1.3-4.1 4-6.9 4zm0-7a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm0 4.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="agree_terms" required>
                                            <span>Ik ga akkoord met de <a href="privacy.php" target="_blank">Voorwaarden en Privacybeleid</a></span>
                                        </label>
                                    </div>
                                    
                                    <button type="submit" class="btn-primary">Account aanmaken</button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Forgot Password Form -->
                        <div id="forgot-tab" class="tab-content <?php echo $activeTab === 'forgot' ? 'active' : ''; ?>">
                            <div class="auth-container">
                                <h2>Wachtwoord herstellen</h2>
                                
                                <?php if ($forgotSuccess): ?>
                                    <div class="alert alert-success">
                                        Als dit e-mailadres bij ons bekend is, ontvang je binnen enkele minuten een e-mail met instructies om je wachtwoord te herstellen.
                                    </div>
                                <?php elseif (!empty($forgotError)): ?>
                                    <div class="alert alert-error"><?php echo $security->escape($forgotError); ?></div>
                                <?php endif; ?>
                                
                                <form class="auth-form" method="post" action="login.php?tab=forgot">
                                    <?php echo $security->getCsrfInput(); ?>
                                    <input type="hidden" name="forgot" value="1">
                                    
                                    <div class="form-group">
                                        <label for="forgot-email">E-mailadres</label>
                                        <input type="email" id="forgot-email" name="email" required>
                                    </div>
                                    
                                    <button type="submit" class="btn-primary">Herstel wachtwoord</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="auth-info">
                        <div class="auth-info-card">
                            <h3>Waarom een account?</h3>
                            <ul>
                                <li>Toegang tot exclusieve AI-tools</li>
                                <li>Volg e-learnings op je eigen tempo</li>
                                <li>Sla aangepaste prompts en templates op</li>
                                <li>Krijg als eerste toegang tot nieuwe features</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Tab schakelfunctionaliteit
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-btn');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Update URL zonder pagina te herladen
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tabId);
                    window.history.pushState({}, '', url);
                    
                    // Update actieve tab
                    document.querySelectorAll('.tab-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    this.classList.add('active');
                    
                    // Update tab content
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    document.getElementById(tabId + '-tab').classList.add('active');
                });
            });
            
            // Wachtwoord toggle functionaliteit
            const toggleButtons = document.querySelectorAll('.password-toggle');
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const passwordInput = document.getElementById(targetId);
                    
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M2.79 4.61a.5.5 0 0 0-.792.614l.615.795C1.592 7.015 1 8 1 8s1.5 3 7 3 7-3 7-3-.5-1.018-1.613-2.02l.634-.818a.5.5 0 0 0-.778-.63A6.968 6.968 0 0 0 13.5 6.5C13.168 5.296 11.5 3 8 3 5.338 3 3.289 4.427 2.79 4.61zm1.71 1.985a6.02 6.02 0 0 1 3.5-1.595C7.033 5 6 5.233 6 6.5c0 1.11.587 1.96 1.526 2.406A7.97 7.97 0 0 1 8 9c-4.196 0-5-2-5-2s.152-.427.42-.91c.268-.483.63-.948 1.08-1.493zm8.5 0c-.6-.49-.968-.911-1.146-1.106.468.308.878.807.878 1.606 0 .819-.67 1.558-1.685 1.913 1.356.067 2.453-.467 2.453-1.413 0-.33-.138-.645-.5-1z"/></svg>';
                    } else {
                        passwordInput.type = 'password';
                        this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 2C3.5 2 0 8 0 8s3.5 6 8 6 8-6 8-6-3.5-6-8-6zm0 10c-2.8 0-5.3-2.7-6.9-4C2.7 6.7 5.2 4 8 4s5.3 2.7 6.9 4c-1.6 1.3-4.1 4-6.9 4zm0-7a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm0 4.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/></svg>';
                    }
                });
            });
        });
    </script>
</body>
</html> 