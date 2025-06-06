<div class="hero-banner e-learning-banner">
    <div class="container">
        <h1>Slimmer met AI E-Learning Platform</h1>
        <p>Ontwikkel je AI-vaardigheden met onze interactieve cursussen</p>
    </div>
</div>

<div class="container">
    <div class="breadcrumbs">
        <?php foreach ($breadcrumbs as $crumb): ?>
            <?php if ($crumb['url']): ?>
                <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['label']) ?></a>
            <?php else: ?>
                <span><?= htmlspecialchars($crumb['label']) ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <div class="dashboard-section">
        <h2>Mijn Dashboard</h2>
        
        <div class="dashboard-cards">
            <div class="dashboard-card">
                <div class="card-icon">📊</div>
                <div class="card-info">
                    <h3>Mijn Voortgang</h3>
                    <p>Bekijk je leervoortgang</p>
                </div>
                <a href="#my-courses" class="card-link">Bekijken</a>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon">🏆</div>
                <div class="card-info">
                    <h3>Certificaten</h3>
                    <p>Bekijk je behaalde certificaten</p>
                </div>
                <a href="#certificates" class="card-link">Bekijken</a>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon">🔍</div>
                <div class="card-info">
                    <h3>Nieuwe Cursussen</h3>
                    <p>Ontdek nieuwe leermogelijkheden</p>
                </div>
                <a href="/ai-cursussen" class="card-link">Ontdekken</a>
            </div>
        </div>
    </div>
    
    <div id="my-courses" class="my-courses-section">
        <h2>Mijn Cursussen</h2>
        <div class="my-courses-grid">
            <div class="loading-courses">Cursussen laden...</div>
        </div>
    </div>
    
    <div id="certificates" class="certificates-section">
        <h2>Mijn Certificaten</h2>
        <div class="certificates-grid">
            <!-- Dynamisch geladen via API -->
        </div>
    </div>
    
    <div id="available-courses" class="available-courses-section">
        <h2>Beschikbare Cursussen</h2>
        <div class="available-courses-grid">
            <!-- Dynamisch geladen via API -->
        </div>
    </div>
</div>

<!-- E-learning CSS component wordt automatisch geladen via Vite -->
<?php
use App\Infrastructure\View\Asset;
echo Asset::js('e-learning-dashboard');
?> 