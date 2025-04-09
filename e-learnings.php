<?php
require_once 'includes/init.php'; // Voeg init.php toe voor functies en sessiebeheer

$page_title = 'E-learnings';
$page_description = 'Ontdek onze praktische e-learnings over AI. Leer hoe je AI effectief kunt inzetten in je werk met onze specifiek ontwikkelde cursussen.';

// Include head component
include 'includes/head.php';

// Include header component
include 'includes/header.php';
?>

<main id="main-content" role="main">
    <section class="hero-with-background" aria-labelledby="main-heading">
        <div class="container">
            <div class="hero-content">
                <h1 id="main-heading">AI E-learnings</h1>
                <p>Ontdek onze praktische e-learnings over AI. Leer hoe je AI effectief kunt inzetten in je werk.</p>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="courses-heading">
        <div class="container">
            <div class="section-header">
                <h2 id="courses-heading">Beschikbare Cursussen</h2>
                <p>Kies uit onze selectie van praktische e-learnings, specifiek ontwikkeld voor Nederlandse professionals.</p>
            </div>
            
            <div class="courses-grid">
                <div class="card">
                    <div class="card-image">
                        <img src="images/ai-basics.svg" alt="Screenshot van de AI Basics cursus die de fundamenten van AI uitlegt">
                    </div>
                    <div class="card-content">
                        <h3>AI Basics</h3>
                        <p>Leer de fundamenten van AI en hoe je het kunt inzetten in je dagelijkse werk.</p>
                        <div class="tool-pricing">
                            <span class="price">€149,95</span>
                            <span class="price-period">eenmalig (incl. BTW)</span>
                        </div>
                        <div class="card-actions">
                            <a href="e-learning/course-ai-basics.html" class="btn btn-outline">Details</a>
                            <button class="btn btn-primary add-to-cart-btn" 
                                data-product-id="ai-basics-1" 
                                data-product-type="Cursus" 
                                data-product-name="AI Basics" 
                                data-product-price="149.95"
                                data-product-img="images/ai-basics.svg">
                                In winkelwagen
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-image">
                        <img src="images/prompt-engineering.svg" alt="Screenshot van de Prompt Engineering cursus die leert hoe je effectief met AI kunt communiceren">
                    </div>
                    <div class="card-content">
                        <h3>Prompt Engineering</h3>
                        <p>Leer hoe je effectief communiceert met AI-systemen voor betere resultaten.</p>
                        <div class="tool-pricing">
                            <span class="price">€199,95</span>
                            <span class="price-period">eenmalig (incl. BTW)</span>
                        </div>
                        <div class="card-actions">
                            <a href="e-learning/course-prompt-engineering.html" class="btn btn-outline">Details</a>
                            <button class="btn btn-primary add-to-cart-btn" 
                                data-product-id="prompt-engineering-1" 
                                data-product-type="Cursus" 
                                data-product-name="Prompt Engineering" 
                                data-product-price="199.95"
                                data-product-img="images/prompt-engineering.svg">
                                In winkelwagen
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-image">
                        <img src="images/workflow-automation.svg" alt="Screenshot van de Workflow Automatisering cursus die leert hoe je processen kunt optimaliseren met AI">
                    </div>
                    <div class="card-content">
                        <h3>Workflow Automatisering</h3>
                        <p>Ontdek hoe je je werkprocessen kunt optimaliseren met AI-tools.</p>
                        <div class="tool-pricing">
                            <span class="price">€179,95</span>
                            <span class="price-period">eenmalig (incl. BTW)</span>
                        </div>
                        <div class="card-actions">
                            <a href="e-learning/course-workflow-automation.html" class="btn btn-outline">Details</a>
                            <button class="btn btn-primary add-to-cart-btn" 
                                data-product-id="workflow-automation-1" 
                                data-product-type="Cursus" 
                                data-product-name="Workflow Automatisering" 
                                data-product-price="179.95"
                                data-product-img="images/workflow-automation.svg">
                                In winkelwagen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
// Include footer component
include 'includes/footer.php';
?>

<script src="js/main.js"></script>
<script src="js/cart.js"></script>
</body>
</html> 