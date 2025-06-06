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
                    <h2>Maak professionele content met AI</h2>
                    <p>Leer hoe je AI inzet voor het creëren van blogs, social media posts, presentaties en andere marketing materialen die je merk versterken.</p>
                    
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
                </div>
            </div>
        </div>
    </div>
</section> 