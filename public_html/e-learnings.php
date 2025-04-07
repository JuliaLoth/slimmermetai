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
$page_title = 'E-learnings | Slimmer met AI';
$page_description = 'Slimmer met AI - E-learnings';
?>
<!DOCTYPE html>
<html lang="nl" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-learnings | Slimmer met AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="Ontdek onze praktische e-learnings over AI voor Nederlandse professionals. Leer hoe je AI effectief kunt gebruiken in je dagelijkse werk.">
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
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
    
    <slimmer-navbar active-page="e-learnings" cart-count="0"></slimmer-navbar>
    
    <main id="main-content" role="main">
        <slimmer-hero 
            title="AI Cursussen voor Professionals" 
            subtitle="Ontdek onze e-learnings over kunstmatige intelligentie, ontworpen voor Nederlandse professionals die slimmer willen werken."
            background="image"
            image-url="images/hero background def.svg"
            centered>
        </slimmer-hero>

        <section id="courses" class="section" aria-labelledby="courses-heading">
            <div class="container">
                <div class="section-header">
                    <h2 id="courses-heading">Beschikbare Cursussen</h2>
                    <p>Kies de cursus die het beste bij jouw niveau en leerdoelen past.</p>
                </div>
                
                <div class="courses-grid">
                    <div class="course-card coming-soon">
                        <div class="course-image">
                            <img src="<?php echo asset_url('images/workflow-automation.svg'); ?>" alt="Workflow Automation cursus">
                            <div class="coming-soon-badge">Binnenkort beschikbaar</div>
                        </div>
                        <div class="course-content">
                            <h3>Workflow Automation met AI</h3>
                            <p>Leer hoe je repetitieve taken kunt automatiseren met AI-tools. Bespaar tijd en verhoog je productiviteit.</p>
                            <div class="course-pricing">
                                <span class="course-price">€149,95</span>
                                <span class="course-price-period">eenmalig (incl. BTW)</span>
                            </div>
                            <div class="course-actions">
                                <a href="#workflow-details" class="btn btn-outline">Details</a>
                                <button class="btn btn-primary" disabled>Binnenkort beschikbaar</button>
                            </div>
                        </div>
                    </div>

                    <div class="course-card coming-soon">
                        <div class="course-image">
                            <img src="<?php echo asset_url('images/prompt-engineering.svg'); ?>" alt="Prompt Engineering cursus">
                            <div class="coming-soon-badge">Binnenkort beschikbaar</div>
                        </div>
                        <div class="course-content">
                            <h3>Prompt Engineering</h3>
                            <p>Ontdek de kunst van het schrijven van effectieve prompts voor AI-tools. Maximaliseer de kwaliteit van je resultaten.</p>
                            <div class="course-pricing">
                                <span class="course-price">€129,95</span>
                                <span class="course-price-period">eenmalig (incl. BTW)</span>
                            </div>
                            <div class="course-actions">
                                <a href="#prompt-details" class="btn btn-outline">Details</a>
                                <button class="btn btn-primary" disabled>Binnenkort beschikbaar</button>
                            </div>
                        </div>
                    </div>

                    <div class="course-card coming-soon">
                        <div class="course-image">
                            <img src="<?php echo asset_url('images/ai-basics.svg'); ?>" alt="AI Basics cursus">
                            <div class="coming-soon-badge">Binnenkort beschikbaar</div>
                        </div>
                        <div class="course-content">
                            <h3>AI Basics voor Professionals</h3>
                            <p>Een complete introductie in AI voor professionals. Begrijp de basis en ontdek praktische toepassingen.</p>
                            <div class="course-pricing">
                                <span class="course-price">€99,95</span>
                                <span class="course-price-period">eenmalig (incl. BTW)</span>
                            </div>
                            <div class="course-actions">
                                <a href="#basics-details" class="btn btn-outline">Details</a>
                                <button class="btn btn-primary" disabled>Binnenkort beschikbaar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <slimmer-footer></slimmer-footer>

    <!-- Scripts -->
    <script src="<?php echo asset_url('js/cart.js'); ?>"></script>
    <script src="<?php echo asset_url('js/main.js'); ?>"></script>
</body>
</html> 

