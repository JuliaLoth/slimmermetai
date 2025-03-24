<?php
// Start de sessie als die nog niet is gestart
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Laad configuratiebestand
require_once 'includes/config.php';

// Laad benodigde klassen
require_once 'includes/Database.php';
require_once 'includes/Auth.php';
require_once 'includes/Security.php';

// Initialiseer klassen
$db = Database::getInstance();
$auth = Auth::getInstance();
$security = Security::getInstance();

// Controleer of de gebruiker is ingelogd
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Controleer sessie integriteit
$security->checkSessionIntegrity();

// Haal de huidige gebruiker op
$user = $auth->getCurrentUser();

// Initialiseer variabelen
$successMessage = '';
$errorMessage = '';

// Verwerk het bijwerken van profiel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Controleer CSRF token
    $security->validateCSRFToken();
    
    // Valideer en saniteer input
    $name = $security->sanitizeInput($_POST['name']);
    $email = $security->sanitizeInput($_POST['email']);
    $bio = $security->sanitizeInput($_POST['bio']);
    
    // Validatie
    if (empty($name)) {
        $errorMessage = 'Naam is verplicht.';
    } elseif (empty($email)) {
        $errorMessage = 'E-mail is verplicht.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Voer een geldig e-mailadres in.';
    } else {
        // Controleer of e-mail al in gebruik is door een andere gebruiker
        $existingUser = $db->getRow("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user['id']]);
        if ($existingUser) {
            $errorMessage = 'Dit e-mailadres is al in gebruik.';
        } else {
            // Update profiel in database
            $result = $db->query(
                "UPDATE users SET name = ?, email = ?, bio = ?, updated_at = NOW() WHERE id = ?",
                [$name, $email, $bio, $user['id']]
            );
            
            if ($result) {
                $successMessage = 'Profiel succesvol bijgewerkt.';
                // Haal bijgewerkte gebruikersgegevens op
                $user = $auth->getCurrentUser(true); // true om cache te vernieuwen
            } else {
                $errorMessage = 'Er is een fout opgetreden bij het bijwerken van het profiel.';
            }
        }
    }
}

// Verwerk het bijwerken van wachtwoord
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    // Controleer CSRF token
    $security->validateCSRFToken();
    
    // Valideer en saniteer input
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validatie
    if (empty($currentPassword)) {
        $errorMessage = 'Huidig wachtwoord is verplicht.';
    } elseif (empty($newPassword)) {
        $errorMessage = 'Nieuw wachtwoord is verplicht.';
    } elseif (strlen($newPassword) < 8) {
        $errorMessage = 'Wachtwoord moet minimaal 8 tekens bevatten.';
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = 'Nieuwe wachtwoorden komen niet overeen.';
    } else {
        // Controleer huidig wachtwoord
        if (password_verify($currentPassword, $user['password'])) {
            // Update wachtwoord in database
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $result = $db->query(
                "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?",
                [$hashedPassword, $user['id']]
            );
            
            if ($result) {
                $successMessage = 'Wachtwoord succesvol bijgewerkt.';
            } else {
                $errorMessage = 'Er is een fout opgetreden bij het bijwerken van het wachtwoord.';
            }
        } else {
            $errorMessage = 'Huidig wachtwoord is onjuist.';
        }
    }
}

// Verwerk profielfoto upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    // Controleer CSRF token
    $security->validateCSRFToken();
    
    // Controleer of er een bestand is geüpload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        
        // Valideer bestandstype
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            $errorMessage = 'Ongeldig bestandstype. Alleen JPG, PNG en GIF zijn toegestaan.';
        } else {
            // Genereer unieke bestandsnaam
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFilename = 'user_' . $user['id'] . '_' . time() . '.' . $extension;
            $uploadDir = 'assets/images/profile/';
            $uploadPath = $uploadDir . $newFilename;
            
            // Maak uploadmap als deze niet bestaat
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Verplaats bestand naar uploadmap
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Update profielfoto in database
                $result = $db->query(
                    "UPDATE users SET profile_picture = ?, updated_at = NOW() WHERE id = ?",
                    [$newFilename, $user['id']]
                );
                
                if ($result) {
                    $successMessage = 'Profielfoto succesvol bijgewerkt.';
                    // Haal bijgewerkte gebruikersgegevens op
                    $user = $auth->getCurrentUser(true); // true om cache te vernieuwen
                } else {
                    $errorMessage = 'Er is een fout opgetreden bij het bijwerken van de profielfoto.';
                }
            } else {
                $errorMessage = 'Er is een fout opgetreden bij het uploaden van de profielfoto.';
            }
        }
    } else {
        $errorMessage = 'Geen bestand geselecteerd of er is een fout opgetreden bij het uploaden.';
    }
}

// Haal gebruikersactiviteit op
$userActivity = $db->getRows(
    "SELECT a.activity_type, a.activity_data, a.created_at
     FROM user_activity a
     WHERE a.user_id = ?
     ORDER BY a.created_at DESC
     LIMIT 10",
    [$user['id']]
);

