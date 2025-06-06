<?php /** @var string $title @var array $course */ ?>

<a href="#main-content" class="skip-link">Direct naar inhoud</a>

<section class="hero-with-background" aria-labelledby="course-heading">
    <div class="container">
        <div class="hero-content">
            <div class="breadcrumb">
                <a href="/e-learnings">E-learnings</a> / <?= htmlspecialchars($course['name']) ?>
            </div>
            <h1 id="course-heading"><?= htmlspecialchars($course['name']) ?></h1>
            <p><?= htmlspecialchars($course['description']) ?></p>
            <div class="course-meta">
                <span class="course-level"><?= htmlspecialchars($course['level']) ?></span>
                <span class="course-duration"><?= htmlspecialchars($course['duration']) ?></span>
            </div>
            <div class="course-price">
                <?php if (isset($course['originalPrice'])): ?>
                    <span class="price-original">€<?= htmlspecialchars($course['originalPrice']) ?></span>
                <?php endif; ?>
                <span class="price-current">€<?= htmlspecialchars($course['price']) ?></span>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="course-detail-grid">
            <div class="course-detail-main">
                <div class="course-image">
                    <img src="<?= htmlspecialchars($course['image']) ?>" alt="<?= htmlspecialchars($course['name']) ?>">
                </div>
                
                <div class="course-description">
                    <h2>Wat ga je leren?</h2>
                    <p>Deze beginnersvriendelijke cursus is jouw eerste stap in de wereld van AI. Je leert praktische vaardigheden die je direct kunt toepassen in je dagelijkse werk, van het schrijven van effectieve prompts tot het automatiseren van repetitieve taken.</p>
                    
                    <h3>Voor wie is deze cursus?</h3>
                    <ul class="target-audience">
                        <li>Professionals die AI willen gaan gebruiken</li>
                        <li>Managers die hun team willen laten opschalen</li>
                        <li>Ondernemers die concurrentievoordeel zoeken</li>
                        <li>Iedereen die nieuwsgierig is naar praktische AI</li>
                    </ul>

                    <h3>Wat je na deze cursus kunt</h3>
                    <ul class="learning-outcomes">
                        <li>Effectieve prompts schrijven voor ChatGPT en andere AI-tools</li>
                        <li>Je werk automatiseren met AI-assistenten</li>
                        <li>Professionele content genereren met AI</li>
                        <li>AI ethisch en verantwoord inzetten</li>
                        <li>Een persoonlijke AI-workflow opzetten</li>
                    </ul>
                </div>

                <div class="course-curriculum">
                    <h3>Cursusinhoud (8 lessen)</h3>
                    <div class="curriculum-modules">
                        <div class="module">
                            <h4>Module 1: AI Fundamenten</h4>
                            <ul class="lessons">
                                <li>✓ Wat is AI en hoe werkt het?</li>
                                <li>✓ ChatGPT, Claude en andere AI-tools</li>
                                <li>✓ Ethische overwegingen</li>
                            </ul>
                        </div>
                        <div class="module">
                            <h4>Module 2: Prompt Engineering</h4>
                            <ul class="lessons">
                                <li>✓ De anatomie van een goede prompt</li>
                                <li>✓ Context en specificiteit</li>
                                <li>✓ Prompt templates en best practices</li>
                            </ul>
                        </div>
                        <div class="module">
                            <h4>Module 3: Praktische Toepassingen</h4>
                            <ul class="lessons">
                                <li>✓ AI voor emailcommunicatie</li>
                                <li>✓ Content creatie en brainstorming</li>
                                <li>✓ Data analyse en rapportage</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="course-features">
                    <h3>Wat krijg je</h3>
                    <div class="features-grid">
                        <?php foreach ($course['features'] as $feature): ?>
                            <div class="feature-item">
                                <span class="feature-icon">✓</span>
                                <span class="feature-text"><?= htmlspecialchars($feature) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="course-detail-sidebar">
                <div class="purchase-card">
                    <div class="purchase-header">
                        <h3><?= htmlspecialchars($course['name']) ?></h3>
                        <div class="price-display">
                            <?php if (isset($course['originalPrice'])): ?>
                                <span class="price-original">€<?= htmlspecialchars($course['originalPrice']) ?></span>
                            <?php endif; ?>
                            <div class="price-current">
                                <span class="currency">€</span>
                                <span class="amount"><?= htmlspecialchars($course['price']) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="course-info">
                        <div class="info-item">
                            <span class="info-label">Niveau:</span>
                            <span class="info-value"><?= htmlspecialchars($course['level']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Duur:</span>
                            <span class="info-value"><?= htmlspecialchars($course['duration']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Toegang:</span>
                            <span class="info-value">Levenslang</span>
                        </div>
                    </div>
                    
                    <div class="purchase-actions">
                        <a href="/betalen?course=<?= htmlspecialchars($course['id']) ?>" class="btn btn-primary btn-large">Nu starten</a>
                        <button class="btn btn-outline btn-large add-to-cart-btn" 
                            data-product-id="<?= htmlspecialchars($course['id']) ?>"
                            data-product-type="course"
                            data-product-name="<?= htmlspecialchars($course['name']) ?>"
                            data-product-price="<?= htmlspecialchars($course['price']) ?>"
                            data-product-img="<?= htmlspecialchars($course['image']) ?>">
                            Toevoegen aan winkelwagen
                        </button>
                    </div>

                    <div class="guarantee">
                        <div class="guarantee-icon">🛡️</div>
                        <div class="guarantee-text">
                            <strong>30 dagen geld terug garantie</strong>
                            <p>Niet tevreden? Krijg je geld terug, geen vragen.</p>
                        </div>
                    </div>
                </div>

                <div class="support-card">
                    <h4>Hulp nodig?</h4>
                    <p>Ons team staat klaar om je te helpen met je leertraject.</p>
                    <a href="/over-mij" class="btn btn-outline">Contact opnemen</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section testimonials-section">
    <div class="container">
        <h2>Wat cursisten zeggen</h2>
        <div class="testimonial-grid">
            <div class="testimonial-card">
                <p>"Perfect voor beginners! Ik ga nu veel zelfverzekerder om met AI-tools en bespaar dagelijks tijd."</p>
                <div class="testimonial-author">
                    <div class="author-info">
                        <h4>Sandra Vermeulen</h4>
                        <p>HR Manager, TechStart</p>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <p>"De praktische oefeningen maken het verschil. Ik kan nu professionele content maken met AI."</p>
                <div class="testimonial-author">
                    <div class="author-info">
                        <h4>Mike van der Berg</h4>
                        <p>Marketing Specialist, CreativeAgency</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section cta-section">
    <div class="container">
        <div class="cta-container">
            <h3>Klaar om te beginnen?</h3>
            <p class="cta-text">Start vandaag nog met AI Basics en ontdek hoe AI jouw werk kan transformeren.</p>
            <div class="cta-buttons">
                <a href="/betalen?course=<?= htmlspecialchars($course['id']) ?>" class="btn btn-primary">Nu starten</a>
                <a href="/e-learnings" class="btn btn-outline">Bekijk andere cursussen</a>
            </div>
        </div>
    </div>
</section> 