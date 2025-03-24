// Cursus JavaScript voor e-learning platform

document.addEventListener('DOMContentLoaded', function() {
    // Controleer login voordat content wordt geladen
    checkCourseAccess();
    
    // Initialiseer functionaliteiten als gebruiker toegang heeft
    initCourseInteraction();
    initQuizzes();
    initAssignments();
    addMobileSidebarToggle();
});

/**
 * Controleert of gebruiker toegang heeft tot de cursus
 */
function checkCourseAccess() {
    const isLoggedIn = localStorage.getItem('loggedIn') === 'true';
    const courseContent = document.querySelector('.course-content');
    const sidebar = document.querySelector('.course-sidebar');
    
    if (!isLoggedIn) {
        // Gebruiker is niet ingelogd, toon login prompt
        document.body.classList.add('access-denied');
        
        // Verberg cursusinhoud
        if (courseContent) courseContent.style.display = 'none';
        if (sidebar) sidebar.style.display = 'none';
        
        // Toon login prompt
        const loginPrompt = document.createElement('div');
        loginPrompt.className = 'login-required-prompt';
        loginPrompt.innerHTML = `
            <div class="prompt-container">
                <h2>Login vereist</h2>
                <p>Je moet ingelogd zijn om deze cursus te bekijken.</p>
                <a href="/login.html?returnUrl=${encodeURIComponent(window.location.pathname)}" class="btn btn-primary">Inloggen</a>
            </div>
        `;
        
        document.querySelector('.course-container').appendChild(loginPrompt);
        return false;
    }
    
    // Controleer of gebruiker de cursus heeft gekocht
    const courseId = getCourseId();
    const purchasedCourses = getUserPurchasedCourses();
    const hasPurchased = purchasedCourses.some(course => course.id === courseId);
    
    if (!hasPurchased) {
        // Gebruiker heeft deze cursus niet gekocht, toon koopscherm
        document.body.classList.add('purchase-required');
        
        // Verberg cursusinhoud
        if (courseContent) courseContent.style.display = 'none';
        if (sidebar) sidebar.style.display = 'none';
        
        // Toon koopscherm
        const purchasePrompt = document.createElement('div');
        purchasePrompt.className = 'purchase-required-prompt';
        purchasePrompt.innerHTML = `
            <div class="prompt-container">
                <h2>Cursus aanschaffen</h2>
                <p>Je hebt nog geen toegang tot deze cursus. Schaf de cursus aan om te beginnen met leren.</p>
                <a href="/e-learning/courses.html" class="btn btn-primary">Bekijk beschikbare cursussen</a>
            </div>
        `;
        
        document.querySelector('.course-container').appendChild(purchasePrompt);
        return false;
    }
    
    return true;
}

/**
 * Haalt gekochte cursussen op
 * In een echte app zou dit van de server komen
 */
function getUserPurchasedCourses() {
    return [
        {
            id: 'ai-basics',
            title: 'AI Basics'
        },
        {
            id: 'prompt-engineering',
            title: 'Prompt Engineering'
        }
    ];
}

/**
 * Initialiseert cursus interactie functionaliteit
 */
function initCourseInteraction() {
    // Module toggle functionaliteit
    const moduleHeaders = document.querySelectorAll('.module-header');
    
    moduleHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const moduleContent = this.nextElementSibling;
            const moduleArrow = this.querySelector('.module-arrow');
            
            // Toggle expanded class
            this.parentElement.classList.toggle('expanded');
            
            // Animeer hoogte voor smooth transitie
            if (this.parentElement.classList.contains('expanded')) {
                moduleContent.style.maxHeight = moduleContent.scrollHeight + 'px';
                if (moduleArrow) moduleArrow.style.transform = 'rotate(180deg)';
            } else {
                moduleContent.style.maxHeight = '0';
                if (moduleArrow) moduleArrow.style.transform = 'rotate(0)';
            }
        });
    });
    
    // Voeg click listeners toe aan lessen
    const lessonLinks = document.querySelectorAll('.lesson-link');
    lessonLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const lesId = this.getAttribute('data-lesson-id');
            navigateToLesson(lesId);
        });
    });
    
    // Activeer eerste les of herstel vorige les uit URL
    const urlHash = window.location.hash.substring(1);
    if (urlHash && urlHash.startsWith('lesson-')) {
        navigateToLesson(urlHash.replace('lesson-', ''));
    } else if (lessonLinks.length > 0) {
        const firstLesId = lessonLinks[0].getAttribute('data-lesson-id');
        navigateToLesson(firstLesId);
    }
    
    // Stel vorige/volgende knoppen in
    setupLessonNavigation();
    
    // Bijwerken cursus voortgang
    updateCourseProgress();
}

