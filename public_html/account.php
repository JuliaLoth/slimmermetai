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
$page_title = 'Account | Slimmer met AI';
$page_description = 'Slimmer met AI - Account';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mijn Account | Slimmer met AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="Beheer je account en toegang tot je AI-tools en e-learnings.">
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
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
    
    <!-- Begin Header -->
<?php include_public('header.php'); ?>
<!-- Einde Header -->
    
    <main id="main-content" role="main">
        <section class="account-section">
            <div class="container">
                <div class="account-header">
                    <div class="account-profile">
                        <div class="profile-avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="profile-info">
                            <h1>Welkom, <span class="user-name">Gebruiker</span></h1>
                            <p class="user-email">gebruiker@voorbeeld.nl</p>
                        </div>
                    </div>
                    <div class="account-actions">
                        <button type="button" class="btn btn-outline">Profiel bewerken</button>
                    </div>
                </div>
                
                <div class="account-tabs">
                    <button type="button" class="tab-button active" data-tab="tools">Mijn Tools</button>
                    <button type="button" class="tab-button" data-tab="courses">Mijn E-Learnings</button>
                    <button type="button" class="tab-button" data-tab="settings">Instellingen</button>
                </div>
                
                <div class="account-content">
                    <!-- Tools tab -->
                    <div class="tab-content active" id="tools-tab">
                        <h2>Mijn Tools</h2>
                        <div class="purchased-items">
                            <div class="purchased-item">
                                <div class="item-image">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                        <polyline points="22,6 12,13 2,6"></polyline>
                                    </svg>
                                </div>
                                <div class="item-info">
                                    <h3>Email Assistent</h3>
                                    <p>Schrijf professionele emails sneller dan ooit</p>
                                    <div class="item-meta">
                                        <span class="item-date">Aangeschaft op: 15 maart 2025</span>
                                    </div>
                                </div>
                                <div class="item-actions">
                                    <a href="#" class="btn btn-primary">Starten</a>
                                </div>
                            </div>
                            
                            <div class="purchased-item">
                                <div class="item-image">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                </div>
                                <div class="item-info">
                                    <h3>Rapport Generator</h3>
                                    <p>Maak gedetailleerde rapporten in enkele minuten</p>
                                    <div class="item-meta">
                                        <span class="item-date">Aangeschaft op: 10 maart 2025</span>
                                    </div>
                                </div>
                                <div class="item-actions">
                                    <a href="#" class="btn btn-primary">Starten</a>
                                </div>
                            </div>
                            
                            <div class="purchased-item">
                                <div class="item-image">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                    </svg>
                                </div>
                                <div class="item-info">
                                    <h3>Meeting Summarizer</h3>
                                    <p>Zet gesprekken automatisch om naar gestructureerde samenvattingen</p>
                                    <div class="item-meta">
                                        <span class="item-date">Aangeschaft op: 8 maart 2025</span>
                                    </div>
                                </div>
                                <div class="item-actions">
                                    <a href="#" class="btn btn-primary">Starten</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- E-Learnings tab -->
                    <div class="tab-content" id="courses-tab">
                        <h2>Mijn E-Learnings</h2>
                        <div class="purchased-items">
                            <div class="purchased-item">
                                <div class="item-image">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                    </svg>
                                </div>
                                <div class="item-info">
                                    <h3>AI Basics</h3>
                                    <p>Begrijp de fundamenten van kunstmatige intelligentie</p>
                                    <div class="item-meta">
                                        <span class="item-date">Aangeschaft op: 18 maart 2025</span>
                                        <span class="item-progress">Voortgang: 60%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: 60%"></div>
                                    </div>
                                </div>
                                <div class="item-actions">
                                    <a href="#" class="btn btn-primary">Doorgaan</a>
                                </div>
                            </div>
                            
                            <div class="purchased-item">
                                <div class="item-image">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <circle cx="12" cy="12" r="4"></circle>
                                        <line x1="4.93" y1="4.93" x2="9.17" y2="9.17"></line>
                                        <line x1="14.83" y1="14.83" x2="19.07" y2="19.07"></line>
                                        <line x1="14.83" y1="9.17" x2="19.07" y2="4.93"></line>
                                        <line x1="14.83" y1="9.17" x2="18.36" y2="5.64"></line>
                                        <line x1="4.93" y1="19.07" x2="9.17" y2="14.83"></line>
                                    </svg>
                                </div>
                                <div class="item-info">
                                    <h3>Prompt Engineering</h3>
                                    <p>Leer effectieve prompts schrijven voor AI systemen</p>
                                    <div class="item-meta">
                                        <span class="item-date">Aangeschaft op: 5 maart 2025</span>
                                        <span class="item-progress">Voortgang: 30%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: 30%"></div>
                                    </div>
                                </div>
                                <div class="item-actions">
                                    <a href="#" class="btn btn-primary">Doorgaan</a>
                                </div>
                            </div>
                            
                            <div class="purchased-item">
                                <div class="item-image">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="3" y1="9" x2="21" y2="9"></line>
                                        <line x1="9" y1="21" x2="9" y2="9"></line>
                                    </svg>
                                </div>
                                <div class="item-info">
                                    <h3>Workflow Automatisering</h3>
                                    <p>Automatiseer dagelijkse taken met AI-tools</p>
                                    <div class="item-meta">
                                        <span class="item-date">Aangeschaft op: 2 maart 2025</span>
                                        <span class="item-progress">Voortgang: 85%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: 85%"></div>
                                    </div>
                                </div>
                                <div class="item-actions">
                                    <a href="#" class="btn btn-primary">Doorgaan</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Settings tab -->
                    <div class="tab-content" id="settings-tab">
                        <h2>Account Instellingen</h2>
                        <div class="settings-form">
                            <form id="account-settings-form">
                                <div class="form-group">
                                    <label for="user-name">Naam</label>
                                    <input type="text" id="user-name" value="Gebruiker">
                                </div>
                                <div class="form-group">
                                    <label for="user-email">E-mailadres</label>
                                    <input type="email" id="user-email" value="gebruiker@voorbeeld.nl">
                                </div>
                                <div class="form-group">
                                    <label for="current-password">Huidig wachtwoord</label>
                                    <div class="password-container">
                                        <input type="password" id="current-password">
                                        <button type="button" class="password-toggle" aria-label="Wachtwoord tonen/verbergen">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="new-password">Nieuw wachtwoord</label>
                                    <div class="password-container">
                                        <input type="password" id="new-password">
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
                                        <input type="checkbox" id="newsletter" checked>
                                        <label for="newsletter">Ontvang onze nieuwsbrief</label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Wijzigingen opslaan</button>
                            </form>
                            
                            <div class="danger-zone">
                                <h3>Gevarenzone</h3>
                                <button type="button" class="btn btn-danger">Account verwijderen</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
<slimmer-footer></slimmer-footer>

    <script src="<?php echo asset_url('js/main.js'); ?>"></script>
</body>
</html> 


