<?php
// Stel paginavariabelen in VOOR het includen van de header
$page_title = 'Nieuws & Blog | Slimmer met AI';
$page_description = 'Blijf op de hoogte van de laatste ontwikkelingen in AI en ontdek praktische tips voor het gebruik van AI in je dagelijkse werk.';
$active_page = 'nieuws';

// Extra head content specifiek voor deze pagina (styles & Substack config)
ob_start();
?>
<style>
    /* Extra styling specifiek voor de nieuws pagina */
    .blog-post {
        margin-bottom: 3rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .blog-post:last-child {
        border-bottom: none;
    }
    
    .blog-post-title {
        font-size: 1.8rem;
        margin-bottom: 0.5rem;
    }
    
    .blog-post-meta {
        color: #666;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }
    
    .blog-post-excerpt {
        margin-bottom: 1rem;
    }
    
    .news-grid {
        margin-top: 2rem;
    }
    
    .news-section-header {
        text-align: center;
        max-width: 800px;
        margin: 0 auto 2rem;
    }
    
    /* Styling voor de custom substack feed */
    .custom-substack-feed {
        margin-bottom: 2rem;
    }
    
    .custom-post-item {
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #eaeaea;
    }
    
    .custom-post-item:last-child {
        border-bottom: none;
    }
    
    .post-title {
        margin-bottom: 0.5rem;
        font-size: 1.4rem;
    }
    
    .post-meta {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 0.75rem;
    }
    
    .post-excerpt {
        margin-bottom: 0.75rem;
        line-height: 1.5;
    }
    
    .post-read-more {
        display: inline-block;
        color: #007bff;
        font-weight: 500;
    }
    
    .loading-spinner {
        text-align: center;
        padding: 2rem;
        color: #666;
    }
    
    .fallback-content {
        text-align: center;
        padding: 2rem;
        background-color: #f9f9f9;
        border-radius: 8px;
    }
    
    /* Fix voor de nieuwsbrief button uitlijning */
    .newsletter-signup {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        max-width: 500px;
    }
    
    .newsletter-signup .btn {
        width: auto;
        min-width: 250px;
    }
</style>
<script>
  window.SubstackFeedWidget = {
    substackUrl: "slimmermetai.substack.com",
    posts: 5,
    filter: "top"
  };
</script>
<script src="https://substackapi.com/embeds/feed.js" async></script>
<?php
$extra_head = ob_get_clean();

// Laad header (deze laadt init.php, head, navbar en opent <main>)
require_once __DIR__ . '/includes/header.php';
?>

<!-- Pagina specifieke content voor Nieuws -->
<slimmer-hero 
    title="Nieuws & Blog" 
    subtitle="Blijf op de hoogte van de laatste ontwikkelingen in AI en ontdek praktische tips voor het gebruik van AI in je dagelijkse werk."
    background="image"
    image-url="images/hero-background.svg"
    centered>
</slimmer-hero>

<section class="section news-grid" aria-labelledby="latest-news">
    <div class="container">
        <div class="news-section-header">
            <h2 id="latest-news">Laatste Nieuws</h2>
            <p>Ontdek onze nieuwste artikelen en updates</p>
        </div>
        
        <div class="substack-feed-wrapper">
            <!-- Directe Substack feed zonder iframe -->
            <div id="substack-feed-embed"></div>
            
            <!-- Fallback voor als de feed niet laadt -->
            <div class="fallback-content" id="fallback-content" style="display: none;">
                <p>Bekijk onze nieuwste artikelen en updates op <a href="https://slimmermetai.substack.com" target="_blank" rel="noopener">Slimmer met AI Substack</a>.</p>
            </div>
        </div>
    </div>
</section>

<section class="section newsletter" aria-labelledby="newsletter">
    <div class="container">
        <div class="section-header">
            <h2 id="newsletter">Blijf op de hoogte</h2>
            <p>Ontvang updates over nieuwe tools en e-learnings</p>
        </div>
        
        <div class="newsletter-form">
            <!-- Nieuwsbrief aanmeld widget met iframe -->
            <div class="newsletter-signup">
                <iframe src="https://slimmermetai.substack.com/embed" width="100%" height="150" style="border:1px solid #EEE; background:white; max-width: 480px; margin: 0 auto; display: block;" frameborder="0" scrolling="no"></iframe>
            </div>
        </div>
    </div>
</section>
    
<slimmer-footer></slimmer-footer>

</main>

<!-- Script om te controleren of de Substack feed geladen is -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Stel een timeout in van 5 seconden
        setTimeout(function() {
            const substackEmbed = document.getElementById('substack-feed-embed');
            const fallbackContent = document.getElementById('fallback-content');
            
            // Als er geen content is, toon de fallback
            if (substackEmbed && substackEmbed.innerHTML.trim() === '') {
                fallbackContent.style.display = 'block';
            }
        }, 5000);
    });
</script>
</body>
</html> 

