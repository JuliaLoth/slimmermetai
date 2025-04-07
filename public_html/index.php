<?php
/**
 * Homepage van SlimmerMetAI.com
 */

// Stel pagina variabelen in
$page_title = 'SlimmerMetAI - Praktische AI-tools voor Nederlandse professionals';
$page_description = 'SlimmerMetAI biedt praktische AI-tools en e-learnings voor Nederlandse professionals. Automatiseer je taken en werk slimmer, niet harder.';
$active_page = 'index';

// Laad de header (die ook de <slimmer-navbar> bevat)
require_once 'includes/header.php';
?>

<main id="main-content" role="main">

<!-- Hero sectie -->
<slimmer-hero 
    title="Werk slimmer. Niet harder." 
    subtitle="De praktische tools en e-learnings die je nodig hebt om AI in te zetten voor administratieve taken. Zodat jij je kunt focussen op wat écht belangrijk is."
    background="image"
    image-url="images/hero-background.svg"
    centered>
    <div slot="actions">
        <slimmer-button href="tools.php">Bekijk Tools</slimmer-button>
        <slimmer-button href="e-learnings.php" type="outline">Ontdek Cursussen</slimmer-button>
    </div>
</slimmer-hero>

<!-- Aanbevolen tools sectie -->
<section class="featured-tools">
    <div class="container">
        <h2>Ontdek Onze AI Tools</h2>
        <p>Ontdek hoe onze AI tools je kunnen helpen om efficiënter te werken</p>
        
        <div class="slider-container">
            <div id="slider-content" class="slider-content">
                <div class="slider-item active">
                    <div class="tool-card">
                        <h3>Email Assistent Plus</h3>
                        <p>Schrijf professionele e-mails in seconden met AI</p>
                        <a href="tools.php?id=email-assistant" class="tool-link">Meer informatie</a>
                    </div>
                </div>
                
                <div class="slider-item">
                    <div class="tool-card">
                        <h3>Document Analyzer 2.0</h3>
                        <p>Automatische analyse en samenvatting van documenten</p>
                        <a href="tools.php?id=document-analyzer" class="tool-link">Meer informatie</a>
                    </div>
                </div>
                
                <div class="slider-item">
                    <div class="tool-card">
                        <h3>Content Generator Suite</h3>
                        <p>Genereer professionele content voor alle kanalen</p>
                        <a href="tools.php?id=content-generator" class="tool-link">Meer informatie</a>
                    </div>
                </div>
            </div>
            
            <div class="slider-controls">
                <button class="slider-btn prev" aria-label="Vorige slide">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <button class="slider-btn next" aria-label="Volgende slide">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Cursussen sectie -->
<section class="featured-courses">
    <div class="container">
        <h2>Populaire Cursussen</h2>
        <p>Leer hoe je de kracht van AI kunt benutten in jouw werk</p>
        
        <div class="courses-grid">
            <div class="course-card">
                <img src="images/ai-basics.svg" alt="AI Basis Cursus" class="course-image">
                <div class="course-content">
                    <h3>AI Basis: Van nul naar praktijk</h3>
                    <p>Een perfecte introductie voor professionals die willen starten met AI</p>
                    <a href="e-learnings.php?id=ai-basics" class="course-link">Bekijk cursus</a>
                </div>
            </div>
            
            <div class="course-card">
                <img src="images/prompt-engineering.svg" alt="Effectief Prompten" class="course-image">
                <div class="course-content">
                    <h3>Effectief Prompten: De sleutel tot AI</h3>
                    <p>Leer betere resultaten krijgen uit AI tools met effectieve prompts</p>
                    <a href="e-learnings.php?id=effective-prompting" class="course-link">Bekijk cursus</a>
                </div>
            </div>
            
            <div class="course-card">
                <img src="images/workflow-automation.svg" alt="AI Workflows" class="course-image">
                <div class="course-content">
                    <h3>AI Workflows: Automatiseer je werk</h3>
                    <p>Ontdek hoe je complexe werkprocessen kunt automatiseren met AI</p>
                    <a href="e-learnings.php?id=ai-workflows" class="course-link">Bekijk cursus</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Nieuwsbrief sectie -->
<section class="section cta-section">
    <div class="container">
        <div class="cta-container">
            <h3>Blijf op de hoogte van nieuwe tools & tips</h3>
            <p class="cta-text">Ontvang tweewekelijks praktische AI-tips die je direct kunt toepassen.<br>Geen buzzwords, gewoon concrete tijdsbesparende hacks.</p>
            
            <div class="newsletter-signup">
                <iframe src="https://slimmermetai.substack.com/embed" 
                        width="100%" 
                        height="150" 
                        style="border:1px solid #EEE; background:white; max-width: 480px; margin: 0 auto; display: block;" 
                        frameborder="0" 
                        scrolling="no"
                        title="SlimmerMetAI Nieuwsbrief Inschrijving"
                        aria-label="Inschrijfformulier voor SlimmerMetAI nieuwsbrief">
                </iframe>
            </div>
        </div>
    </div>
</section>



<?php
// Footer wordt hieronder geladen als web component
?>
<slimmer-footer></slimmer-footer>

</main> <!-- Sluit de main content sectie geopend in header.php -->
</body>
</html>