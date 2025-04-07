<?php
// Definieer paden voor de productieomgeving - VERWIJDERD (header.php laadt init.php)
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

// Stel paginavariabelen in VOOR het includen van de header
$page_title = 'AI Tools | Slimmer met AI';
$page_description = 'Ontdek praktische AI-tools die je administratieve taken automatiseren, zodat jij je kunt focussen op wat echt belangrijk is.';
$active_page = 'tools';

// Laad header (deze laadt init.php, head, navbar en opent <main>)
require_once __DIR__ . '/includes/header.php';
?>

<!-- Pagina specifieke content voor Tools -->
<slimmer-hero 
    title="AI Tools voor Professionals" 
    subtitle="Ontdek onze collectie van praktische AI-tools die je helpen om efficiÃ«nter te werken en tijd te besparen op routinetaken."
    background="image"
    image-url="images/hero-background.svg"
    centered>
</slimmer-hero>

<section class="section" aria-labelledby="tools-heading">
    <div class="container">
        <div class="section-header">
            <h2 id="tools-heading">Beschikbare Tools</h2>
            <p>Kies uit onze selectie van AI-tools, specifiek ontwikkeld voor Nederlandse professionals.</p>
        </div>
        
        <div class="tools-grid">
            <div class="card">
                <div class="card-image">
                    <img src="<?php echo asset_url('images/email-assistant.svg'); ?>" alt="Screenshot van de Email Assistent AI tool die helpt bij het opstellen en beantwoorden van emails">
                </div>
                <div class="card-content">
                    <h3>AI Email Assistent</h3>
                    <p>Laat AI je helpen bij het schrijven, beantwoorden en categoriseren van e-mails. Bespaar uren per week.</p>
                    <div class="tool-pricing">
                        <span class="price">€79,95</span>
                        <span class="price-period">eenmalig (incl. BTW)</span>
                    </div>
                    <div class="card-actions">
                        <a href="#email-tool-details" class="btn btn-outline">Details</a>
                        <button class="btn btn-primary add-to-cart-btn" 
                            data-product-id="email-assistant-1" 
                            data-product-type="Tool" 
                            data-product-name="AI Email Assistent" 
                            data-product-price="79.95"
                            data-product-img="images/email-assistant.svg">
                            In winkelwagen
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-image">
                    <img src="<?php echo asset_url('images/rapport-generator.svg'); ?>" alt="Screenshot van de Rapport Generator die automatisch professionele rapporten maakt">
                </div>
                <div class="card-content">
                    <h3>Rapport Generator</h3>
                    <p>Genereer in enkele minuten professionele rapporten op basis van jouw data en input.</p>
                    <div class="tool-pricing">
                        <span class="price">€99,95</span>
                        <span class="price-period">eenmalig (incl. BTW)</span>
                    </div>
                    <div class="card-actions">
                        <a href="#rapport-tool-details" class="btn btn-outline">Details</a>
                        <button class="btn btn-primary add-to-cart-btn" 
                            data-product-id="rapport-generator-1" 
                            data-product-type="Tool" 
                            data-product-name="Rapport Generator" 
                            data-product-price="99.95"
                            data-product-img="images/rapport-generator.svg">
                            In winkelwagen
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-image">
                    <img src="<?php echo asset_url('images/meeting-summarizer.svg'); ?>" alt="Screenshot van de Meeting Summarizer die vergaderingen automatisch samenvat">
                </div>
                <div class="card-content">
                    <h3>Meeting Summarizer</h3>
                    <p>Krijg automatisch gestructureerde samenvattingen en actiepunten uit je vergaderingen.</p>
                    <div class="tool-pricing">
                        <span class="price">€69,95</span>
                        <span class="price-period">eenmalig (incl. BTW)</span>
                    </div>
                    <div class="card-actions">
                        <a href="#meeting-tool-details" class="btn btn-outline">Details</a>
                        <button class="btn btn-primary add-to-cart-btn" 
                            data-product-id="meeting-summarizer-1" 
                            data-product-type="Tool" 
                            data-product-name="Meeting Summarizer" 
                            data-product-price="69.95"
                            data-product-img="images/meeting-summarizer.svg">
                            In winkelwagen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<slimmer-footer></slimmer-footer>

</main> <!-- Sluit de main content sectie geopend in header.php -->
</body>
</html> 