/**
 * Navigeert naar een specifieke les
 */
function navigateToLesson(lesId) {
    // Verberg alle lessen
    const allLessons = document.querySelectorAll('.lesson-content');
    allLessons.forEach(lesson => {
        lesson.classList.remove('active');
    });
    
    // Deactiveer alle les links
    const allLessonLinks = document.querySelectorAll('.lesson-link');
    allLessonLinks.forEach(link => {
        link.classList.remove('active');
    });
    
    // Activeer huidige les
    const currentLesson = document.getElementById('lesson-' + lesId);
    const currentLink = document.querySelector(`.lesson-link[data-lesson-id="${lesId}"]`);
    
    if (currentLesson) {
        currentLesson.classList.add('active');
        
        // Scroll naar de top van de les
        currentLesson.scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        // Update URL hash
        window.location.hash = 'lesson-' + lesId;
        
        // Markeer les als bekeken
        markLessonAsViewed(lesId);
    }
    
    if (currentLink) {
        currentLink.classList.add('active');
        
        // Zorg dat het huidige item zichtbaar is in de sidebar
        const moduleItem = currentLink.closest('.module-item');
        if (moduleItem && !moduleItem.classList.contains('expanded')) {
            const moduleHeader = moduleItem.querySelector('.module-header');
            if (moduleHeader) moduleHeader.click();
        }
    }
    
    // Bijwerken cursus voortgang
    updateCourseProgress();
}

/**
 * Markeert een les als bekeken
 */
function markLessonAsViewed(lesId) {
    const courseId = getCourseId();
    const storageKey = `course_${courseId}_viewed_lessons`;
    
    // Haal huidige bekeken lessen op
    let viewedLessons = JSON.parse(localStorage.getItem(storageKey) || '[]');
    
    // Voeg deze les toe als hij er nog niet in zit
    if (!viewedLessons.includes(lesId)) {
        viewedLessons.push(lesId);
        localStorage.setItem(storageKey, JSON.stringify(viewedLessons));
        
        // Update UI om aan te geven dat de les bekeken is
        const lessonLink = document.querySelector(`.lesson-link[data-lesson-id="${lesId}"]`);
        if (lessonLink) {
            lessonLink.classList.add('viewed');
        }
    }
}

/**
 * Stelt navigatieknoppen in voor vorige/volgende les
 */
function setupLessonNavigation() {
    const prevButton = document.querySelector('.nav-previous');
    const nextButton = document.querySelector('.nav-next');
    
    if (prevButton) {
        prevButton.addEventListener('click', function() {
            const activeLink = document.querySelector('.lesson-link.active');
            if (!activeLink) return;
            
            const prevLink = activeLink.previousElementSibling;
            if (prevLink && prevLink.classList.contains('lesson-link')) {
                const lesId = prevLink.getAttribute('data-lesson-id');
                navigateToLesson(lesId);
            }
        });
    }
    
    if (nextButton) {
        nextButton.addEventListener('click', function() {
            const activeLink = document.querySelector('.lesson-link.active');
            if (!activeLink) return;
            
            const nextLink = activeLink.nextElementSibling;
            if (nextLink && nextLink.classList.contains('lesson-link')) {
                const lesId = nextLink.getAttribute('data-lesson-id');
                navigateToLesson(lesId);
            }
        });
    }
}

/**
 * Haalt de cursus ID op uit URL of pagina
 */
function getCourseId() {
    // Haal cursus ID uit pathname
    // Bijvoorbeeld: /e-learning/course-ai-basics.html -> ai-basics
    const path = window.location.pathname;
    const matches = path.match(/course-([a-z0-9-]+)\.html/);
    
    if (matches && matches[1]) {
        return matches[1];
    }
    
    // Fallback: probeer het uit een data attribuut te halen
    const courseContainer = document.querySelector('.course-container');
    if (courseContainer) {
        return courseContainer.dataset.courseId;
    }
    
    return 'unknown';
}

/**
 * Werkt de voortgangsindicator bij
 */
