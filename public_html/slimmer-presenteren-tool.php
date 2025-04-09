<?php
// Stel paginavariabelen in VOOR het includen van de header
$page_title = 'Slimmer Presenteren Tool | Slimmer met AI';
$page_description = 'Converteer uw React component code direct naar professionele PowerPoint presentaties.';
$active_page = 'tools'; // Houdt 'tools' actief in de navigatie

// Laad header (deze laadt init.php, die sessies en auth start)
require_once __DIR__ . '/includes/header.php';

// --- TOEGANGSCONTROLE ---
// 1. Vereis dat de gebruiker ingelogd is EN de juiste rol heeft.
//    Pas 'subscriber' aan naar de daadwerkelijke rol(len) die toegang hebben.
requireRole(['subscriber', 'admin']); // Voorbeeld: zowel subscriber als admin hebben toegang

// Als de gebruiker hier komt, is deze ingelogd en heeft de juiste rol.

?>

<!-- Pagina specifieke content voor Slimmer Presenteren -->
<section class="section tool-section" aria-labelledby="tool-heading">
    <div class="container">
        <div class="section-header">
            <h1 id="tool-heading">Slimmer Presenteren Tool</h1>
            <p>Plak hier uw React code of genereer deze via onze AI prompt om een PowerPoint presentatie te maken.</p>
            <!-- Optioneel: Verwijzing naar abonnement kan weg als requireRole() toegang regelt -->
            <!-- <p class="small-text">Let op: Alleen toegankelijk met een actief <a href="/abonnementen.php">Slimmer met AI abonnement</a>.</p> -->
        </div>

        <!-- Hier komt de React Applicatie -->
        <div id="slimmer-presenteren-app">
            <!-- React laadt hier de componenten -->
            <p>Converter wordt geladen...</p> 
        </div>
        
    </div>
</section>

<?php
// Pad naar de gecompileerde React bestanden
// Let op: De hash in de bestandsnamen kan veranderen na elke build!
$react_build_dir = '/react-builds/slimmer-presenteren'; 
$react_js_filename = 'index-DBl2qSpZ.js'; // BIJGEWERKT na laatste npm run build
$react_css_filename = 'index-Dtn62Xmo.css'; // Gevonden na npm run build

$react_js_path = $react_build_dir . '/' . $react_js_filename;
$react_css_path = $react_build_dir . '/' . $react_css_filename;
?>

<?php // Laad CSS als het bestand bestaat ?>
<?php if (file_exists(PUBLIC_ROOT . $react_css_path)): ?>
    <link rel="stylesheet" href="<?php echo asset_url($react_css_path); ?>">
<?php else: ?>
    <!-- Optioneel: Toon een fout als CSS niet gevonden wordt -->
    <!-- <p style="color: orange;">Waarschuwing: Kon React CSS niet laden.</p> -->
<?php endif; ?>

<?php // Laad JS als het bestand bestaat ?>
<?php if (file_exists(PUBLIC_ROOT . $react_js_path)): ?>
    <script type="module" src="<?php echo asset_url($react_js_path); ?>"></script>
<?php else: ?>
    <p style="color: red; text-align: center; padding: 2rem;"><b>Fout: Kon de Slimmer Presenteren tool niet laden.</b><br>Neem contact op met de beheerder als dit probleem blijft bestaan.</p>
    <style>#slimmer-presenteren-app { display: none; }</style> <?php // Verberg de 'laden...' tekst ?>
<?php endif; ?>

<?php
// Laad de standaard footer (sluit </body> en </html>)
require_once INCLUDES_ROOT . '/footer.php'; 
?> 