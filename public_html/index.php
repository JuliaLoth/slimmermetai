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

<style>
  /* Centreer tekst in hoofdsecties */
  main .featured-tools,
  main .featured-courses,
  main .cta-section .cta-container { /* Specifieker voor CTA container */
    text-align: center;
  }

  /* Achtergrond en padding voor slider */
  .slider-container {
    /* background: linear-gradient(to right, #D83C8F, #ff80ab); */ /* Roze gradient */
    /* background-image: url('images/hero-background.svg'); */ /* Oude SVG achtergrond */
    background-image: url('images/Website background.svg'); /* Nieuwe SVG achtergrond */
    background-size: cover; /* Bedek het hele gebied */
    background-position: center; /* Centreer de afbeelding */
    background-color: #f8f9fa; /* Fallback kleur */
    padding: 2rem; /* Padding rondom de slider */
    border-radius: 8px; /* Afgeronde hoeken */
    margin-top: 2rem; /* Extra ruimte boven de slider */
    text-align: left; /* Reset text-align voor de inhoud van de slider zelf */
    position: relative; /* Nodig voor positionering knoppen, maar nu anders gebruikt */
  }

  /* Zorg dat tool cards binnen slider niet gecentreerd zijn */
  .tool-card {
      text-align: left; /* Reset voor kaart zelf */
  }

  /* Centreren van inhoud binnen tool-card in slider */
  .slider-container .tool-card {
      text-align: center; /* Centreer inhoud van tool card */
      background-color: rgba(255, 255, 255, 0.9); /* Iets minder transparante witte achtergrond voor leesbaarheid */
      padding: 1.5rem;
      border-radius: 6px;
      margin-bottom: 1rem; /* Ruimte tussen kaarten indien ze onder elkaar komen */
  }

  .slider-container .tool-card h3 {
      text-align: center;
      /* color: #ffffff; */ /* Oude witte kleur */
      color: #333333; /* Donkere tekstkleur behouden, goed leesbaar op lichte kaart */
      margin-bottom: 0.75rem; /* Extra ruimte onder titel */
  }

  .slider-container .tool-card p {
       /* color: #f8f9fa; */ /* Oude lichte kleur */
       color: #555555; /* Iets lichtere donkere kleur voor paragraaf, goed leesbaar op lichte kaart */
       text-align: center; /* Centreer ook de paragraaf */
       margin-bottom: 1rem; /* Ruimte onder paragraaf */
  }

  /* Zorg dat course cards niet gecentreerd zijn */
   .courses-grid {
       text-align: left; /* Reset voor de grid zelf */
   }
   .course-card {
       text-align: left; /* Reset voor individuele kaarten */
       display: flex; /* Verbeter layout van cursus kaart */
       flex-direction: column; /* Stapel inhoud verticaal */
       justify-content: space-between; /* Duw knop naar beneden */
       height: 100%; /* Zorg dat kaarten gelijke hoogte hebben indien nodig */
       border: 1px solid #eee; /* Lichte rand voor course cards */
       border-radius: 8px;
       padding: 1.5rem;
       background-color: #fff;
   }
    .course-card img.course-image {
        max-width: 100%;
        height: auto;
        margin-bottom: 1rem;
        border-radius: 6px;
    }
   .course-card .course-content {
       flex-grow: 1; /* Laat inhoud groeien */
       display: flex;
       flex-direction: column;
   }
   .course-card h3 {
       margin-bottom: 0.5rem;
   }
   .course-card p {
       flex-grow: 1;
       margin-bottom: 1rem;
   }

  /* Aanpassing voor slimmer-button binnen course-card */
  .course-card slimmer-button {
      margin-top: auto; /* Zorgt dat knop onderaan blijft in flex container */
      align-self: flex-start; /* Zorg dat knop niet volledige breedte inneemt */
  }

  /* Styling voor slider navigatie knoppen */
  .slider-controls {
    /* Verwijder absolute positionering */
    /* position: absolute; */
    /* top: 50%; */
    /* transform: translateY(-50%); */
    /* width: 100%; */
    /* display: flex; */ /* Behoud flex voor inline plaatsing knoppen */
    /* justify-content: space-between; */
    /* padding: 0 10px; */
    /* box-sizing: border-box; */
    /* pointer-events: none; */

    /* Nieuwe styling: centreer onder de slider inhoud */
    text-align: center; /* Centreer de knoppen */
    margin-top: 1.5rem; /* Ruimte boven de knoppen */
  }

  .slider-btn {
    /* background-color: #007bff; */ /* Oude blauwe achtergrond */
    background-image: linear-gradient(45deg, #5852f2, #8e88ff, #5852f2);
    background-size: 200% auto;
    color: #ffffff; /* Witte pijl */
    /* border: 1px solid #007bff; */ /* Oude rand */
    border: none; /* Geen rand */
    border-radius: 50%; /* Maak rond */
    width: 40px; /* Vaste breedte */
    height: 40px; /* Vaste hoogte */
    display: inline-flex; /* Gebruik inline-flex voor align/justify */
    align-items: center;
    justify-content: center;
    cursor: pointer;
    /* transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease; */ /* Oude transitie */
    transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1); /* Nieuwe transitie */
    padding: 0; /* Reset padding */
    margin: 0 0.5rem; /* Ruimte tussen de knoppen */
    box-shadow: 0 4px 15px rgba(88, 82, 242, 0.2); /* Schaduw zoals slimmer-button */
    position: relative; /* Nodig voor pseudo-elementen indien gewenst, maar houden we simpel */
    overflow: hidden; /* Voorkomt dat effecten buiten de rand gaan */
  }

  .slider-btn:hover {
    /* background-color: #0056b3; */ /* Oude hover kleur */
    /* border-color: #0050a2; */ /* Oude hover rand */
    background-position: right center; /* Gradient shift */
    box-shadow: 0 7px 20px rgba(88, 82, 242, 0.4); /* Verhoogde schaduw */
    transform: translateY(-2px); /* Lichte lift */
    color: #ffffff; /* Witte pijl blijft wit */
  }

  .slider-btn svg {
    stroke: currentColor; /* Gebruik de 'color' eigenschap van de knop */
    width: 20px; /* Grootte pijl */
    height: 20px;
  }
  .slider-btn.prev {
      /* margin-left: -30px; */ /* Verwijderd */
  }
  .slider-btn.next {
      /* margin-right: -30px; */ /* Verwijderd */
  }

  /* Zorg dat nieuwsbrief iframe niet gecentreerd wordt door text-align */
   .newsletter-signup iframe {
       margin: 0 auto; /* Blijft gecentreerd via margin */
       display: block;
   }

