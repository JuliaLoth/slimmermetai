<?php /** @var string $title */ ?>

<a href="#main-content" class="skip-link">Direct naar inhoud</a>

<section class="hero-with-background" aria-labelledby="cursussen-heading">
    <div class="container">
        <div class="hero-content">
            <h1 id="cursussen-heading">AI Cursussen</h1>
            <p>Leer hoe je AI effectief inzet in je dagelijkse werk. Van beginnerscursussen tot geavanceerde technieken - vind de perfecte training voor jouw niveau.</p>
        </div>
    </div>
</section>

<section class="section" id="main-content">
    <div class="container">
        <div class="filters-container">
            <div class="filter-group">
                <h3>Niveau</h3>
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="level" data-value="all">Alle cursussen</button>
                    <button class="filter-btn" data-filter="level" data-value="beginner">Beginners</button>
                    <button class="filter-btn" data-filter="level" data-value="intermediate">Gevorderd</button>
                    <button class="filter-btn" data-filter="level" data-value="advanced">Expert</button>
                </div>
            </div>
            
            <div class="filter-group">
                <h3>Type training</h3>
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="type" data-value="all">Alle types</button>
                    <button class="filter-btn" data-filter="type" data-value="e-learning">E-learning</button>
                    <button class="filter-btn" data-filter="type" data-value="webinar">Webinar</button>
                    <button class="filter-btn" data-filter="type" data-value="consultation">1 op 1 advies</button>
                </div>
            </div>
            
            <div class="filter-results">
                <span id="results-count">6 cursussen gevonden</span>
                <button id="clear-filters" class="btn-link" style="display: none;">Alle filters wissen</button>
            </div>
        </div>

        <div class="courses-grid" id="courses-grid">
            <div class="course-card featured" data-level="beginner" data-type="e-learning">
                <div class="course-image">
                    <img src="/images/ai-basics-course.svg" alt="AI Basics Cursus">
                    <div class="course-badge popular">Populair</div>
                    <div class="course-labels">
                        <span class="label-level beginner">Beginner</span>
                        <span class="label-type">E-learning</span>
                    </div>
                </div>
                <div class="course-content">
                    <div class="course-meta">
                        <span class="course-duration">4 uur</span>
                        <span class="course-modules">8 modules</span>
                    </div>
                    <h3>AI Basics voor Professionals</h3>
                    <p>De perfecte startcursus voor iedereen die AI wil gaan gebruiken in hun werk. Leer de basis van ChatGPT, prompting en praktische toepassingen.</p>
                    <div class="course-features">
                        <ul>
                            <li>8 praktische lessen</li>
                            <li>Hands-on oefeningen</li>
                            <li>Certificaat</li>
                            <li>Levenslange toegang</li>
                        </ul>
                    </div>
                    <div class="course-price">
                        <span class="price-current">€97</span>
                        <span class="price-original">€149</span>
                        <span class="discount-badge">-35%</span>
                    </div>
                    <div class="course-actions">
                        <a href="/e-learnings/ai-basics" class="btn btn-primary">Meer informatie</a>
                        <button class="btn btn-outline add-to-cart-btn" 
                            data-product-id="ai-basics"
                            data-product-type="course"
                            data-product-name="AI Basics voor Professionals"
                            data-product-price="97.00"
                            data-product-img="/images/ai-basics-course.svg">
                            Toevoegen aan winkelwagen
                        </button>
                    </div>
                </div>
            </div>

            <div class="course-card" data-level="intermediate" data-type="e-learning">
                <div class="course-image">
                    <img src="/images/prompt-engineering-course.svg" alt="Prompt Engineering Cursus">
                    <div class="course-labels">
                        <span class="label-level intermediate">Gevorderd</span>
                        <span class="label-type">E-learning</span>
                    </div>
                </div>
                <div class="course-content">
                    <div class="course-meta">
                        <span class="course-duration">6 uur</span>
                        <span class="course-modules">12 modules</span>
                    </div>
                    <h3>Advanced Prompt Engineering</h3>
                    <p>Ontdek geavanceerde prompt technieken om maximale resultaten uit AI te halen. Van chain-of-thought tot role-playing prompts.</p>
                    <div class="course-features">
                        <ul>
                            <li>12 geavanceerde technieken</li>
                            <li>150+ prompt templates</li>
                            <li>Real-world cases</li>
                            <li>Expert feedback</li>
                        </ul>
                    </div>
                    <div class="course-price">
                        <span class="price-current">€197</span>
                    </div>
                    <div class="course-actions">
                        <a href="/e-learnings/prompt-engineering" class="btn btn-primary">Meer informatie</a>
                        <button class="btn btn-outline add-to-cart-btn" 
                            data-product-id="prompt-engineering"
                            data-product-type="course"
                            data-product-name="Advanced Prompt Engineering"
                            data-product-price="197.00"
                            data-product-img="/images/prompt-engineering-course.svg">
                            Toevoegen aan winkelwagen
                        </button>
                    </div>
                </div>
            </div>

            <div class="course-card" data-level="intermediate" data-type="webinar">
                <div class="course-image">
                    <img src="/images/ai-automation-course.svg" alt="AI Automatisering Cursus">
                    <div class="course-labels">
                        <span class="label-level intermediate">Gevorderd</span>
                        <span class="label-type">Webinar</span>
                    </div>
                </div>
                <div class="course-content">
                    <div class="course-meta">
                        <span class="course-duration">2 uur live</span>
                        <span class="course-modules">Live + opname</span>
                    </div>
                    <h3>AI Workflow Automatisering</h3>
                    <p>Leer hoe je repetitieve taken automatiseert met AI. Van email management tot rapport generatie - bespaar uren per week.</p>
                    <div class="course-features">
                        <ul>
                            <li>Live webinar sessie</li>
                            <li>Q&A met expert</li>
                            <li>Opname beschikbaar</li>
                            <li>Downloadbare templates</li>
                        </ul>
                    </div>
                    <div class="course-price">
                        <span class="price-current">€97</span>
                    </div>
                    <div class="course-actions">
                        <a href="/e-learnings/ai-automation" class="btn btn-primary">Meer informatie</a>
                        <button class="btn btn-outline add-to-cart-btn" 
                            data-product-id="ai-automation"
                            data-product-type="course"
                            data-product-name="AI Workflow Automatisering"
                            data-product-price="97.00"
                            data-product-img="/images/ai-automation-course.svg">
                            Toevoegen aan winkelwagen
                        </button>
                    </div>
                </div>
            </div>

            <div class="course-card" data-level="advanced" data-type="consultation">
                <div class="course-image">
                    <img src="/images/ai-strategy-course.svg" alt="AI Strategie Cursus">
                    <div class="course-badge premium">Premium</div>
                    <div class="course-labels">
                        <span class="label-level advanced">Expert</span>
                        <span class="label-type">1 op 1 advies</span>
                    </div>
                </div>
                <div class="course-content">
                    <div class="course-meta">
                        <span class="course-duration">3 sessies</span>
                        <span class="course-modules">Persoonlijk</span>
                    </div>
                    <h3>AI Strategie voor Organisaties</h3>
                    <p>Ontwikkel een complete AI-strategie voor je organisatie. Van risicomanagement tot change management en ROI-optimalisatie.</p>
                    <div class="course-features">
                        <ul>
                            <li>3x 1-op-1 sessies (2u)</li>
                            <li>Persoonlijke strategieplan</li>
                            <li>Risk assessment</li>
                            <li>Follow-up support</li>
                        </ul>
                    </div>
                    <div class="course-price">
                        <span class="price-current">€497</span>
                    </div>
                    <div class="course-actions">
                        <a href="/e-learnings/ai-strategy" class="btn btn-primary">Meer informatie</a>
                        <button class="btn btn-outline add-to-cart-btn" 
                            data-product-id="ai-strategy"
                            data-product-type="course"
                            data-product-name="AI Strategie voor Organisaties"
                            data-product-price="497.00"
                            data-product-img="/images/ai-strategy-course.svg">
                            Toevoegen aan winkelwagen
                        </button>
                    </div>
                </div>
            </div>

            <div class="course-card" data-level="beginner" data-type="e-learning">
                <div class="course-image">
                    <img src="/images/ai-content-course.svg" alt="AI Content Creatie Cursus">
                    <div class="course-labels">
                        <span class="label-level beginner">Beginner</span>
                        <span class="label-type">E-learning</span>
                    </div>
                </div>
                <div class="course-content">
                    <div class="course-meta">
                        <span class="course-duration">5 uur</span>
                        <span class="course-modules">10 modules</span>
                    </div>
                    <h3>Content Creatie met AI</h3>
                    <p>Maak professionele content met AI. Van blog posts tot social media, presentaties en marketing materiaal.</p>
                    <div class="course-features">
                        <ul>
                            <li>Content templates</li>
                            <li>Brand consistency</li>
                            <li>SEO optimalisatie</li>
                            <li>Multi-platform publishing</li>
                        </ul>
                    </div>
                    <div class="course-price">
                        <span class="price-current">€147</span>
                    </div>
                    <div class="course-actions">
                        <a href="/e-learnings/ai-content" class="btn btn-primary">Meer informatie</a>
                        <button class="btn btn-outline add-to-cart-btn" 
                            data-product-id="ai-content"
                            data-product-type="course"
                            data-product-name="Content Creatie met AI"
                            data-product-price="147.00"
                            data-product-img="/images/ai-content-course.svg">
                            Toevoegen aan winkelwagen
                        </button>
                    </div>
                </div>
            </div>

            <div class="course-card" data-level="intermediate" data-type="webinar">
                <div class="course-image">
                    <img src="/images/ai-data-course.svg" alt="AI Data Analyse Cursus">
                    <div class="course-labels">
                        <span class="label-level intermediate">Gevorderd</span>
                        <span class="label-type">Webinar</span>
                    </div>
                </div>
                <div class="course-content">
                    <div class="course-meta">
                        <span class="course-duration">2.5 uur live</span>
                        <span class="course-modules">Live + opname</span>
                    </div>
                    <h3>Data Analyse met AI</h3>
                    <p>Transformeer ruwe data naar actionable insights met AI. Leer data visualisatie, trend analyse en predictive modelling.</p>
                    <div class="course-features">
                        <ul>
                            <li>Live data analyse demo</li>
                            <li>Interactieve worksheets</li>
                            <li>Tools & templates</li>
                            <li>Community toegang</li>
                        </ul>
                    </div>
                    <div class="course-price">
                        <span class="price-current">€127</span>
                    </div>
                    <div class="course-actions">
                        <a href="/e-learnings/ai-data" class="btn btn-primary">Meer informatie</a>
                        <button class="btn btn-outline add-to-cart-btn" 
                            data-product-id="ai-data"
                            data-product-type="course"
                            data-product-name="Data Analyse met AI"
                            data-product-price="127.00"
                            data-product-img="/images/ai-data-course.svg">
                            Toevoegen aan winkelwagen
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="no-results" class="no-results" style="display: none;">
            <div class="no-results-content">
                <h3>Geen cursussen gevonden</h3>
                <p>Er zijn geen cursussen die voldoen aan je filters. Probeer andere filterinstellingen.</p>
                <button class="btn btn-primary" onclick="clearAllFilters()">Alle filters wissen</button>
            </div>
        </div>
    </div>