// Haal favorieten op
$favoriteElearnings = $db->getRows(
    "SELECT e.id, e.title, e.description, e.thumbnail
     FROM favorites f
     JOIN elearnings e ON f.item_id = e.id
     WHERE f.user_id = ? AND f.item_type = 'elearning'
     ORDER BY f.created_at DESC",
    [$user['id']]
);

$favoriteTools = $db->getRows(
    "SELECT t.id, t.name, t.description, t.icon
     FROM favorites f
     JOIN tools t ON f.item_id = t.id
     WHERE f.user_id = ? AND f.item_type = 'tool'
     ORDER BY f.created_at DESC",
    [$user['id']]
);

// Bepaal profielfoto pad
$profilePicture = !empty($user['profile_picture']) 
    ? 'assets/images/profile/' . $user['profile_picture'] 
    : 'assets/images/profile/default.png';

// Laad header
$pageTitle = 'Mijn Profiel - Slimmer met AI';
include 'includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <div class="hero-content">
            <h1>Mijn Profiel</h1>
            <p>Beheer je persoonlijke gegevens en accountinstellingen</p>
        </div>
    </div>
</div>

<main class="container">
    <section class="section">
        <?php if ($successMessage): ?>
            <div class="alert alert-success">
                <?php echo $security->escapeOutput($successMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="alert alert-error">
                <?php echo $security->escapeOutput($errorMessage); ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-tabs">
            <div class="tab-nav">
                <button class="dashboard-tab active" data-tab="profile-info">Profiel</button>
                <button class="dashboard-tab" data-tab="security">Beveiliging</button>
                <button class="dashboard-tab" data-tab="favorites">Favorieten</button>
                <button class="dashboard-tab" data-tab="activity">Activiteit</button>
            </div>
            
            <!-- Profiel informatie tab -->
            <div id="profile-info" class="dashboard-content" style="display: block;">
                <div class="profile-header">
                    <div class="profile-picture-container">
                        <img src="<?php echo $security->escapeOutput($profilePicture); ?>" alt="Profielfoto" class="profile-picture">
                        
                        <form method="post" enctype="multipart/form-data" class="profile-picture-form">
                            <?php echo $security->generateCSRFInput(); ?>
                            <label for="profile-picture" class="profile-picture-label">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" id="profile-picture" name="profile_picture" accept="image/*" style="display: none;">
                            <button type="submit" name="upload_photo" class="btn btn-primary btn-sm profile-picture-upload">Foto bijwerken</button>
                        </form>
                    </div>
                    
                    <div class="profile-info">
                        <h2><?php echo $security->escapeOutput($user['name']); ?></h2>
                        <p class="profile-email"><?php echo $security->escapeOutput($user['email']); ?></p>
                        <p class="profile-member-since">Lid sinds: <?php echo date('d-m-Y', strtotime($user['created_at'])); ?></p>
                        <button id="edit-profile-btn" class="btn btn-outline">Profiel bewerken</button>
                    </div>
                </div>
                
                <div class="profile-details">
                    <h3>Over mij</h3>
                    <p><?php echo !empty($user['bio']) ? $security->escapeOutput($user['bio']) : 'Geen biografie ingesteld.'; ?></p>
                </div>
                
                <div id="profile-edit" style="display: none;">
                    <h3>Profiel bewerken</h3>
                    <form method="post" class="profile-edit-form">
                        <?php echo $security->generateCSRFInput(); ?>
                        <div class="form-group">
                            <label for="name">Naam</label>
                            <input type="text" id="name" name="name" value="<?php echo $security->escapeOutput($user['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" name="email" value="<?php echo $security->escapeOutput($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="bio">Over mij</label>
                            <textarea id="bio" name="bio" rows="5"><?php echo $security->escapeOutput($user['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" id="cancel-edit-btn" class="btn btn-outline">Annuleren</button>
                            <button type="submit" name="update_profile" class="btn btn-primary">Opslaan</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Beveiliging tab -->
            <div id="security" class="dashboard-content" style="display: none;">
                <h3>Wachtwoord wijzigen</h3>
                <form method="post" class="password-change-form">
                    <?php echo $security->generateCSRFInput(); ?>
                    <div class="form-group">
                        <label for="current_password">Huidig wachtwoord</label>
                        <div class="password-field">
                            <input type="password" id="current_password" name="current_password" required>
                            <span class="password-toggle" data-password-id="current_password">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Nieuw wachtwoord</label>
                        <div class="password-field">
                            <input type="password" id="new_password" name="new_password" required>
                            <span class="password-toggle" data-password-id="new_password">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                        <div class="password-strength-container">
                            <div id="password-strength" class="password-strength-bar"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Bevestig nieuw wachtwoord</label>
                        <div class="password-field">
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <span class="password-toggle" data-password-id="confirm_password">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_password" class="btn btn-primary">Wachtwoord bijwerken</button>
                    </div>
                </form>
                
                <div class="danger-zone">
                    <h3>Gevaarlijke zone</h3>
                    <p>Let op: Deze actie kan niet ongedaan worden gemaakt.</p>
                    <button id="delete-account-btn" class="btn btn-danger">Account verwijderen</button>
                </div>
            </div>
            
            <!-- Favorieten tab -->
            <div id="favorites" class="dashboard-content" style="display: none;">
                <h3>Favoriete E-learnings</h3>
                <div class="favorites-container">
                    <?php if (empty($favoriteElearnings)): ?>
                        <p>Je hebt nog geen e-learnings aan je favorieten toegevoegd.</p>
                    <?php else: ?>
                        <div class="favorites-grid">
                            <?php foreach ($favoriteElearnings as $elearning): ?>
                                <div class="favorite-item">
                                    <img src="<?php echo $security->escapeOutput($elearning['thumbnail']); ?>" alt="<?php echo $security->escapeOutput($elearning['title']); ?>">
                                    <div class="favorite-item-content">
                                        <h4><?php echo $security->escapeOutput($elearning['title']); ?></h4>
                                        <p><?php echo $security->escapeOutput(substr($elearning['description'], 0, 100) . '...'); ?></p>
                                        <div class="favorite-item-actions">
                                            <a href="elearning.php?id=<?php echo $elearning['id']; ?>" class="btn btn-sm btn-outline">Bekijken</a>
                                            <button class="favorite-btn active" data-id="<?php echo $elearning['id']; ?>" data-type="elearning" title="Verwijderen uit favorieten">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <h3>Favoriete Tools</h3>
                <div class="favorites-container">
                    <?php if (empty($favoriteTools)): ?>
                        <p>Je hebt nog geen tools aan je favorieten toegevoegd.</p>
                    <?php else: ?>
                        <div class="favorites-grid">
                            <?php foreach ($favoriteTools as $tool): ?>
                                <div class="favorite-item">
                                    <div class="tool-icon">
                                        <i class="<?php echo $security->escapeOutput($tool['icon']); ?>"></i>
                                    </div>
                                    <div class="favorite-item-content">
                                        <h4><?php echo $security->escapeOutput($tool['name']); ?></h4>
                                        <p><?php echo $security->escapeOutput(substr($tool['description'], 0, 100) . '...'); ?></p>
                                        <div class="favorite-item-actions">
                                            <a href="tool.php?id=<?php echo $tool['id']; ?>" class="btn btn-sm btn-outline">Bekijken</a>
                                            <button class="favorite-btn active" data-id="<?php echo $tool['id']; ?>" data-type="tool" title="Verwijderen uit favorieten">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Activiteit tab -->
            <div id="activity" class="dashboard-content" style="display: none;">
                <h3>Recente activiteit</h3>
                <?php if (empty($userActivity)): ?>
                    <p>Geen recente activiteit gevonden.</p>
                <?php else: ?>
                    <div class="activity-timeline">
                        <?php foreach ($userActivity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?php 
                                    $icon = 'fa-info-circle';
                                    switch ($activity['activity_type']) {
                                        case 'login':
                                            $icon = 'fa-sign-in-alt';
                                            break;
                                        case 'elearning_view':
                                            $icon = 'fa-book-open';
                                            break;
                                        case 'elearning_complete':
                                            $icon = 'fa-check-circle';
                                            break;
                                        case 'tool_view':
                                            $icon = 'fa-tools';
                                            break;
                                        case 'profile_update':
                                            $icon = 'fa-user-edit';
                                            break;
                                    }
                                    ?>
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <p class="activity-text">
                                        <?php 
                                        $activityData = json_decode($activity['activity_data'], true);
                                        $activityText = '';
                                        
                                        switch ($activity['activity_type']) {
                                            case 'login':
                                                $activityText = 'Je bent ingelogd';
                                                break;
                                            case 'elearning_view':
                                                $activityText = 'Je hebt e-learning "' . $activityData['title'] . '" bekeken';
                                                break;
                                            case 'elearning_complete':
                                                $activityText = 'Je hebt e-learning "' . $activityData['title'] . '" voltooid';
                                                break;
                                            case 'tool_view':
                                                $activityText = 'Je hebt tool "' . $activityData['name'] . '" bekeken';
                                                break;
                                            case 'profile_update':
                                                $activityText = 'Je hebt je profiel bijgewerkt';
                                                break;
                                            default:
                                                $activityText = 'Onbekende activiteit';
                                        }
                                        
                                        echo $security->escapeOutput($activityText);
                                        ?>
                                    </p>
                                    <p class="activity-time"><?php echo date('d-m-Y H:i', strtotime($activity['created_at'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<!-- Account verwijderen modal -->
<div id="delete-account-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Account verwijderen</h3>
            <button id="close-modal-btn" class="modal-close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <p>Weet je zeker dat je je account wilt verwijderen? Deze actie kan niet ongedaan worden gemaakt en al je gegevens worden permanent verwijderd.</p>
            <form method="post" action="includes/delete_account.php">
                <?php echo $security->generateCSRFInput(); ?>
                <div class="form-actions">
                    <button type="button" id="cancel-delete-btn" class="btn btn-outline">Annuleren</button>
                    <button type="submit" id="confirm-delete-btn" class="btn btn-danger">Ja, verwijder mijn account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
<script src="assets/js/dashboard.js"></script>
<script src="assets/js/auth.js"></script>

<?php include 'includes/footer.php'; ?>