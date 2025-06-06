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
                    <p>Document Analyzer 2.0 transformeert de manier waarop je juridische en zakelijke documenten analyseert. Met geavanceerde AI krijg je binnen seconden een volledig overzicht van contracten, overeenkomsten en andere belangrijke documenten.</p>
                    
                    <h3>Belangrijkste voordelen</h3>
                    <ul class="benefits-list">
                        <li><strong>Tijdsbesparing:</strong> Analyseer documenten 10x sneller dan handmatig</li>
                        <li><strong>Risico detectie:</strong> Automatische identificatie van potentiële risico's</li>
                        <li><strong>Compliance:</strong> Check op juridische compliance en best practices</li>
                        <li><strong>Samenvatting:</strong> Krijg kernpunten in begrijpelijke taal</li>
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
                                <h4>Upload je document</h4>
                                <p>Sleep je PDF of Word document naar de analyzer of kies het bestand.</p>
                            </div>
                        </div>
                        <div class="demo-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>AI analyseert document</h4>
                                <p>Onze AI leest het document en identificeert belangrijke clausules en risico's.</p>
                            </div>
                        </div>
                        <div class="demo-step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>Ontvang gedetailleerd rapport</h4>
                                <p>Krijg een duidelijke samenvatting met risico's, aanbevelingen en actiepunten.</p>
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
                <p>"Onmisbaar voor onze juridische afdeling. We spotten nu risico's die we vroeger over het hoofd zagen."</p>
                <div class="testimonial-author">
                    <div class="author-info">
                        <h4>Emma de Vries</h4>
                        <p>Legal Counsel, InnovateLaw</p>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <p>"De contractanalyse die vroeger dagen duurde, doe ik nu in enkele uren. Fantastisch hulpmiddel."</p>
                <div class="testimonial-author">
                    <div class="author-info">
                        <h4>Robert Janssen</h4>
                        <p>Procurement Manager, BuildCorp</p>
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
            <p class="cta-text">Probeer Document Analyzer 2.0 vandaag nog en ervaar zelf hoe AI je documentanalyse kan versnellen.</p>
            <div class="cta-buttons">
                <a href="/betalen?tool=<?= htmlspecialchars($tool['id']) ?>" class="btn btn-primary">Nu kopen</a>
                <a href="/tools" class="btn btn-outline">Bekijk andere tools</a>
            </div>
        </div>
    </div>
</section> 