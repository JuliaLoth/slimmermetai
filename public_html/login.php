<?php
require_once dirname(__DIR__) . '/includes/init.php'; // Pad naar init.php buiten public_html

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
$page_title = 'Login | Slimmer met AI';
$page_description = 'Slimmer met AI - Login';
?>
<!DOCTYPE html>
<html lang="nl" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account | Slimmer met AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="Log in op je account om toegang te krijgen tot je AI-tools en e-learnings.">
    <meta name="google-signin-client_id" content="625906341722-2eohq5a55sl4a8511h6s20dbsicuknku.apps.googleusercontent.com">
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
    <!-- Laad alle componenten -->
    <script src="<?php echo asset_url('components/ComponentsLoader.js'); ?>"></script>
    <script src="https://apis.google.com/js/platform.js" async defer></script>
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
            padding: 3rem;
            margin-bottom: 2rem;
            width: 100%;
        }
        
        .auth-row {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 2rem;
            align-items: flex-start;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .auth-form .form-group {
            margin-bottom: 1.8rem;
        }
        
        .auth-form .form-group label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 500;
            font-size: 1.05rem;
            color: #374151;
        }
        
        .auth-form input {
            width: 100%;
            padding: 0.85rem 1.2rem;
            border: 1px solid #e5e7eb;
            background-color: #f9fafb;
            border-radius: 8px;
            font-size: 1.05rem;
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
            padding: 0.9rem 1.5rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            width: 100%;
            font-size: 1.1rem;
            margin-top: 0.5rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(88, 82, 242, 0.3);
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
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.05rem;
        }
        
        .social-btn.google {
            background: linear-gradient(45deg, #5852f2, #8e88ff);
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
        
        .social-btn svg {
            margin-right: 0.75rem;
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
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        
        .checkbox-group input {
            width: auto;
            margin-right: 0.5rem;
        }
        
        .forgot-link,
        .auth-switch a {
            color: #5852f2;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .forgot-link:hover,
        .auth-switch a:hover {
            color: #403aa0;
            text-decoration: underline;
        }
        
        .auth-switch {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }
        
        .auth-info-card {
            background-color: #f9fafb;
            border-radius: 12px;
            padding: 3rem;
            height: auto;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
            margin-top: 3.5rem;
            position: sticky;
            top: 2rem;
        }
        
        .auth-info-card h3 {
            color: #1f2937;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            position: relative;
        }
        
        .auth-info-card h3::after {
            content: "";
            position: absolute;
            bottom: -0.5rem;
            left: 0;
            width: 3rem;
            height: 3px;
            background: linear-gradient(45deg, #5852f2, #8e88ff);
            border-radius: 3px;
        }
        
        .benefits-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .benefits-list li {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
            color: #4b5563;
        }
        
        .benefits-list li svg {
            flex-shrink: 0;
            width: 1.5rem;
            height: 1.5rem;
            margin-right: 1rem;
            color: #5852f2;
        }
        
        .auth-section {
            padding: 4rem 0;
            background-color: #f9fafb;
        }
        
        @media (max-width: 768px) {
            .auth-container {
                padding: 2rem;
            }
            
            .auth-row {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .auth-info {
                margin-top: 2rem;
            }
            
            .auth-info-card {
                margin-top: 0;
                position: static;
            }
            
            .auth-form input {
                font-size: 1rem;
                padding: 0.75rem 1rem;
            }
        }
        
        /* Account dropdown styling */
        .account-dropdown {
            position: relative;
            margin-right: 15px;
        }
        
        .account-dropdown-btn {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 6px;
            background-color: transparent;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            color: #374151;
            font-weight: 500;
        }
        
        .account-dropdown-btn:hover {
            background-color: rgba(88, 82, 242, 0.05);
            border-color: #5852f2;
        }
        
        .account-dropdown-btn svg {
            margin-left: 5px;
            transition: transform 0.3s ease;
        }
        
        .account-dropdown.open .account-dropdown-btn svg {
            transform: rotate(180deg);
        }
        
        .account-dropdown-content {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 5px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            display: none;
            z-index: 100;
            overflow: hidden;
            border: 1px solid rgba(229, 231, 235, 0.6);
        }
        
        .account-dropdown.open .account-dropdown-content {
            display: block;
            animation: dropdownFadeIn 0.3s ease;
        }
        
        .account-dropdown-content a {
            display: block;
            padding: 12px 20px;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s ease;
            font-weight: 500;
            border-bottom: 1px solid rgba(229, 231, 235, 0.6);
        }
        
        .account-dropdown-content a:last-child {
            border-bottom: none;
        }
        
        .account-dropdown-content a:hover {
            background-color: rgba(88, 82, 242, 0.05);
            color: #5852f2;
            padding-left: 25px;
        }
        
        .account-dropdown-content a.active {
            background-color: rgba(88, 82, 242, 0.1);
            color: #5852f2;
            border-left: 3px solid #5852f2;
            padding-left: 17px;
        }
        
        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Formulier styling */
        .auth-form {
            position: relative;
        }
        
        .form-group {
            margin-bottom: 1.8rem;
            position: relative;
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: flex-start;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 10px;
            margin-top: 2px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .checkbox-group label {
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 0;
        }
        
        .checkbox-group label a {
            color: #5852f2;
            text-decoration: none;
        }
        
        .checkbox-group label a:hover {
            text-decoration: underline;
        }
        
        /* Success message styling */
        .success-message {
            background-color: #ecfdf5;
            color: #047857;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            text-align: center;
            font-weight: 500;
            border: 1px solid #d1fae5;
            animation: fadeIn 0.3s ease-out;
        }
        
        /* Tab content transition */
        .tab-content {
            transition: opacity 0.3s ease, transform 0.3s ease;
            opacity: 0;
            transform: translateY(10px);
        }
        
        .tab-content.active {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Button hover styling */
        .btn {
            position: relative;
            overflow: hidden;
        }
        
        .btn:after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 120%;
            height: 0;
            padding-bottom: 120%;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translate(-50%, -50%) scale(0);
            opacity: 0;
            transition: transform 0.6s, opacity 0.6s;
        }
        
        .btn:hover:after {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
        }
        
        .btn:active:after {
            transform: translate(-50%, -50%) scale(0.9);
        }
        
        /* Login pagina specifieke styling */
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .social-login {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin: 1.5rem 0;
            text-align: center;
        }
        
        .social-divider {
            position: relative;
            text-align: center;
            margin: 1.5rem 0;
        }
        
        .social-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background-color: #e5e7eb;
        }
        
        .social-divider span {
            position: relative;
            background-color: white;
            padding: 0 1rem;
            color: #6b7280;
        }
        
        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background-color: white;
            color: #4b5563;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
        }
        
        .social-btn:hover {
            background-color: #f9fafb;
            border-color: #d1d5db;
        }
        
        .social-btn svg {
            width: 20px;
            height: 20px;
        }
        
        .account-switch {
            margin-top: 1.5rem;
            text-align: center;
            color: #6b7280;
        }
        
        .account-switch a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .account-switch a:hover {
            color: var(--primary-hover);
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-group label {
            font-weight: 500;
            color: #4b5563;
        }
        
        .form-group input {
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(88, 82, 242, 0.1);
        }
        
        .password-field {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 5px;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        .btn-login {
            width: 100%;
            background: linear-gradient(45deg, var(--primary-color), var(--primary-hover));
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(88, 82, 242, 0.25);
        }
        
        .login-container {
            max-width: 500px;
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
        .login-btn {
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
        .login-btn:hover {
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
        .google-btn {
            width: 100%;
            padding: 12px;
            background-color: #fff;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }
        .google-btn:hover {
            background-color: #f5f5f5;
        }
        .google-icon {
            margin-right: 10px;
            width: 18px;
            height: 18px;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .forgot-password {
            display: block;
            text-align: right;
            margin-top: 10px;
            color: var(--primary-color);
            text-decoration: none;
        }
        .forgot-password:hover {
            text-decoration: underline;
        }
    </style>
    <noscript>
        <style>
            .preloader { display: none !important; }
        </style>
    </noscript>
</head>
<body>
    <div class="preloader">
        <div class="loader"></div>
    </div>
    
    <a href="#main-content" class="skip-link">Direct naar inhoud</a>
    
    <!-- Vervang de oude header door het nieuwe AccountNavbar component -->
    <slimmer-account-navbar active-page="login" cart-count="0"></slimmer-account-navbar>
    
    <main id="main-content" role="main">
        <!-- Hero balk toevoegen zoals andere pagina's -->
        <slimmer-hero 
            title="Jouw Slimmer met AI portaal" 
            subtitle="Log in op je bestaande account of maak een nieuwe aan voor toegang tot je gekochte tools en e-learnings."
            background="image"
            image-url="images/hero background def.svg"
            centered>
        </slimmer-hero>
        
        <section class="auth-section">
            <div class="container">
                <div class="auth-row">
                    <div class="auth-container">
                        <div class="auth-tabs">
                            <button class="tab-btn active" id="login-tab" data-tab="login">Inloggen</button>
                            <button class="tab-btn" id="signup-tab" data-tab="signup">Aanmelden</button>
                        </div>
                        
                        <div class="tab-content active" id="login-content">
                            <form id="login-form" class="auth-form">
                                <div class="form-group">
                                    <label for="login-email">E-mailadres</label>
                                    <input type="email" id="login-email" required placeholder="jouw@email.nl">
                                </div>
                                <div class="form-group">
                                    <label for="login-password">Wachtwoord</label>
                                    <div class="password-container">
                                        <input type="password" id="login-password" required placeholder="Jouw wachtwoord">
                                        <button type="button" class="password-toggle" aria-label="Wachtwoord tonen/verbergen">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-options">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="remember-me">
                                        <label for="remember-me">Onthoud mij</label>
                                    </div>
                                    <a href="#" class="forgot-link">Wachtwoord vergeten?</a>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Inloggen</button>
                                
                                <div class="auth-divider">
                                    <span>Of</span>
                                </div>
                                
                                <div class="social-auth">
                                    <button type="button" class="social-btn google" id="google-signin-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                            <path fill="currentColor" d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/>
                                        </svg>
                                        Inloggen met Google
                                    </button>
                                </div>
                                
                                <div class="auth-switch">
                                    Nog geen account? <a href="#" id="switch-to-signup">Meld je aan</a>
                                </div>
                            </form>
                        </div>
                        
                        <div class="tab-content" id="signup-content">
                            <form id="signup-form" class="auth-form">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="signup-name">Naam</label>
                                        <input type="text" id="signup-name" required placeholder="Jouw volledige naam">
                                    </div>
                                    <div class="form-group">
                                        <label for="signup-email">E-mailadres</label>
                                        <input type="email" id="signup-email" required placeholder="jouw@email.nl">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="signup-password">Wachtwoord</label>
                                    <div class="password-container">
                                        <input type="password" id="signup-password" required placeholder="Minimaal 8 tekens">
                                        <button type="button" class="password-toggle" aria-label="Wachtwoord tonen/verbergen">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="terms" required>
                                        <label for="terms">Ik ga akkoord met de <a href="#">gebruiksvoorwaarden</a> en <a href="#">privacybeleid</a></label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-block">Aanmelden</button>
                                
                                <div class="auth-divider">
                                    <span>Of</span>
                                </div>
                                
                                <div class="social-auth">
                                    <button type="button" class="social-btn google" id="google-signup-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                            <path fill="currentColor" d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/>
                                        </svg>
                                        Aanmelden met Google
                                    </button>
                                </div>
                                
                                <div class="auth-switch">
                                    Al een account? <a href="#" id="switch-to-login">Log in</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="auth-info">
                        <div class="auth-info-card">
                            <h3>Voordelen van een account</h3>
                            <ul class="benefits-list">
                                <li>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    <span>Toegang tot je gekochte AI-tools</span>
                                </li>
                                <li>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    <span>Volg al je e-learnings</span>
                                </li>
                                <li>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    <span>Bewaar je favoriete content</span>
                                </li>
                                <li>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    <span>Ontvang exclusieve updates</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <slimmer-footer></slimmer-footer>

    <!-- Core Scripts -->
    <script src="<?php echo asset_url('js/main.js'); ?>"></script>
    <script src="<?php echo asset_url('js/auth.js'); ?>"></script>
    <script src="<?php echo asset_url('js/cart.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching functionaliteit
            const loginTab = document.getElementById('login-tab');
            const signupTab = document.getElementById('signup-tab');
            const loginContent = document.getElementById('login-content');
            const signupContent = document.getElementById('signup-content');
            const switchToSignup = document.getElementById('switch-to-signup');
            const switchToLogin = document.getElementById('switch-to-login');
            
            // Google Sign-in functionaliteit
            const googleSigninBtn = document.getElementById('google-signin-btn');
            const googleSignupBtn = document.getElementById('google-signup-btn');
            
            // Functie voor Google Sign-in
            window.handleGoogleSignIn = function(googleUser) {
                // Hier krijg je de Google gebruikersgegevens
                const id_token = googleUser.getAuthResponse().id_token;
                
                // Token naar backend sturen
                fetch('https://slimmermetai.com/api/auth/google/token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ token: id_token })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Token en gebruikersgegevens opslaan
                        localStorage.setItem('token', data.token);
                        localStorage.setItem('user', JSON.stringify(data.user));
                        
                        // Doorverwijzen naar dashboard
                        window.location.href = '/dashboard';
                    } else {
                        console.error('Google inloggen mislukt:', data.message);
                        // Hier kun je een foutmelding tonen
                    }
                })
                .catch(error => {
                    console.error('Fout bij Google inloggen:', error);
                });
            };
            
            // Account dropdown links bijwerken
            const dropdownLoginLink = document.querySelector('.account-dropdown-content a[href="login"]');
            const dropdownSignupLink = document.querySelector('.account-dropdown-content a[href="login.php#signup"]');
            
            // Functie om de actieve link in het dropdown menu bij te werken
            function updateActiveDropdownLink(isLoginActive) {
                if (dropdownLoginLink && dropdownSignupLink) {
                    if (isLoginActive) {
                        dropdownLoginLink.classList.add('active');
                        dropdownSignupLink.classList.remove('active');
                    } else {
                        dropdownLoginLink.classList.remove('active');
                        dropdownSignupLink.classList.add('active');
                    }
                }
            }
            
            // Functie om tussen tabs te wisselen met animatie
            function switchTab(activateTab, activateContent, deactivateTab, deactivateContent) {
                if (!activateContent || !deactivateContent) return;
                
                // Voeg fade-out animatie toe aan huidige content
                deactivateContent.style.opacity = 0;
                
                setTimeout(() => {
                    // Switch de active classes
                    activateTab.classList.add('active');
                    deactivateTab.classList.remove('active');
                    
                    // Verberg huidige content en toon nieuwe content
                    deactivateContent.classList.remove('active');
                    activateContent.classList.add('active');
                    
                    // Reset opacity voor volgende animatie
                    deactivateContent.style.opacity = '';
                    
                    // Fade-in animatie voor nieuwe content
                    activateContent.style.opacity = 0;
                    setTimeout(() => {
                        activateContent.style.opacity = 1;
                    }, 50);
                    
                    // Update actieve dropdown link
                    updateActiveDropdownLink(activateTab === loginTab);
                }, 200);
            }
            
            // Event listeners voor tab knoppen
            if (loginTab) {
                loginTab.addEventListener('click', function() {
                    if (!this.classList.contains('active')) {
                        switchTab(loginTab, loginContent, signupTab, signupContent);
                        history.replaceState(null, null, ' '); // Verwijder hash uit URL
                    }
                });
            }
            
            if (signupTab) {
                signupTab.addEventListener('click', function() {
                    if (!this.classList.contains('active')) {
                        switchTab(signupTab, signupContent, loginTab, loginContent);
                        history.replaceState(null, null, '#signup'); // Voeg hash toe aan URL
                    }
                });
            }
            
            // Event listeners voor de "switch" links
            if (switchToSignup) {
                switchToSignup.addEventListener('click', function(e) {
                    e.preventDefault();
                    switchTab(signupTab, signupContent, loginTab, loginContent);
                    history.replaceState(null, null, '#signup'); // Voeg hash toe aan URL
                });
            }
            
            if (switchToLogin) {
                switchToLogin.addEventListener('click', function(e) {
                    e.preventDefault();
                    switchTab(loginTab, loginContent, signupTab, signupContent);
                    history.replaceState(null, null, ' '); // Verwijder hash uit URL
                });
            }
            
            // Wachtwoord zichtbaarheid toggle
            const passwordToggles = document.querySelectorAll('.password-toggle');
            
            passwordToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const passwordField = this.previousElementSibling;
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    
                    // Update icon (optioneel)
                    const eyeIcon = this.querySelector('svg');
                    if (type === 'text') {
                        eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
                    } else {
                        eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
                    }
                });
            });
            
            // Formulier validatie en verwerking
            const loginForm = document.getElementById('login-form');
            const signupForm = document.getElementById('signup-form');
            
            function showFeedback(form, message, isError = false) {
                let feedback = form.querySelector('.form-feedback');
                
                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'form-feedback';
                    form.appendChild(feedback);
                }
                
                feedback.textContent = message;
                feedback.className = isError ? 'form-feedback error' : 'form-feedback success';
                feedback.style.display = 'block';
                
                if (!isError) {
                    setTimeout(() => {
                        feedback.style.opacity = 0;
                        setTimeout(() => {
                            feedback.style.display = 'none';
                            feedback.style.opacity = 1;
                        }, 300);
                    }, 2000);
                }
            }
            
            if (loginForm) {
                loginForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const email = this.querySelector('input[name="email"]').value;
                    const password = this.querySelector('input[name="password"]').value;
                    
                    if (!email || !password) {
                        showFeedback(this, 'Vul alstublieft alle velden in.', true);
                        return;
                    }
                    
                    // Toon laad feedback
                    showFeedback(this, 'Inloggen...');
                    
                    try {
                        // Gebruik de login functie uit auth.js
                        const result = await login(email, password);
                        
                        if (result.success) {
                            // Controleer of er een returnUrl is (voor e-learning platform)
                            const urlParams = new URLSearchParams(window.location.search);
                            const returnUrl = urlParams.get('returnUrl');
                            
                            if (returnUrl) {
                                window.location.href = returnUrl;
                            } else {
                                window.location.href = 'account.php';
                            }
                        } else {
                            // Toon foutmelding
                            showFeedback(this, result.message || 'Inloggen mislukt', true);
                        }
                    } catch (error) {
                        console.error('Login error:', error);
                        showFeedback(this, 'Er is een fout opgetreden bij het inloggen. Probeer het later opnieuw.', true);
                    }
                });
            }
            
            if (signupForm) {
                signupForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const name = this.querySelector('input[name="name"]').value;
                    const email = this.querySelector('input[name="email"]').value;
                    const password = this.querySelector('input[name="password"]').value;
                    const confirmPassword = this.querySelector('input[name="confirm-password"]').value;
                    
                    if (!name || !email || !password || !confirmPassword) {
                        showFeedback(this, 'Vul alstublieft alle velden in.', true);
                        return;
                    }
                    
                    if (password !== confirmPassword) {
                        showFeedback(this, 'Wachtwoorden komen niet overeen.', true);
                        return;
                    }
                    
                    // Toon laad feedback
                    showFeedback(this, 'Account aanmaken...');
                    
                    // Hier zou normaal een API call naar de server zijn
                    // Voor deze demo simuleren we een succesvolle registratie
                    setTimeout(() => {
                        // Sla de login status op in localStorage
                        localStorage.setItem('loggedIn', 'true');
                        localStorage.setItem('userEmail', email);
                        localStorage.setItem('userName', name);
                        
                        // Controleer of er een returnUrl is (voor e-learning platform)
                        const urlParams = new URLSearchParams(window.location.search);
                        const returnUrl = urlParams.get('returnUrl');
                        
                        if (returnUrl) {
                            window.location.href = returnUrl;
                        } else {
                            window.location.href = 'account.php';
                        }
                    }, 1000);
                });
            }
            
            // Check login status
            const isLoggedIn = localStorage.getItem('loggedIn') === 'true';
            if (isLoggedIn) {
                // Gebruiker is al ingelogd, toon welkomstbericht
                const userName = localStorage.getItem('userName') || 'gebruiker';
                const userEmail = localStorage.getItem('userEmail');
                
                const loginContainer = document.querySelector('.auth-container');
                if (loginContainer) {
                    loginContainer.innerHTML = `
                        <div class="logged-in-message">
                            <h2>Welkom terug, ${userName}!</h2>
                            <p>Je bent ingelogd met ${userEmail}.</p>
                            <div class="action-buttons">
                                <a href="account" class="btn btn-primary">Ga naar je account</a>
                                <a href="e-learning/index" class="btn btn-outline">Ga naar E-Learning</a>
                                <button id="logout-btn" class="btn btn-outline">Uitloggen</button>
                            </div>
                        </div>
                    `;
                    
                    // Voeg uitlog functionaliteit toe
                    const logoutBtn = document.getElementById('logout-btn');
                    if (logoutBtn) {
                        logoutBtn.addEventListener('click', function() {
                            localStorage.removeItem('loggedIn');
                            localStorage.removeItem('userEmail');
                            localStorage.removeItem('userName');
                            window.location.reload();
                        });
                    }
                }
            }
            
            // Check URL hash bij laden
            if (window.location.hash === '#signup' && signupTab) {
                switchTab(signupTab, signupContent, loginTab, loginContent);
            }
            
            // Controleer of er een returnUrl is en toon een bericht
            const urlParams = new URLSearchParams(window.location.search);
            const returnUrl = urlParams.get('returnUrl');
            
            if (returnUrl && returnUrl.includes('/e-learning/')) {
                const loginHeader = document.querySelector('.auth-section h1');
                if (loginHeader) {
                    const notificationDiv = document.createElement('div');
                    notificationDiv.className = 'return-notification';
                    notificationDiv.innerHTML = `
                        <p>Log in om toegang te krijgen tot het e-learning platform.</p>
                    `;
                    notificationDiv.style.cssText = `
                        padding: 12px 16px;
                        background-color: #f8f9fa;
                        border-left: 4px solid #4A6CF7;
                        margin-bottom: 20px;
                        border-radius: 4px;
                        font-size: 14px;
                    `;
                    loginHeader.insertAdjacentElement('afterend', notificationDiv);
                }
            }
        });
    </script>
    
    <!-- Google API Script - Dit moet na onze eigen scripts komen -->
    <script src="https://apis.google.com/js/platform.js" async defer></script>

    <script>
      // Functie die wordt aangeroepen na succesvolle Google login
      function onSignIn(googleUser) {
        // Belangrijk: Verstuur NOOIT het volledige googleUser object naar je backend!
        // Haal alleen het ID Token op.
        var id_token = googleUser.getAuthResponse().id_token;
        console.log("Google ID Token: " + id_token); // Voor debuggen

        // Maak een POST request naar je backend API endpoint
        // Vervang '/api/google_auth.php' door je daadwerkelijke API endpoint
        // !! BELANGRIJK: Controleer of dit het juiste pad is naar je PHP backend script !!
        fetch('/api/google_auth.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ token: id_token }),
        })
        .then(response => response.json())
        .then(data => {
          console.log('Backend response:', data);
          if (data.success) {
            // Login succesvol, stuur gebruiker door of update UI
            // Voorbeeld: Stuur door naar het dashboard
            // !! BELANGRIJK: Controleer of '/dashboard.php' de juiste pagina is na login !!
            window.location.href = '/dashboard.php'; // Pas dit aan naar je doelpagina
          } else {
            // Toon foutmelding van de backend
            console.error('Google login backend error:', data.message);
            // Optioneel: Toon een bericht aan de gebruiker
            alert('Login met Google mislukt: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error sending token to backend:', error);
          alert('Er is een fout opgetreden tijdens het communiceren met de server.');
        });
      }

      // Functie om de Google API te initialiseren
      function startGoogleAuth() {
        gapi.load('auth2', function() {
          // Initialiseer de GoogleAuth library
          var auth2 = gapi.auth2.init({
            client_id: '625906341722-2eohq5a55sl4a8511h6s20dbsicuknku.apps.googleusercontent.com', // Jouw Client ID hier
            // cookiepolicy: 'single_host_origin', // Optioneel
            // scope: 'profile email' // Optioneel: vraag extra permissies
          });

          // Koppel de login functie aan de knoppen
          attachSignin(document.getElementById('google-signin-btn'), auth2);
          attachSignin(document.getElementById('google-signup-btn'), auth2); // Signup gebruikt dezelfde flow
        });
      }

      // Functie om de sign-in logica aan een knop te koppelen
      function attachSignin(element, auth2) {
        if (element) {
            console.log('Attaching sign-in to:', element.id);
            auth2.attachClickHandler(element, {},
                onSignIn, // Functie bij succes
                function(error) { // Functie bij fout
                  console.error('Google Sign-In Error:', JSON.stringify(error, undefined, 2));
                  alert('Fout bij Google login: ' + JSON.stringify(error.error || error));
                });
         } else {
            console.warn('Button element not found for attaching Google sign-in');
         }
      }

      // Wacht tot de Google API bibliotheek (platform.js) volledig is geladen
      // en roep dan de initialisatiefunctie aan.
      // Omdat platform.js async defer wordt geladen, moeten we wachten.
      // Een eenvoudige check:
      function checkGapiReady() {
        if (typeof gapi !== 'undefined' && gapi.load) {
            console.log('gapi is ready, calling startGoogleAuth()');
            startGoogleAuth();
        } else {
            console.log('gapi not ready yet, retrying...');
            setTimeout(checkGapiReady, 100); // Probeer opnieuw na 100ms
        }
      }

      // Start de check wanneer de DOM klaar is
      document.addEventListener('DOMContentLoaded', checkGapiReady);

    </script>
</body>
</html>


