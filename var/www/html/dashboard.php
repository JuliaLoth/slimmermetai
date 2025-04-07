<?php
// Laad configuratie
require_once '../config/config.php';

// Laad classes
require_once 'includes/Auth.php';
require_once 'includes/Security.php';

// Initialiseer Auth en Security
$auth = Auth::getInstance();
$security = Security::getInstance();

// Controleer of de gebruiker is ingelogd, zo niet stuur naar login pagina
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Controleer sessie-integriteit (om sessie-kaping te voorkomen)
if (!$security->checkSessionIntegrity()) {
    $auth->logout();
    header('Location: login.php?error=session_expired');
    exit;
}

// Haal huidige gebruiker op
$user = $auth->getCurrentUser();

// Verwerk uitloggen
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    // Controleer CSRF token (optioneel voor uitloggen, maar beter voor veiligheid)
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $auth->logout();
        header('Location: login.php?message=logged_out');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="nl" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Slimmer met AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="Je persoonlijke dashboard voor Slimmer met AI.">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .welcome-message {
            margin-bottom: 0.5rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.1);
        }
        
        .dashboard-card-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(88, 82, 242, 0.1) 0%, rgba(219, 39, 119, 0.1) 100%);
            border-bottom: 1px solid #f3f4f6;
        }
        
        .dashboard-card-body {
            padding: 1.5rem;
        }
        
        .dashboard-card h3 {
            margin: 0;
            color: #111827;
        }
        
        .dashboard-card p {
            color: #6b7280;
            margin-bottom: 1.5rem;
        }
        
        .dashboard-card-footer {
            padding: 1rem 1.5rem;
            background-color: #f9fafb;
            border-top: 1px solid #f3f4f6;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin: 0;
            color: #5852f2;
        }
        
        .stat-card p {
            color: #6b7280;
            margin: 0.5rem 0 0;
        }
        
        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .activity-item {
            display: flex;
            padding: 1rem 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(88, 82, 242, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: #5852f2;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 500;
            color: #111827;
            margin: 0 0 0.25rem;
        }
        
        .activity-time {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .profile-section {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .profile-info h3 {
            margin-top: 0;
        }
        
        .profile-detail {
            display: flex;
            margin-bottom: 1rem;
        }
        
        .profile-detail strong {
            width: 120px;
            color: #4b5563;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .profile-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main id="main-content" role="main">
        <section class="page-hero">
            <div class="container">
                <div class="hero-content">
                    <h1>Mijn Dashboard</h1>
                    <p>Welkom bij je persoonlijke dashboard bij Slimmer met AI.</p>
                </div>
            </div>
        </section>
        
        <section class="section">
            <div class="container">
                <div class="dashboard-header">
                    <div>
                        <h2 class="welcome-message">Welkom, <?php echo $security->escape($user['name']); ?>!</h2>
                        <p>Hier vind je al je tools, e-learnings en persoonlijke gegevens.</p>
                    </div>
                    <a href="dashboard.php?logout=1&token=<?php echo $security->getCsrfToken(); ?>" class="btn-outline">Uitloggen</a>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>0</h3>
                        <p>Actieve e-learnings</p>
                    </div>
                    <div class="stat-card">
                        <h3>0</h3>
                        <p>Voltooide e-learnings</p>
                    </div>
                    <div class="stat-card">
                        <h3>0</h3>
                        <p>Favoriete tools</p>
                    </div>
                    <div class="stat-card">
                        <h3>0</h3>
                        <p>Opgeslagen prompts</p>
                    </div>
                </div>
                
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <h3>Mijn E-learnings</h3>
                        </div>
                        <div class="dashboard-card-body">
                            <p>Je hebt momenteel geen actieve e-learnings.</p>
                            <a href="e-learnings.php" class="btn-primary">Bekijk e-learnings</a>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <h3>Mijn Tools</h3>
                        </div>
                        <div class="dashboard-card-body">
                            <p>Krijg toegang tot exclusieve AI-tools en prompt templates.</p>
                            <a href="tools.php" class="btn-primary">Bekijk tools</a>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <h3>Recente Activiteit</h3>
                        </div>
                        <div class="dashboard-card-body">
                            <ul class="activity-list">
                                <li class="activity-item">
                                    <div class="activity-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                                        </svg>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">Account aangemaakt</div>
                                        <div class="activity-time"><?php echo date('d-m-Y H:i', strtotime($user['created_at'])); ?></div>
                                    </div>
                                </li>
                                <li class="activity-item">
                                    <div class="activity-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm3.5 7.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
                                        </svg>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">Laatste login</div>
                                        <div class="activity-time"><?php echo $user['last_login'] ? date('d-m-Y H:i', strtotime($user['last_login'])) : 'Nu'; ?></div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="profile-section">
                    <div class="profile-image-container">
                        <img src="assets/images/profile-placeholder.jpg" alt="Profielfoto" class="profile-image">
                    </div>
                    
                    <div class="profile-info">
                        <h3>Mijn Profiel</h3>
                        
                        <div class="profile-detail">
                            <strong>Naam:</strong>
                            <span><?php echo $security->escape($user['name']); ?></span>
                        </div>
                        
                        <div class="profile-detail">
                            <strong>E-mail:</strong>
                            <span><?php echo $security->escape($user['email']); ?></span>
                        </div>
                        
                        <div class="profile-detail">
                            <strong>Lid sinds:</strong>
                            <span><?php echo date('d-m-Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        
                        <div class="profile-buttons" style="margin-top: 1.5rem;">
                            <a href="profile-edit.php" class="btn-outline">Profiel bewerken</a>
                            <a href="change-password.php" class="btn-outline" style="margin-left: 1rem;">Wachtwoord wijzigen</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html> 