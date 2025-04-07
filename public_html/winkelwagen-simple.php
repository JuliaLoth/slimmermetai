<?php
/**
 * Simpele winkelwagen testpagina
 */

// Initialiseer de website
require_once dirname(__DIR__) . '/includes/init.php';

// Stel pagina variabelen in
$page_title = 'Simpele Winkelwagen | Slimmer met AI';
$page_description = 'Eenvoudige testpagina voor winkelwagen functionaliteit';
$active_page = 'winkelwagen';

// Laad de header
require_once 'includes/header.php';
?>

<!-- Hero sectie -->
<slimmer-hero 
    title="Simpele Winkelwagen" 
    subtitle="Test pagina voor winkelwagen functionaliteit"
    background="image"
    image-url="images/hero background def.svg"
    centered>
</slimmer-hero>

<!-- Content sectie -->
<section class="section">
    <div class="container">
        <h2>Eenvoudige test</h2>
        <p>Deze pagina is een vereenvoudigde versie van de winkelwagen pagina om te testen of de basisstructuur werkt.</p>
        
        <div class="test-info" style="margin-top: 30px; padding: 20px; background: #f5f5f5; border-radius: 5px;">
            <h3>Debug Info</h3>
            <pre>
PHP Versie: <?php echo phpversion(); ?>
Include path: <?php echo get_include_path(); ?>
Current file: <?php echo __FILE__; ?>
Parent directory: <?php echo dirname(__DIR__); ?>
            </pre>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/../components/footer.php';
?> 