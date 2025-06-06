<section class="hero-with-background" aria-labelledby="course-heading">
    <div class="container">
        <div class="hero-content">
            <h1 id="course-heading"><?= htmlspecialchars($course_title) ?></h1>
            <p><?= htmlspecialchars($course_description) ?></p>
        </div>
    </div>
</section>

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
    
    <div class="course-meta-container">
        <div class="tags-container">
            <span class="tag"><?= htmlspecialchars($course_level) ?></span>
            <span class="tag online-tag">Online Les</span>
        </div>
        <div class="course-details">
            <span class="course-duration">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <?= htmlspecialchars($course_duration) ?>
            </span>
            <span class="course-by">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Ontwikkeld door <?= htmlspecialchars($course_author) ?>
            </span>
        </div>
    </div>
    
    <!-- Cursus Container -->
    <div class="course-container">
        <div id="course-content" class="course-content" data-course-id="<?= htmlspecialchars($course_id) ?>">
            <!-- Cursusinhoud wordt dynamisch geladen via API -->
        </div>
        
        <div class="lesson-content-container">
            <div class="start-course-message">
                <h2>Welkom bij de <?= htmlspecialchars($course_title) ?> cursus!</h2>
                <p>Selecteer een les in het menu links om te beginnen met leren.</p>
            </div>
        </div>
    </div>
</div>

<!-- Course viewer JavaScript wordt automatisch geladen via Vite -->
<?php
use App\Infrastructure\View\Asset;
echo Asset::js('course-viewer');
?> 