<?php /** @var string $title @var array $tool */ ?>

<a href="#main-content" class="skip-link">Direct naar inhoud</a>

<section class="hero-with-background" aria-labelledby="tool-heading">
    <div class="container">
        <div class="hero-content">
            <div class="breadcrumb">
                <a href="/tools">AI Tools</a> / <?= htmlspecialchars($tool['name']) ?>
            </div>
            <h1 id="tool-heading"><?= htmlspecialchars($tool['name']) ?></h1>
            <p><?= htmlspecialchars($tool['description']) ?></p>
            <div class="tool-price">
                <span class="price-label">Prijs:</span>
                <span class="price">€<?= htmlspecialchars($tool['price']) ?></span>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="tool-detail-grid">
            <div class="tool-detail-main">
                <div class="tool-image">
                    <img src="<?= htmlspecialchars($tool['image']) ?>" alt="<?= htmlspecialchars($tool['name']) ?> Icon">
                </div>
                
                <div class="tool-description">
                    <h2>Wat kan deze tool voor jou doen?</h2>
                    <p>Onze Email Assistent Plus revolutioneert de manier waarop je communiceert. Met geavanceerde AI-technologie help je bij het opstellen van professionele emails, het beantwoorden van berichten en het beheren van je inbox.</p>
                    
                    <h3>Belangrijkste voordelen</h3>
                    <ul class="benefits-list">
                        <li><strong>Tijdsbesparing:</strong> Bespaar tot 5 uur per week op emailcommunicatie</li>
                        <li><strong>Professionaliteit:</strong> Consistente en professionele toon in alle berichten</li>
                        <li><strong>Meertalig:</strong> Ondersteuning voor Nederlands, Engels, Duits en Frans</li>
                        <li><strong>Integratie:</strong> Werkt naadloos met Gmail, Outlook en andere email clients</li>
                    </ul>
                </div>

                <div class="tool-features">
                    <h3>Functies</h3>
                    <div class="features-grid">
                        <?php foreach ($tool['features'] as $feature): ?>
                            <div class="feature-item">
                                <span class="feature-icon">✓</span>
                                <span class="feature-text"><?= htmlspecialchars($feature) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="tool-demo">
                    <h3>Hoe werkt het?</h3>
                    <div class="demo-steps">
                        <div class="demo-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Upload je email context</h4>
                                <p>Geef de AI context over het onderwerp en de gewenste toon van je email.</p>
                            </div>
                        </div>
                        <div class="demo-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>AI genereert concept</h4>
                                <p>Binnen seconden krijg je een professioneel email concept dat je kunt aanpassen.</p>
                            </div>
                        </div>
                        <div class="demo-step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>Verstuur direct</h4>
                                <p>Kopieer en plak de email of gebruik de directe integratie met je email client.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tool-detail-sidebar">
                <div class="purchase-card">
                    <div class="purchase-header">
                        <h3><?= htmlspecialchars($tool['name']) ?></h3>
                        <div class="price-display">
                            <span class="currency">€</span>
                            <span class="amount"><?= htmlspecialchars($tool['price']) ?></span>
                        </div>
                    </div>
                    
                    <div class="purchase-actions">
                        <a href="/betalen?tool=<?= htmlspecialchars($tool['id']) ?>" class="btn btn-primary btn-large">Nu kopen</a>
                        <button class="btn btn-outline btn-large add-to-cart-btn" 
                            data-product-id="<?= htmlspecialchars($tool['id']) ?>"
                            data-product-type="tool"
                            data-product-name="<?= htmlspecialchars($tool['name']) ?>"
                            data-product-price="<?= htmlspecialchars($tool['price']) ?>"
                            data-product-img="<?= htmlspecialchars($tool['image']) ?>">
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
                    <p>Ons team staat klaar om je te helpen met onze tools.</p>
                    <a href="/over-mij" class="btn btn-outline">Contact opnemen</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section testimonials-section">
    <div class="container">
        <h2>Wat anderen zeggen</h2>
        <div class="testimonial-grid">
            <div class="testimonial-card">
                <p>"De Email Assistent heeft mijn productiviteit enorm verhoogd. Ik schrijf nu veel professionelere emails in de helft van de tijd."</p>
                <div class="testimonial-author">
                    <div class="author-info">
                        <h4>Marina van der Berg</h4>
                        <p>Sales Manager, TechCorp</p>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <p>"Perfect voor niet-native speakers. De AI zorgt ervoor dat mijn Nederlandse emails altijd correct en professioneel zijn."</p>
                <div class="testimonial-author">
                    <div class="author-info">
                        <h4>James Wilson</h4>
                        <p>Project Manager, International B.V.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section cta-section">
    <div class="container">
        <div class="cta-container">
            <h3>Klaar om te starten?</h3>
            <p class="cta-text">Probeer de Email Assistent Plus vandaag nog en ervaar zelf hoe AI je emailcommunicatie kan verbeteren.</p>
            <div class="cta-buttons">
                <a href="/betalen?tool=<?= htmlspecialchars($tool['id']) ?>" class="btn btn-primary">Nu kopen</a>
                <a href="/tools" class="btn btn-outline">Bekijk andere tools</a>
            </div>
        </div>
    </div>
</section> 