<?php
// Laad configuratie als die bestaat
if (file_exists('includes/config.php')) {
    require_once 'includes/config.php';
}

// Stel paginatitel en beschrijving in
$page_title = 'Home | Slimmer met AI';
$page_description = 'Praktische AI-tools en e-learnings voor Nederlandse professionals. Werk slimmer, niet harder.';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="<?php echo $page_description; ?>">
    <link rel="stylesheet" href="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/css/style.css'; ?>">
    <noscript>
        <style>
            .preloader { display: none !important; }
        </style>
    </noscript>

    <!-- Favicon -->
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">

    <!-- Scripts -->
    <script src="js/main.js" defer></script>
</head>
<body>
    <div class="preloader">
        <div class="loader"></div>
    </div>
    
    <a href="#main-content" class="skip-link">Direct naar inhoud</a>

    <!-- Begin Header -->
    <header role="banner">
        <div class="container">
            <nav class="navbar" role="navigation" aria-label="Hoofdnavigatie">
                <div class="logo">
                    <a href="index.php">
                        <img src="images/Logo.svg" alt="Slimmer met AI logo" width="50">
                        <span class="logo-text">Slimmer met AI</span>
                    </a>
                </div>
                <div class="nav-links">
                    <a href="index.php" aria-current="page">Home</a>
                    <a href="tools.php">Tools</a>
                    <a href="e-learnings.php">Cursussen</a>
                    <a href="over-mij.php">Over Mij</a>
                    <a href="nieuws.php">Nieuws</a>
                </div>
            </nav>
        </div>
    </header>
    <!-- Einde Header -->

    <main id="main-content" role="main">
        <section class="page-hero">
            <div class="container">
                <div class="hero-content">
                    <h1>Slimmer met AI</h1>
                    <p>Praktische AI-tools en e-learnings voor Nederlandse professionals</p>
                    <div class="hero-buttons">
                        <a href="tools.php" class="btn btn-primary">Bekijk Tools</a>
                        <a href="e-learnings.php" class="btn btn-secondary">Ontdek E-learnings</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <h2>Welkom bij Slimmer met AI</h2>
                <p>Hier vind je praktische tools en cursussen om slimmer te werken met AI.</p>
                <!-- Hier kan meer content komen -->
            </div>
        </section>
    </main>

    <!-- Begin Footer -->
    <footer role="contentinfo">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <div class="footer-logo">
                        <img src="images/Logo.svg" alt="Slimmer met AI logo" width="30">
                        <span class="logo-text">Slimmer met AI</span>
                    </div>
                    <p class="footer-text">Praktische AI-tools en e-learnings voor Nederlandse professionals. Werk slimmer, niet harder.</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Slimmer met AI. Alle rechten voorbehouden.</p>
            </div>
        </div>
    </footer>
    <!-- Einde Footer -->
</body>
</html> 