function updateCourseProgress() {
    const courseId = getCourseId();
    const storageKey = `course_${courseId}_viewed_lessons`;
    
    // Haal bekeken lessen op
    const viewedLessons = JSON.parse(localStorage.getItem(storageKey) || '[]');
    
    // Update lessen in UI als bekeken
    viewedLessons.forEach(lesId => {
        const lessonLink = document.querySelector(`.lesson-link[data-lesson-id="${lesId}"]`);
        if (lessonLink) {
            lessonLink.classList.add('viewed');
        }
    });
    
    // Bereken voortgang percentage
    const totalLessons = document.querySelectorAll('.lesson-link').length;
    if (totalLessons === 0) return;
    
    const progress = Math.round((viewedLessons.length / totalLessons) * 100);
    
    // Update voortgangsbalk
    const progressBar = document.querySelector('.progress-bar-fill');
    const progressText = document.querySelector('.progress-text');
    
    if (progressBar) {
        progressBar.style.width = progress + '%';
    }
    
    if (progressText) {
        progressText.textContent = progress + '% voltooid';
    }
}

/**
 * Initialiseert quiz functionaliteit
 */
function initQuizzes() {
    const quizzes = document.querySelectorAll('.quiz-container');
    
    quizzes.forEach(quiz => {
        const quizId = quiz.getAttribute('id');
        const submitButton = quiz.querySelector('.quiz-submit');
        
        if (submitButton) {
            submitButton.addEventListener('click', function() {
                checkQuizAnswers(quiz);
            });
        }
        
        // Herstel opgeslagen antwoorden als ze er zijn
        restoreQuizState(quiz);
    });
}

/**
 * Controleert quiz antwoorden
 */
function checkQuizAnswers(quiz) {
    const quizId = quiz.getAttribute('id');
    const questions = quiz.querySelectorAll('.quiz-question');
    let correctAnswers = 0;
    
    questions.forEach(question => {
        const options = question.querySelectorAll('input[type="radio"]');
        const questionId = question.getAttribute('data-question-id');
        let isCorrect = false;
        let selectedAnswer = null;
        
        options.forEach(option => {
            if (option.checked) {
                selectedAnswer = option.value;
                isCorrect = option.getAttribute('data-correct') === 'true';
            }
        });
        
        // Markeer vraag als correct of incorrect
        if (selectedAnswer !== null) {
            question.classList.remove('correct', 'incorrect');
            question.classList.add(isCorrect ? 'correct' : 'incorrect');
            
            if (isCorrect) {
                correctAnswers++;
            }
            
            // Sla antwoord op
            saveQuizAnswer(quizId, questionId, selectedAnswer, isCorrect);
        }
    });
    
    // Toon resultaat
    const resultContainer = quiz.querySelector('.quiz-result');
    if (resultContainer) {
        const totalQuestions = questions.length;
        const percentage = Math.round((correctAnswers / totalQuestions) * 100);
        
        resultContainer.innerHTML = `
            <div class="result-message">
                <h4>Resultaat: ${correctAnswers}/${totalQuestions} correct (${percentage}%)</h4>
                <p>${percentage >= 70 ? 'Goed gedaan!' : 'Probeer het nog eens!'}</p>
            </div>
        `;
        
        resultContainer.style.display = 'block';
    }
}

/**
 * Slaat een quiz antwoord op
 */
function saveQuizAnswer(quizId, questionId, answer, isCorrect) {
    const courseId = getCourseId();
    const storageKey = `course_${courseId}_quiz_${quizId}`;
    
    // Haal huidige antwoorden op
    let savedAnswers = JSON.parse(localStorage.getItem(storageKey) || '{}');
    
    // Update met nieuw antwoord
    savedAnswers[questionId] = {
        answer: answer,
        correct: isCorrect
    };
    
    localStorage.setItem(storageKey, JSON.stringify(savedAnswers));
}

/**
 * Herstelt opgeslagen quiz antwoorden
 */
