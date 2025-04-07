<?php
// Definieer paden voor de productieomgeving
define('PUBLIC_INCLUDES', __DIR__ . '/includes');

// Helper functie voor includes
function include_public($file) {
    return include PUBLIC_INCLUDES . '/' . $file;
}

// Helper functie voor asset URLs
function asset_url($path) {
    return '//' . $_SERVER['HTTP_HOST'] . '/' . ltrim($path, '/');
}

// Stel paginatitel en beschrijving in
$page_title = ' $args[0].ToString().ToUpper() ijn cursussen | Slimmer met AI';
$page_description = 'Slimmer met AI -  $args[0].ToString().ToUpper() ijn cursussen';
?>
<!DOCTYPE html>
<html lang="nl" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mijn Cursussen | Slimmer met AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="Toegang tot je gekochte cursussen bij Slimmer met AI. Volg je cursussen op je eigen tempo.">
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
    <!-- Laad alle componenten -->
    <script src="<?php echo asset_url('components/ComponentsLoader.js'); ?>"></script>
</head>
<body>
    <a href="#main-content" class="skip-link">Direct naar inhoud</a>
    
    <slimmer-navbar user-logged-in user-name="Gebruiker" active-page="e-learnings"></slimmer-navbar>

    <slimmer-hero 
        title="Mijn Cursussen" 
        subtitle="Alle cursussen die je hebt gekocht op Ã©Ã©n plek. Volg ze in je eigen tempo en houd je voortgang bij."
        background="image"
        image-url="images/hero background def.svg"
        centered>
    </slimmer-hero>

    <!-- Gebruikersstatistieken via component -->
    <slimmer-user-stats></slimmer-user-stats>

    <main id="main-content" class="my-courses-container" role="main">
        <div class="container">
            <div class="courses-list" id="my-courses-container">
                <!-- Cursussen worden hier dynamisch ingeladen -->
                <div class="no-courses-message" id="no-courses-message">
                    <div class="message-content">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <h3>Nog geen cursussen gekocht</h3>
                        <p>Je hebt nog geen cursussen gekocht. Ontdek ons aanbod in de <a href="e-learnings">cursussen sectie</a>.</p>
                        <slimmer-button href="e-learnings" type="primary">Bekijk beschikbare cursussen</slimmer-button>
                    </div>
                </div>
            </div>
            
            <!-- AI Basics Cursus Template (wordt getoond als gekocht) -->
            <template id="course-ai-basics">
                <slimmer-card title="AI Basics">
                    <div class="course-header">
                        <div class="course-icon">
                            <img src="<?php echo asset_url('images/ai-basics.svg'); ?>" alt="AI Basics cursus icoon">
                        </div>
                        <div class="course-progress">
                            <div class="progress-bar">
                                <div class="progress-value" style="width: 0%"></div>
                            </div>
                            <span class="progress-text">0% voltooid</span>
                        </div>
                    </div>
                    
                    <p>Een toegankelijke introductie tot AI en hoe je het kunt inzetten in je dagelijks werk. Geen technische kennis vereist.</p>
                    
                    <div class="course-modules">
                        <h4>Cursusmodules</h4>
                        <ul class="modules-list">
                            <li class="module-item">
                                <span class="module-title">1. Introductie tot AI</span>
                                <span class="module-status not-started">Nog niet gestart</span>
                            </li>
                            <li class="module-item">
                                <span class="module-title">2. AI-toepassingen in de praktijk</span>
                                <span class="module-status locked">Vergrendeld</span>
                            </li>
                            <li class="module-item">
                                <span class="module-title">3. Werken met AI-tools</span>
                                <span class="module-status locked">Vergrendeld</span>
                            </li>
                            <li class="module-item">
                                <span class="module-title">4. Praktijkopdracht</span>
                                <span class="module-status locked">Vergrendeld</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div slot="actions" class="course-actions" data-course-id="ai-basics-1">
                        <slimmer-button type="primary" href="e-learning/courses/ai-basics/index">Cursus starten</slimmer-button>
                        <slimmer-button type="outline" id="reset-progress-ai-basics">Voortgang resetten</slimmer-button>
                    </div>
                </slimmer-card>
            </template>
            
            <!-- Prompt Engineering Cursus Template (wordt getoond als gekocht) -->
            <template id="course-prompt-engineering">
                <slimmer-card title="Prompt Engineering">
                    <div class="course-header">
                        <div class="course-icon">
                            <img src="<?php echo asset_url('images/prompt-engineering.svg'); ?>" alt="Prompt Engineering cursus icoon">
                        </div>
                        <div class="course-progress">
                            <div class="progress-bar">
                                <div class="progress-value" style="width: 0%"></div>
                            </div>
                            <span class="progress-text">0% voltooid</span>
                        </div>
                    </div>
                    
                    <p>Leer hoe je ChatGPT en andere AI-tools precies kunt instrueren om exacte resultaten te krijgen.</p>
                    
                    <div class="course-modules">
                        <h4>Cursusmodules</h4>
                        <ul class="modules-list">
                            <li class="module-item">
                                <span class="module-title">1. Basis van Prompt Engineering</span>
                                <span class="module-status not-started">Nog niet gestart</span>
                            </li>
                            <li class="module-item">
                                <span class="module-title">2. Geavanceerde prompting technieken</span>
                                <span class="module-status locked">Vergrendeld</span>
                            </li>
                            <li class="module-item">
                                <span class="module-title">3. Prompts voor specifieke taken</span>
                                <span class="module-status locked">Vergrendeld</span>
                            </li>
                            <li class="module-item">
                                <span class="module-title">4. Prompt bibliotheek opbouwen</span>
                                <span class="module-status locked">Vergrendeld</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div slot="actions" class="course-actions" data-course-id="nl-prompt-eng-2023">
                        <slimmer-button type="primary" href="e-learning/courses/prompt-engineering/index">Cursus starten</slimmer-button>
                        <slimmer-button type="outline" id="reset-progress-prompt">Voortgang resetten</slimmer-button>
                    </div>
                </slimmer-card>
            </template>
            
            <!-- Workflow Automatisering Cursus Template (wordt getoond als gekocht) -->
            <template id="course-workflow-automation">
                <slimmer-card title="Workflow Automatisering">
                    <div class="course-header">
                        <div class="course-icon">
                            <img src="<?php echo asset_url('images/workflow-automation.svg'); ?>" alt="Workflow Automatisering cursus icoon">
                        </div>
                        <div class="course-progress">
                            <div class="progress-bar">
                                <div class="progress-value" style="width: 0%"></div>
                            </div>
                            <span class="progress-text">0% voltooid</span>
                        </div>
                    </div>
                    
                    <p>Leer hoe je complete werkprocessen kunt automatiseren met AI-tools en no-code platforms.</p>
                    
                    <div class="course-modules">
                        <h4>Cursusmodules</h4>
                        <ul class="modules-list">
                            <li class="module-item">
                                <span class="module-title">1. Automatisering basis principes</span>
                                <span class="module-status not-started">Nog niet gestart</span>
                            </li>
                            <li class="module-item">
                                <span class="module-title">2. No-code automatisering tools</span>
                                <span class="module-status locked">Vergrendeld</span>
                            </li>
                            <li class="module-item">
                                <span class="module-title">3. AI integreren in workflows</span>
                                <span class="module-status locked">Vergrendeld</span>
                            </li>
                            <li class="module-item">
                                <span class="module-title">4. Eindproject: Workflow automatiseren</span>
                                <span class="module-status locked">Vergrendeld</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div slot="actions" class="course-actions" data-course-id="workflow-auto-1">
                        <slimmer-button type="primary" href="e-learning/courses/workflow-automation/index">Cursus starten</slimmer-button>
                        <slimmer-button type="outline" id="reset-progress-workflow">Voortgang resetten</slimmer-button>
                    </div>
                </slimmer-card>
            </template>
        </div>
    </main>

    <slimmer-footer></slimmer-footer>

    <!-- Scripts -->
    <script src="<?php echo asset_url('js/main.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initMyCoursesPage();
        });
        
        function initMyCoursesPage() {
            // In een echte applicatie zou dit data van een backend API halen
            // Nu voor de demo simuleren we het ophalen van gekochte cursussen vanuit localStorage
            
            const purchasedCourses = getPurchasedCourses();
            renderPurchasedCourses(purchasedCourses);
            
            // Voeg event listeners toe aan de cursus interfaces
            setupCourseEventListeners();
        }
        
        function getPurchasedCourses() {
            // In een echte applicatie zou dit van de backend API komen
            // Voor de demo halen we het uit localStorage of gebruiken we vooraf ingestelde demo-data
            
            try {
                const cartData = localStorage.getItem('cart');
                if (cartData) {
                    const cart = JSON.parse(cartData);
                    // Filter alleen cursussen uit de winkelwagen
                    return cart.items.filter(item => item.type === 'Cursus').map(item => ({
                        id: item.id,
                        name: item.name,
                        progress: 0 // Begin met 0% voortgang
                    }));
                }
            } catch (error) {
                console.error('Fout bij ophalen cursussen:', error);
            }
            
            // Demo data voor preview doeleinden
            return [
                { id: 'ai-basics-1', name: 'AI Basics', progress: 0 }
            ];
        }
        
        function renderPurchasedCourses(courses) {
            const container = document.getElementById('my-courses-container');
            const noCoursesMessage = document.getElementById('no-courses-message');
            
            if (courses && courses.length > 0) {
                // Verberg de "geen cursussen" melding
                if (noCoursesMessage) {
                    noCoursesMessage.style.display = 'none';
                }
                
                // Toon gekochte cursussen
                courses.forEach(course => {
                    const templateId = `course-${course.id.split('-')[0]}`;
                    const template = document.getElementById(templateId) || document.getElementById('course-ai-basics');
                    
                    if (template) {
                        const clone = document.importNode(template.content, true);
                        
                        // Update voortgang
                        if (course.progress > 0) {
                            const progressBar = clone.querySelector('.progress-value');
                            const progressText = clone.querySelector('.progress-text');
                            
                            if (progressBar) {
                                progressBar.style.width = `${course.progress}%`;
                            }
                            
                            if (progressText) {
                                progressText.textContent = `${course.progress}% voltooid`;
                            }
                        }
                        
                        container.appendChild(clone);
                    }
                });
            } else {
                // Toon de "geen cursussen" melding
                if (noCoursesMessage) {
                    noCoursesMessage.style.display = 'flex';
                }
            }
        }
        
        function setupCourseEventListeners() {
            // Voeg event listeners toe voor de reset knoppen
            document.querySelectorAll('[id^="reset-progress-"]').forEach(button => {
                button.addEventListener('click', function() {
                    const courseActions = this.closest('.course-actions');
                    if (courseActions) {
                        const courseId = courseActions.dataset.courseId;
                        resetCourseProgress(courseId);
                    }
                });
            });
        }
        
        function resetCourseProgress(courseId) {
            // In een echte applicatie zou dit naar de backend gaan
            // Hier simuleren we het resetten van de voortgang in de UI
            
            const courseActions = document.querySelector(`.course-actions[data-course-id="${courseId}"]`);
            if (courseActions) {
                const card = courseActions.closest('slimmer-card');
                const progressBar = card.querySelector('.progress-value');
                const progressText = card.querySelector('.progress-text');
                
                if (progressBar) {
                    progressBar.style.width = '0%';
                }
                
                if (progressText) {
                    progressText.textContent = '0% voltooid';
                }
                
                // Reset module statuses
                card.querySelectorAll('.module-status').forEach((status, index) => {
                    if (index === 0) {
                        status.textContent = 'Nog niet gestart';
                        status.className = 'module-status not-started';
                    } else {
                        status.textContent = 'Vergrendeld';
                        status.className = 'module-status locked';
                    }
                });
                
                alert('Je voortgang is gereset. Je kunt de cursus opnieuw beginnen.');
            }
        }
    </script>
</body>
</html> 