</section>

<section class="section testimonials">
    <div class="container">
        <div class="section-header">
            <h2>Wat onze cursisten zeggen</h2>
            <p>Professionals die succesvol AI hebben geïmplementeerd in hun werk.</p>
        </div>
        
        <div class="testimonial-grid">
            <div class="testimonial-card">
                <p>"De AI Basics cursus heeft mijn carrière veranderd. Ik bespaar nu 15 uur per week en kan me focussen op strategisch werk."</p>
                <div class="testimonial-author">
                    <img src="/images/testimonial-course-1.svg" alt="Sarah van der Berg">
                    <div class="author-info">
                        <h4>Sarah van der Berg</h4>
                        <p>Project Manager, TechCorp</p>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card">
                <p>"De prompt engineering technieken zijn goud waard. Mijn AI-output is dramatisch verbeterd en veel specifieker geworden."</p>
                <div class="testimonial-author">
                    <img src="/images/testimonial-course-2.svg" alt="Mark Jansen">
                    <div class="author-info">
                        <h4>Mark Jansen</h4>
                        <p>Marketing Director, CreativeAgency</p>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card">
                <p>"Dankzij de automatiseringscursus heb ik mijn hele workflow geoptimaliseerd. ROI was binnen 3 weken terugverdiend."</p>
                <div class="testimonial-author">
                    <img src="/images/testimonial-course-3.svg" alt="Linda Vermeer">
                    <div class="author-info">
                        <h4>Linda Vermeer</h4>
                        <p>Operations Manager, LogisticsPro</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section cta-section">
    <div class="container">
        <div class="cta-container">
            <h3>Nog vragen over onze cursussen?</h3>
            <p class="cta-text">Neem contact op voor persoonlijk advies over welke cursus het beste bij jouw doelen en niveau past.</p>
            <div class="cta-buttons">
                <a href="/over-mij" class="btn btn-primary">Contact opnemen</a>
                <a href="/tools" class="btn btn-outline">Bekijk Tools</a>
            </div>
        </div>
    </div>
</section>

<script>
// Course filtering functionaliteit is verplaatst naar resources/js/core/course-filters.js
// en wordt automatisch geladen via main.js
</script> 