</style>

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
                        <slimmer-button href="tools.php?id=email-assistant">Meer informatie</slimmer-button>
                    </div>
                </div>
                
                <div class="slider-item">
                    <div class="tool-card">
                        <h3>Document Analyzer 2.0</h3>
                        <p>Automatische analyse en samenvatting van documenten</p>
                        <slimmer-button href="tools.php?id=document-analyzer">Meer informatie</slimmer-button>
                    </div>
                </div>
                
                <div class="slider-item">
                    <div class="tool-card">
                        <h3>Content Generator Suite</h3>
                        <p>Genereer professionele content voor alle kanalen</p>
                        <slimmer-button href="tools.php?id=content-generator">Meer informatie</slimmer-button>
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
                    <slimmer-button href="e-learnings.php?id=ai-basics">Bekijk cursus</slimmer-button>
                </div>
            </div>
            
            <div class="course-card">
                <img src="images/prompt-engineering.svg" alt="Effectief Prompten" class="course-image">
                <div class="course-content">
                    <h3>Effectief Prompten: De sleutel tot AI</h3>
                    <p>Leer betere resultaten krijgen uit AI tools met effectieve prompts</p>
                    <slimmer-button href="e-learnings.php?id=effective-prompting">Bekijk cursus</slimmer-button>
                </div>
            </div>
            
            <div class="course-card">
                <img src="images/workflow-automation.svg" alt="AI Workflows" class="course-image">
                <div class="course-content">
                    <h3>AI Workflows: Automatiseer je werk</h3>
                    <p>Ontdek hoe je complexe werkprocessen kunt automatiseren met AI</p>
                    <slimmer-button href="e-learnings.php?id=ai-workflows">Bekijk cursus</slimmer-button>
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