<?php /** @var string $title */ ?>

<a href="#main-content" class="skip-link">Direct naar inhoud</a>

<section class="hero-with-background" aria-labelledby="nieuws-heading">
    <div class="container">
        <div class="hero-content">
            <h1 id="nieuws-heading">AI Nieuws & Tips</h1>
            <p>Blijf op de hoogte van de laatste ontwikkelingen in AI en krijg praktische tips om slimmer te werken. Geen buzzwords, gewoon concrete tijdsbesparende hacks.</p>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <!-- Substack Feed Widget - boven aan -->
        <div class="substack-feed-section">
            <h3>Meest gelezen artikelen</h3>
            <p>Ontdek de populairste artikelen uit onze AI-nieuwsbrief met praktische tips en inzichten.</p>
            <div id="substack-feed-embed"></div>
        </div>

        <div class="newsletter-section">
            <div class="newsletter-card">
                <h3>Wekelijkse AI-tips in je inbox</h3>
                <p>Ontvang elke week praktische tips die je direct kunt toepassen in je werk. Geen theorie, gewoon tools en technieken die écht werken.</p>
                <div class="newsletter-signup">
                    <iframe src="https://slimmermetai.substack.com/embed" width="600" height="250" style="border:1px solid #EEE; background:white;" frameborder="0" scrolling="no"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section cta-section">
    <div class="container">
        <div class="cta-container">
            <h3>Wil je meer praktische AI-tips?</h3>
            <p class="cta-text">Ontdek onze e-learnings en tools om direct aan de slag te gaan met AI in jouw werk.</p>
            <div class="cta-buttons">
                <a href="/e-learnings" class="btn btn-primary">Bekijk Cursussen</a>
                <a href="/tools" class="btn btn-outline">Ontdek Tools</a>
            </div>
        </div>
    </div>
</section>

<!-- Substack Feed Widget Scripts -->
<script>
  window.SubstackFeedWidget = {
    substackUrl: "slimmermetai.substack.com",
    posts: 3,
    filter: "top"
  };
</script>
<script src="https://substackapi.com/embeds/feed.js" async></script> 