function restoreQuizState(quiz) {
    const quizId = quiz.getAttribute('id');
    const courseId = getCourseId();
    const storageKey = `course_${courseId}_quiz_${quizId}`;
    
    // Haal opgeslagen antwoorden op
    const savedAnswers = JSON.parse(localStorage.getItem(storageKey) || '{}');
    
    // Als er geen opgeslagen antwoorden zijn, doe niets
    if (Object.keys(savedAnswers).length === 0) {
        return;
    }
    
    // Herstel antwoorden
    const questions = quiz.querySelectorAll('.quiz-question');
    let correctCount = 0;
    
    questions.forEach(question => {
        const questionId = question.getAttribute('data-question-id');
        const savedAnswer = savedAnswers[questionId];
        
        if (savedAnswer) {
            // Selecteer het opgeslagen antwoord
            const options = question.querySelectorAll('input[type="radio"]');
            options.forEach(option => {
                if (option.value === savedAnswer.answer) {
                    option.checked = true;
                }
            });
            
            // Markeer als correct/incorrect
            question.classList.remove('correct', 'incorrect');
            question.classList.add(savedAnswer.correct ? 'correct' : 'incorrect');
            
            if (savedAnswer.correct) {
                correctCount++;
            }
        }
    });
    
    // Toon resultaat als alle vragen beantwoord zijn
    if (Object.keys(savedAnswers).length === questions.length) {
        const resultContainer = quiz.querySelector('.quiz-result');
        if (resultContainer) {
            const totalQuestions = questions.length;
            const percentage = Math.round((correctCount / totalQuestions) * 100);
            
            resultContainer.innerHTML = `
                <div class="result-message">
                    <h4>Resultaat: ${correctCount}/${totalQuestions} correct (${percentage}%)</h4>
                    <p>${percentage >= 70 ? 'Goed gedaan!' : 'Probeer het nog eens!'}</p>
                </div>
            `;
            
            resultContainer.style.display = 'block';
        }
    }
}

/**
 * Initialiseert opdracht functionaliteit
 */
function initAssignments() {
    const assignments = document.querySelectorAll('.assignment-container');
    
    assignments.forEach(assignment => {
        const assignmentId = assignment.getAttribute('id');
        const submitButton = assignment.querySelector('.assignment-submit');
        const answerField = assignment.querySelector('.assignment-answer');
        
        if (submitButton && answerField) {
            submitButton.addEventListener('click', function() {
                submitAssignment(assignment, answerField.value);
            });
            
            // Herstel opgeslagen antwoord als het er is
            restoreAssignmentState(assignment, answerField);
        }
    });
}

/**
 * Verwerkt een opdrachtinzending
 */
function submitAssignment(assignment, answer) {
    if (!answer.trim()) {
        return;
    }
    
    const assignmentId = assignment.getAttribute('id');
    const courseId = getCourseId();
    const storageKey = `course_${courseId}_assignment_${assignmentId}`;
    
    // Sla antwoord op
    localStorage.setItem(storageKey, answer);
    
    // Toon bevestiging
    const feedback = assignment.querySelector('.assignment-feedback');
    if (feedback) {
        feedback.innerHTML = `
            <div class="feedback-success">
                <p>Je antwoord is opgeslagen. Je instructeur zal dit beoordelen.</p>
            </div>
        `;
        feedback.style.display = 'block';
    }
}

/**
 * Herstelt opgeslagen opdracht antwoorden
 */
function restoreAssignmentState(assignment, answerField) {
    const assignmentId = assignment.getAttribute('id');
    const courseId = getCourseId();
    const storageKey = `course_${courseId}_assignment_${assignmentId}`;
    
    // Haal opgeslagen antwoord op
    const savedAnswer = localStorage.getItem(storageKey);
    
    if (savedAnswer) {
        // Vul het antwoord in
        answerField.value = savedAnswer;
        
        // Toon bevestiging
        const feedback = assignment.querySelector('.assignment-feedback');
        if (feedback) {
            feedback.innerHTML = `
                <div class="feedback-success">
                    <p>Je antwoord is opgeslagen. Je instructeur zal dit beoordelen.</p>
                </div>
            `;
            feedback.style.display = 'block';
        }
    }
}

/**
 * Voegt een mobiele sidebar toggle toe
 */
function addMobileSidebarToggle() {
    const sidebarToggle = document.createElement('button');
    sidebarToggle.className = 'sidebar-toggle';
    sidebarToggle.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    `;
    
    // Voeg toggle toe aan de DOM
    const courseContainer = document.querySelector('.course-container');
    if (courseContainer) {
        courseContainer.appendChild(sidebarToggle);
    }
    
    // Voeg event listener toe
    sidebarToggle.addEventListener('click', toggleSidebar);
}

/**
 * Schakelt de sidebar zichtbaarheid om op mobiele apparaten
 */
function toggleSidebar() {
    const sidebar = document.querySelector('.course-sidebar');
    const courseContent = document.querySelector('.course-content');
    
    if (sidebar) {
        sidebar.classList.toggle('open');
        
        if (courseContent) {
            courseContent.classList.toggle('sidebar-open');
        }
    }
} 