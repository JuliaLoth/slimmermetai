<?php
// Stel paginavariabelen in VOOR het includen van de header
$page_title = 'Over Mij | Slimmer met AI';
$page_description = 'Leer meer over Julia Loth, AI-expert en maker van Slimmer met AI. Ontdek mijn achtergrond en expertise in kunstmatige intelligentie.';
$active_page = 'over-mij';

// Laad header (deze laadt init.php, head, navbar en opent <main>)
require_once __DIR__ . '/includes/header.php';
?>

<!-- Pagina specifieke content voor Over Mij -->
<slimmer-hero 
    title="Van innovator naar AI-expert" 
    subtitle="Herkenbaar? Je weet dat AI enorme kansen biedt, maar tussen alle buzzwords en technische termen vraag je je af: hoe vertaal ik dit concreet naar mijn dagelijkse werk? Precies daar ligt mijn expertise."
    background="image"
    image-url="images/hero-background.svg"
    centered>
    <div slot="actions">
        <slimmer-button href="https://nl.linkedin.com/in/julialoth" type="primary">Connect op LinkedIn</slimmer-button>
    </div>
</slimmer-hero>

<section class="section" aria-labelledby="about-profile-title">
    <div class="container">
        <div class="section-header">
            <h2 id="about-profile-title">Over mijzelf</h2>
            <p>Als innovator vertaal ik complexe vraagstukken naar concrete oplossingen</p>
        </div>
        
        <div class="about-profile">
            <div class="profile-image-container">
                <img src="<?php echo asset_url('images/Profiel foto Julia.svg'); ?>" alt="Profielfoto van Julia Loth" class="profile-image">
            </div>
            <div class="profile-content">
                <p class="lead">Met mijn hands-on mentaliteit breng ik communicatie, concept en techniek samen om vernieuwing te realiseren die Ã©cht werkt in de praktijk.</p>
                <p>In mijn professionele reis heb ik me gespecialiseerd in het toegankelijk maken van geavanceerde technologie. Ik geloof in:</p>
                <ul class="mission-list" role="list">
                    <li>Praktische AI-toepassingen die direct implementeerbaar zijn</li>
                    <li>Meetbare resultaten in plaats van vage beloftes</li>
                    <li>Technologie als versterker van menselijke creativiteit en productiviteit</li>
                    <li>Kennisdeling die professionals op elk niveau helpt mee te komen</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section" aria-labelledby="background-title">
    <div class="container">
        <div class="section-header">
            <h2 id="background-title">Mijn professionele achtergrond</h2>
            <p>Door jarenlange ervaring met digitale transformatie heb ik geleerd hoe je technologie succesvol integreert</p>
        </div>
        
        <div class="background-content">
            <p>Mijn expertise in AI komt voort uit een solide achtergrond in digitale innovatie. Door projecten in verschillende sectoren heb ik inzicht gekregen in de uitdagingen waar organisaties tegenaan lopen bij het implementeren van nieuwe technologieÃ«n.</p>
            <p>Deze ervaring heeft me geleerd dat succesvolle AI-implementatie niet alleen draait om de techniek, maar vooral om de mensen die ermee werken. Mijn aanpak combineert daarom technische kennis met praktische toepasbaarheid en mensgerichte implementatie.</p>
        </div>
    </div>
</section>
    
<slimmer-footer></slimmer-footer>

</main>
</body>
</html> 

