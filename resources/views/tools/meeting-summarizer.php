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
                    <p>Meeting Summarizer verandert de manier waarop je vergaderingen bijhoudt. Upload audio of video van je meetings en krijg automatisch een gestructureerde samenvatting met alle belangrijke punten en actiepunten.</p>
                    
                    <h3>Belangrijkste voordelen</h3>
                    <ul class="benefits-list">
                        <li><strong>Tijdsbesparing:</strong> Geen urenlang uitwerken van notities meer</li>
                        <li><strong>Compleet overzicht:</strong> Mis nooit meer belangrijke beslissingen</li>
                        <li><strong>Actiepunten:</strong> Automatische extractie van taken en deadlines</li>
                        <li><strong>Delen:</strong> Stuur samenvattingen direct naar deelnemers</li>
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
                                <h4>Upload meeting opname</h4>
                                <p>Sleep je audio of video bestand naar de tool of maak een directe opname.</p>
                            </div>
                        </div>
                        <div class="demo-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>AI transcribeert en analyseert</h4>
                                <p>Onze AI maakt een transcriptie en identificeert belangrijke onderwerpen.</p>
                            </div>
                        </div>
                        <div class="demo-step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>Ontvang gestructureerde samenvatting</h4>
                                <p>Krijg een overzichtelijk rapport met beslissingen, actiepunten en follow-ups.</p>
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

<section class="section cta-section">
    <div class="container">
        <div class="cta-container">
            <h3>Klaar om te starten?</h3>
            <p class="cta-text">Probeer Meeting Summarizer vandaag nog en mis nooit meer belangrijke informatie uit je vergaderingen.</p>
            <div class="cta-buttons">
                <a href="/betalen?tool=<?= htmlspecialchars($tool['id']) ?>" class="btn btn-primary">Nu kopen</a>
                <a href="/tools" class="btn btn-outline">Bekijk andere tools</a>
            </div>
        </div>
    </div>
</section> 