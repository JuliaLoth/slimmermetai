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
$page_title = ' $args[0].ToString().ToUpper() rofiel | Slimmer met AI';
$page_description = 'Slimmer met AI -  $args[0].ToString().ToUpper() rofiel';
?>
<!DOCTYPE html>
<html lang="nl" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mijn Profiel | Slimmer met AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="Beheer je persoonlijke profiel bij Slimmer met AI. Update je gegevens, voorkeuren en bekijk je voortgang.">
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
    <!-- Laad alle componenten -->
    <script src="<?php echo asset_url('components/ComponentsLoader.js'); ?>"></script>
</head>
<body>
    <a href="#main-content" class="skip-link">Direct naar inhoud</a>
    
    <slimmer-navbar user-logged-in user-name="Gebruiker" active-page="profiel"></slimmer-navbar>

    <slimmer-hero 
        title="Mijn Profiel" 
        subtitle="Beheer je persoonlijke gegevens en voorkeuren. Hier kun je je profiel bijwerken en je voortgang bekijken."
        background="image"
        image-url="images/hero-background.svg"
        centered>
    </slimmer-hero>

    <main id="main-content" class="profile-container" role="main">
        <div class="profile-grid">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <div class="profile-card">
                    <div class="profile-stats">
                        <div class="profile-stat">
                            <div class="profile-stat-number">3</div>
                            <div class="profile-stat-label">Actieve cursussen</div>
                            <div class="progress-container">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 30%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="profile-stat">
                            <div class="profile-stat-number">65%</div>
                            <div class="profile-stat-label">Gemiddelde voortgang</div>
                            <div class="progress-container">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 65%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="profile-stat">
                            <div class="profile-stat-number">42</div>
                            <div class="profile-stat-label">Behaalde badges</div>
                            <div class="progress-container">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 42%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="profile-stat">
                            <div class="profile-stat-number">7</div>
                            <div class="profile-stat-label">Dagen streak</div>
                            <div class="progress-container">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 70%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="profile-card">
                    <h2>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2v20M2 12h20"></path>
                        </svg>
                        Snelle links
                    </h2>
                    <div class="profile-actions">
                        <slimmer-button href="dashboard">Dashboard</slimmer-button>
                        <slimmer-button href="e-learnings">Cursussen</slimmer-button>
                        <slimmer-button href="mijn-tools">Mijn Tools</slimmer-button>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="profile-main">
                <div class="profile-section">
                    <h2>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Persoonlijke gegevens
                    </h2>
                    <form class="profile-form" id="profile-form">
                        <div class="form-group">
                            <label for="name">Naam</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">E-mailadres</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="profile-actions">
                            <button type="submit" style="display: none;" id="submit-profile-form"></button>
                            <slimmer-button type="primary" onClick="document.getElementById('submit-profile-form').click()">Wijzigingen opslaan</slimmer-button>
                            <slimmer-button type="outline" id="cancel-edit">Annuleren</slimmer-button>
                        </div>
                    </form>
                </div>

                <div class="profile-section">
                    <h2>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                        Wachtwoord wijzigen
                    </h2>
                    <form class="profile-form" id="password-form">
                        <div class="form-group">
                            <label for="current-password">Huidig wachtwoord</label>
                            <input type="password" id="current-password" name="current-password" required>
                        </div>
                        <div class="form-group">
                            <label for="new-password">Nieuw wachtwoord</label>
                            <input type="password" id="new-password" name="new-password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm-password">Bevestig nieuw wachtwoord</label>
                            <input type="password" id="confirm-password" name="confirm-password" required>
                        </div>
                        <div class="profile-actions">
                            <button type="submit" style="display: none;" id="submit-password-form"></button>
                            <slimmer-button type="primary" onClick="document.getElementById('submit-password-form').click()">Wachtwoord wijzigen</slimmer-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <!-- Begin Footer -->
<?php require_once __DIR__ . '/components/footer.php'; ?>
<!-- Einde Footer -->

    <!-- Scripts -->
    <script src="<?php echo asset_url('js/auth.js'); ?>"></script>
    <script src="<?php echo asset_url('js/main.js'); ?>"></script>
    <script src="<?php echo asset_url('js/user-stats.js'); ?>"></script>

    <script>
      document.addEventListener('DOMContentLoaded', async () => {
        // Initialiseer auth staat
        window.auth.initAuth();

        // Haal huidige gebruiker op zodat form velden zijn ingevuld
        let currentUser = null;
        try {
          const res = await window.auth.getCurrentUser();
          if (res.success) currentUser = res.user;
        } catch(e) {
          console.error('Kan gebruiker niet ophalen:', e);
        }

        if (!currentUser) {
          try {
            const stored = localStorage.getItem('user');
            if (stored) currentUser = JSON.parse(stored);
          } catch(e) {}
        }

        if (currentUser) {
          const nameInput = document.getElementById('name');
          const emailInput = document.getElementById('email');
          if (nameInput) nameInput.value = currentUser.name || '';
          if (emailInput) emailInput.value = currentUser.email || '';
        }

        /* ===== Profielgegevens wijzigen ===== */
        const profileForm = document.getElementById('profile-form');
        if (profileForm) {
          profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();

            if (!name || !email) {
              alert('Naam en e-mail zijn verplicht.');
              return;
            }

            try {
              const result = await updateProfile({ name, email });
              if (result.success) {
                // Werk localStorage en UI bij
                localStorage.setItem('user', JSON.stringify(result.user));
                localStorage.setItem('currentUser', JSON.stringify(result.user));

                // Update navbar naam in huidige pagina
                const navbar = document.querySelector('slimmer-navbar');
                if (navbar) navbar.setAttribute('user-name', result.user.name);

                alert('Profiel succesvol bijgewerkt!');
              } else {
                alert(result.message || 'Bijwerken mislukt');
              }
            } catch (err) {
              console.error('Fout bij profiel bijwerken:', err);
              alert('Er is een fout opgetreden bij het opslaan van je profiel.');
            }
          });
        }

        /* ===== Wachtwoord wijzigen ===== */
        const passwordForm = document.getElementById('password-form');
        if (passwordForm) {
          passwordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;

            if (newPassword !== confirmPassword) {
              alert('Nieuw wachtwoord en bevestiging komen niet overeen.');
              return;
            }

            try {
              const result = await changePassword(currentPassword, newPassword);
              if (result.success) {
                alert('Wachtwoord succesvol gewijzigd!');
                passwordForm.reset();
              } else {
                alert(result.message || 'Wijzigen wachtwoord mislukt');
              }
            } catch (err) {
              console.error('Fout bij wachtwoord wijzigen:', err);
              alert('Er is een fout opgetreden bij het wijzigen van je wachtwoord.');
            }
          });
        }
      });
    </script>
</body>
</html> 
