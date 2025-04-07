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
$page_title = 'Dashboard | Slimmer met AI';
$page_description = 'Slimmer met AI - Dashboard';
?>
<!DOCTYPE html>
<html lang="nl" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Slimmer met AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="Beheer je persoonlijke dashboard bij Slimmer met AI. Toegang tot je tools, cursussen en account informatie.">
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
    <!-- Laad alle componenten -->
    <script src="<?php echo asset_url('components/ComponentsLoader.js'); ?>"></script>
</head>
<body>
    <a href="#main-content" class="skip-link">Direct naar inhoud</a>
    
    <slimmer-navbar user-logged-in user-name="Gebruiker" active-page="dashboard"></slimmer-navbar>

    <slimmer-hero 
        title="Welkom, Gebruiker!" 
        subtitle="Dit is je persoonlijke dashboard bij Slimmer met AI. Hier vind je al je cursussen, trainingen en andere AI-tools."
        background="image"
        image-url="images/hero background def.svg"
        centered>
    </slimmer-hero>

    <main id="main-content" class="dashboard-container" role="main">
        <!-- Admin sectie (alleen zichtbaar voor admins) -->
        <div id="admin-section" class="admin-section" style="display: none;">
            <h2>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 4.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"></path>
                    <path d="M19.5 19.5h-15"></path>
                    <path d="M17 14h.01"></path>
                    <path d="M12.01 14H12"></path>
                    <path d="M7.01 14H7"></path>
                    <path d="M17 19c.2-5.4 1.8-10 4.5-14"></path>
                    <path d="M6.5 5C8.5 9 9.5 14 7 19"></path>
                    <path d="M12 5v9"></path>
                </svg>
                Beheerdersfuncties
            </h2>
            <div class="admin-actions">
                <a href="admin/users">Gebruikersbeheer</a>
                <a href="admin/courses">Cursussen beheren</a>
                <a href="admin/analytics">Analytics bekijken</a>
                <a href="admin/settings">Site-instellingen</a>
            </div>
        </div>
        
        <!-- Statistieken rij -->
        <slimmer-user-stats></slimmer-user-stats>
        
        <!-- Dashboard kaarten -->
        <div class="dashboard-grid">
            <!-- Mijn tools kaart -->
            <slimmer-card title="Mijn tools">
                <div class="card-icon" slot="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                </div>
                <p>Bekijk en gebruik de AI-tools die je hebt aangeschaft om je werk efficiënter te maken.</p>
                <div slot="actions">
                    <slimmer-button href="mijn-tools" type="primary">Naar mijn tools</slimmer-button>
                </div>
            </slimmer-card>
            
            <!-- Mijn cursussen kaart -->
            <slimmer-card title="Mijn cursussen">
                <div class="card-icon" slot="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </div>
                <p>Volg je gekochte cursussen en houd je voortgang bij. Leer op je eigen tempo nieuwe AI-vaardigheden.</p>
                <div slot="actions">
                    <slimmer-button href="mijn-cursussen" type="primary">Naar mijn cursussen</slimmer-button>
                </div>
            </slimmer-card>
            
            <!-- Profiel kaart -->
            <slimmer-card title="Mijn profiel">
                <div class="card-icon" slot="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <p>Beheer je persoonlijke gegevens, abonnementen en voorkeuren. Update je profiel om je ervaring te verbeteren.</p>
                <div slot="actions">
                    <slimmer-button href="profiel" type="primary">Bewerk profiel</slimmer-button>
                </div>
            </slimmer-card>
            
            <!-- Support kaart -->
            <slimmer-card title="Support">
                <div class="card-icon" slot="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <path d="M12 17h.01"></path>
                    </svg>
                </div>
                <p>Heb je hulp nodig? Contact onze supportafdeling voor vragen over je account, cursussen of technische problemen.</p>
                <div slot="actions">
                    <slimmer-button href="support" type="primary">Contact support</slimmer-button>
                </div>
            </slimmer-card>
        </div>
    </main>

    <slimmer-footer></slimmer-footer>

    <!-- Scripts -->
    <script src="<?php echo asset_url('js/main.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gebruikersnaam instellen
            const user = {
                name: 'Gebruiker',
                email: 'gebruiker@example.com',
                role: 'user'
            };
            
            // Check of gebruiker admin is
            if (user.role === 'admin') {
                document.getElementById('admin-section').style.display = 'block';
            }
            
            // Welkomstbericht bijwerken
            document.getElementById('user-name').textContent = user.name;
            
            // Uitloggen via welcome card button
            document.getElementById('logout-btn').addEventListener('click', function() {
                window.location.href = 'test-login.php';
            });
            
            // Mobiel menu toggle
            const mobileMenuBtn = document.querySelector('.mobile-menu-button');
            const navLinks = document.querySelector('.nav-links');
            
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    navLinks.classList.toggle('active');
                    mobileMenuBtn.classList.toggle('active');
                });
            }
            
            // Dashboard kaarten animatie
            const dashboardCards = document.querySelectorAll('.dashboard-card');
            
            // Maak alle kaarten zichtbaar na 100ms
            setTimeout(() => {
                dashboardCards.forEach(card => {
                    card.classList.add('visible');
                });
            }, 100);
        });
    </script>
</body>
</html> 

