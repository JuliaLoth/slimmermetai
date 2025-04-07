<?php
// Definieer paden voor de productieomgeving - VERWIJDERD (aangenomen dat init.php wordt geladen)
// define('PUBLIC_INCLUDES', __DIR__ . '/includes');

// Helper functie voor includes - VERWIJDERD (nu in init.php)
/*
function include_public($file) {
    return include PUBLIC_INCLUDES . '/' . $file;
}
*/

// Helper functie voor asset URLs - VERWIJDERD (nu in init.php)
/*
function asset_url($path) {
    return '//' . $_SERVER['HTTP_HOST'] . '/' . ltrim($path, '/');
}
*/

// Laad init.php als dat nog niet gebeurd is (nodig als header.php niet wordt geladen)
if (file_exists(dirname(__DIR__) . '/includes/init.php')) {
    require_once dirname(__DIR__) . '/includes/init.php';
} else {
    // Fallback of error als init.php niet gevonden kan worden
    error_log('FATAL: init.php niet gevonden vanuit 404.php');
    // Stuur een simpele 404 response zonder afhankelijkheden
    http_response_code(404);
    echo "404 Pagina niet gevonden";
    exit;
}

// Stel paginatitel en beschrijving in
$page_title = 'Pagina niet gevonden | Slimmer met AI';
$page_description = 'De opgevraagde pagina kon niet worden gevonden - Slimmer met AI';
?>
<!DOCTYPE html>
<html lang="nl" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="<?php echo $page_description; ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
    <!-- Laad alle componenten -->
    <script src="<?php echo asset_url('components/ComponentsLoader.js'); ?>"></script>
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
    <?php @include_public('header.php'); ?>
    <!-- Einde Header -->
    
    <main id="main-content" role="main">
        <slimmer-hero 
            title="Pagina niet gevonden" 
            subtitle="De pagina die je zoekt bestaat niet"
            background="image"
            image-url="images/hero background def.svg"
            centered>
        </slimmer-hero>
        
        <section class="section error-section">
            <div class="container">
                <div class="error-container">
                    <h2>Fout 404: Pagina niet gevonden</h2>
                    <p>We kunnen de pagina die je zoekt niet vinden. Dit kan zijn omdat:</p>
                    <ul>
                        <li>De pagina is verplaatst of verwijderd</li>
                        <li>De URL die je hebt ingevoerd bevat een typfout</li>
                        <li>Je hebt op een verouderde link geklikt</li>
                    </ul>
                    <p>Wat kun je doen?</p>
                    <ul>
                        <li>Terug naar de <a href="/">homepagina</a></li>
                        <li>Gebruik de navigatie om te vinden wat je zoekt</li>
                        <li>Controleer de URL op typefouten</li>
                    </ul>
                </div>
            </div>
        </section>
    </main>
    
    <slimmer-footer></slimmer-footer>

    <!-- Scripts -->
    <script src="<?php echo asset_url('js/main.js'); ?>"></script>
</body>
</html> 