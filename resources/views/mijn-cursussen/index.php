<?php /** @var string $title */ ?>

<a href="#main-content" class="skip-link">Direct naar inhoud</a>

<section class="hero-with-background" aria-labelledby="mijn-cursussen-heading">
    <div class="container">
        <div class="hero-content">
            <h1 id="mijn-cursussen-heading">Mijn Cursussen</h1>
            <p>Beheer je AI-cursussen, bekijk je voortgang en ga verder waar je gebleven was. Alle leermaterialen op één plek.</p>
        </div>
    </div>
</section>

<section class="section" aria-labelledby="progress-overview-title" id="main-content">
    <div class="container">
        <div class="dashboard-header">
            <h2 id="progress-overview-title">Leervoortgang</h2>
            <p>Je huidige voortgang en prestaties</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>0</h3>
                <p>Actieve cursussen</p>
            </div>
            <div class="stat-card">
                <h3>0</h3>
                <p>Voltooide cursussen</p>
            </div>
            <div class="stat-card">
                <h3>0</h3>
                <p>Certificaten behaald</p>
            </div>
            <div class="stat-card">
                <h3>0</h3>
                <p>Leeruren totaal</p>
            </div>
        </div>
    </div>
</section>

<section class="section" aria-labelledby="active-courses-title">
    <div class="container">
        <div class="section-header">
            <h2 id="active-courses-title">Actieve Cursussen</h2>
            <p>Ga verder waar je gebleven was</p>
        </div>
        
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8.5 2.687c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492V2.687zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783z"/>
                </svg>
            </div>
            <h3>Nog geen cursussen gestart</h3>
            <p>Je hebt nog geen cursussen in je bibliotheek. Start vandaag nog met leren en ontdek hoe AI jouw werk kan transformeren.</p>
            <div class="empty-state-actions">
                <a href="/e-learnings" class="btn btn-primary">Bekijk cursussen</a>
                <a href="/dashboard" class="btn btn-outline">Terug naar dashboard</a>
            </div>
        </div>
    </div>
</section>

<section class="section" aria-labelledby="completed-courses-title">
    <div class="container">
        <div class="section-header">
            <h2 id="completed-courses-title">Voltooide Cursussen</h2>
            <p>Je behaalde certificaten en voltooide trainingen</p>
        </div>
        
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M10 2a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-3a1 1 0 0 1-1-1v-1H9a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1h1V2z"/>
                    <path d="M9 9v4a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1zM3 10a1 1 0 1 1 2 0 1 1 0 0 1-2 0zm4 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0z"/>
                </svg>
            </div>
            <h3>Nog geen cursussen voltooid</h3>
            <p>Wanneer je een cursus voltooit, vind je hier je certificaten en je kunt de materialen opnieuw bekijken.</p>
        </div>
    </div>
</section>

<section class="section" aria-labelledby="recommendations-title">
    <div class="container">
        <div class="section-header">
            <h2 id="recommendations-title">Aanbevolen voor jou</h2>
            <p>Cursussen die perfect aansluiten bij jouw niveau en interesses</p>
        </div>
        
        <div class="courses-grid">
            <div class="course-card featured">
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

            <div class="course-card">
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

            <div class="course-card">
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
        </div>
    </div>
</section>

<section class="section cta-section">
    <div class="container">
        <div class="cta-container">
            <h3>Klaar om je AI-reis te beginnen?</h3>
            <p class="cta-text">Ontdek ons complete aanbod van AI-cursussen en vind de perfecte training voor jouw niveau en doelen.</p>
            <div class="cta-buttons">
                <a href="/e-learnings" class="btn btn-primary">Alle cursussen bekijken</a>
                <a href="/over-mij" class="btn btn-outline">Persoonlijk advies</a>
            </div>
        </div>
    </div>
</section> 