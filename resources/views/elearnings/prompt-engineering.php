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
                    <h2>Master de kunst van prompt engineering</h2>
                    <p>Deze geavanceerde cursus brengt je prompt skills naar het volgende niveau. Leer geavanceerde technieken die de profs gebruiken om consistente, hoogwaardige output uit AI-modellen te halen.</p>
                    
                    <h3>Voor wie is deze cursus?</h3>
                    <ul class="target-audience">
                        <li>Professionals die al ervaring hebben met AI-tools</li>
                        <li>Content creators die hun output willen optimaliseren</li>
                        <li>Consultants die AI voor klanten inzetten</li>
                        <li>Iedereen die maximale waarde uit AI wil halen</li>
                    </ul>

                    <h3>Wat je na deze cursus kunt</h3>
                    <ul class="learning-outcomes">
                        <li>Geavanceerde prompt technieken zoals chain-of-thought beheersen</li>
                        <li>Role-playing prompts voor specifieke scenario's opstellen</li>
                        <li>Complexe AI-workflows bouwen met meerdere stappen</li>
                        <li>Prompt libraries beheren en optimaliseren</li>
                        <li>AI-output evalueren en verfijnen</li>
                    </ul>
                </div>

                <div class="course-curriculum">
                    <h3>Cursusinhoud (12 modules)</h3>
                    <div class="curriculum-modules">
                        <div class="module">
                            <h4>Module 1-3: Fundamentele Technieken</h4>
                            <ul class="lessons">
                                <li>✓ Chain-of-thought prompting</li>
                                <li>✓ Few-shot en zero-shot learning</li>
                                <li>✓ Temperature en top-p optimalisatie</li>
                            </ul>
                        </div>
                        <div class="module">
                            <h4>Module 4-8: Geavanceerde Methoden</h4>
                            <ul class="lessons">
                                <li>✓ Role-playing en persona development</li>
                                <li>✓ Multi-step reasoning</li>
                                <li>✓ Prompt chaining en workflows</li>
                                <li>✓ Error handling en fallbacks</li>
                            </ul>
                        </div>
                        <div class="module">
                            <h4>Module 9-12: Praktische Implementatie</h4>
                            <ul class="lessons">
                                <li>✓ Industry-specific prompt libraries</li>
                                <li>✓ A/B testing voor prompts</li>
                                <li>✓ Scaling en automatisering</li>
                                <li>✓ ROI meting en optimalisatie</li>
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
                        <div class="info-item">
                            <span class="info-label">Certificaat:</span>
                            <span class="info-value">Ja</span>
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

<section class="section cta-section">
    <div class="container">
        <div class="cta-container">
            <h3>Klaar voor het volgende niveau?</h3>
            <p class="cta-text">Neem je prompt skills mee naar een professioneel niveau en haal het maximum uit AI.</p>
            <div class="cta-buttons">
                <a href="/betalen?course=<?= htmlspecialchars($course['id']) ?>" class="btn btn-primary">Nu starten</a>
                <a href="/e-learnings" class="btn btn-outline">Bekijk andere cursussen</a>
            </div>
        </div>
    </div>
</section> 