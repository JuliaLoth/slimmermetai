/**
 * Content Renderer
 * 
 * Bevat functies om verschillende typen cursusinhoud te renderen en weer te geven.
 * Werkt samen met de course-data-manager om gegevens op te halen en te verwerken.
 */

/**
 * Initialiseert de content renderer en verbindt met de data manager
 */
function initContentRenderer() {
    // Controleer of data manager beschikbaar is
    if (!window.CourseDataManager) {
        console.error('CourseDataManager niet gevonden. Zorg ervoor dat course-data-manager.js eerst wordt geladen.');
        return false;
    }
    
    console.log('Content renderer geïnitialiseerd');
    return true;
}

/**
 * Rendert een volledige cursuspagina met modules en lessen
 */
function renderCourse(courseId, targetElement) {
    if (!window.CourseDataManager) return;
    
    const course = CourseDataManager.getCourseById(courseId);
    if (!course) {
        console.error(`Cursus met ID "${courseId}" niet gevonden`);
        return;
    }
    
    const container = document.querySelector(targetElement);
    if (!container) {
        console.error(`Element "${targetElement}" niet gevonden`);
        return;
    }
    
    // Haal gebruikersvoortgang op
    const progress = CourseDataManager.getUserProgressForCourse(courseId);
    
    // Bouw cursusheader
    const courseHeader = document.createElement('div');
    courseHeader.className = 'course-header';
    courseHeader.innerHTML = `
        <h1>${course.title}</h1>
        <div class="course-meta">
            <span class="course-level">${course.level}</span>
            <span class="course-duration">${course.duration}</span>
        </div>
        <div class="course-author">
            <img src="${course.author.avatar}" alt="${course.author.name}" class="author-avatar">
            <span>${course.author.name}</span>
        </div>
        ${progress ? `
            <div class="course-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${progress.overallProgress}%"></div>
                </div>
                <span class="progress-text">${progress.overallProgress}% voltooid</span>
            </div>
        ` : ''}
    `;
    
    container.appendChild(courseHeader);
    
    // Maak module accordions
    const modulesContainer = document.createElement('div');
    modulesContainer.className = 'course-modules';
    
    course.modules.forEach(module => {
        const moduleElement = renderModule(course.id, module, progress);
        modulesContainer.appendChild(moduleElement);
    });
    
    container.appendChild(modulesContainer);
    
    // Initialiseer event listeners voor leslinks
    initLessonNavigation(courseId);
}

/**
 * Rendert een module als een accordion element
 */
function renderModule(courseId, module, progress) {
    const moduleElement = document.createElement('div');
    moduleElement.className = 'module-accordion';
    moduleElement.dataset.moduleId = module.id;
    
    const moduleHeader = document.createElement('div');
    moduleHeader.className = 'module-header';
    moduleHeader.innerHTML = `
        <h2>${module.title}</h2>
        <div class="module-meta">
            <span class="lesson-count">${module.lessons.length} lessen</span>
        </div>
        <button class="toggle-module">
            <span class="icon-expanded">▼</span>
            <span class="icon-collapsed">►</span>
        </button>
    `;
    
    moduleElement.appendChild(moduleHeader);
    
    // Lessen van de module
    const lessonsContainer = document.createElement('div');
    lessonsContainer.className = 'module-lessons';
    
    module.lessons.forEach(lesson => {
        // Controleer of les is voltooid
        const isCompleted = progress && progress.completed && progress.completed.includes(lesson.id);
        const isCurrent = progress && progress.current === lesson.id;
        
        const lessonElement = document.createElement('div');
        lessonElement.className = `lesson-item ${isCompleted ? 'completed' : ''} ${isCurrent ? 'current' : ''}`;
        lessonElement.innerHTML = `
            <a href="#" class="lesson-link" data-course-id="${courseId}" data-module-id="${module.id}" data-lesson-id="${lesson.id}">
                <span class="lesson-title">${lesson.title}</span>
                <span class="lesson-duration">${lesson.duration}</span>
                ${isCompleted ? '<span class="completion-indicator">✓</span>' : ''}
            </a>
        `;
        
        lessonsContainer.appendChild(lessonElement);
    });
    
    moduleElement.appendChild(lessonsContainer);
    
    // Voeg event listener toe voor toggle
    moduleHeader.querySelector('.toggle-module').addEventListener('click', function() {
        moduleElement.classList.toggle('expanded');
    });
    
    return moduleElement;
}

/**
 * Initialiseert event listeners voor lesnavigatie
 */
function initLessonNavigation(courseId) {
    document.querySelectorAll('.lesson-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const lessonId = this.dataset.lessonId;
            const moduleId = this.dataset.moduleId;
            
            // Laad lesinhoud
            loadLessonContent(courseId, moduleId, lessonId);
            
            // Markeer huidige les in navigatie
            document.querySelectorAll('.lesson-item').forEach(item => {
                item.classList.remove('current');
            });
            this.closest('.lesson-item').classList.add('current');
            
            // Update gebruikersvoortgang (huidige les)
            CourseDataManager.setCurrentLesson(courseId, lessonId);
        });
    });
}

/**
 * Laadt lesinhoud en geeft deze weer
 */
function loadLessonContent(courseId, moduleId, lessonId) {
    if (!window.CourseDataManager) return;
    
    const lessonContentContainer = document.querySelector('.lesson-content-container');
    if (!lessonContentContainer) {
        console.error('Lesinhoud container niet gevonden');
        return;
    }
    
    const lesson = CourseDataManager.getLessonById(courseId, moduleId, lessonId);
    if (!lesson) {
        console.error(`Les met ID "${lessonId}" niet gevonden`);
        return;
    }
    
    // Toon laad indicator
    lessonContentContainer.innerHTML = '<div class="loading-lesson">Lesinhoud laden...</div>';
    
    // Simuleer laadtijd (in een echte app zou dit een API call kunnen zijn)
    setTimeout(() => {
        lessonContentContainer.innerHTML = '';
        
        // Voeg les header toe
        const lessonHeader = document.createElement('div');
        lessonHeader.className = 'lesson-header';
        lessonHeader.innerHTML = `
            <h2>${lesson.title}</h2>
            <div class="lesson-meta">
                <span class="lesson-duration">${lesson.duration}</span>
            </div>
        `;
        lessonContentContainer.appendChild(lessonHeader);
        
        // Render lesinhoud
        const contentContainer = document.createElement('div');
        contentContainer.className = 'lesson-content';
        
        if (lesson.content && lesson.content.length > 0) {
            lesson.content.forEach(contentItem => {
                const contentElement = renderContentItem(contentItem);
                contentContainer.appendChild(contentElement);
            });
        } else {
            contentContainer.innerHTML = '<p>Geen inhoud beschikbaar voor deze les.</p>';
        }
        
        lessonContentContainer.appendChild(contentContainer);
        
        // Voeg notities en voortgangsknoppen toe
        renderLessonControls(lessonContentContainer, courseId, lessonId);
        
        // Initialiseer quizzes indien aanwezig
        initQuizzes(courseId, lessonId);
    }, 500);
}

/**
 * Rendert een enkel inhoudsitem (tekst, afbeelding, video, quiz)
 */
function renderContentItem(contentItem) {
    const container = document.createElement('div');
    container.className = `content-item content-type-${contentItem.type}`;
    
    switch(contentItem.type) {
        case 'text':
            container.innerHTML = `<div class="text-content">${contentItem.data}</div>`;
            break;
            
        case 'image':
            container.innerHTML = `
                <figure class="image-content">
                    <img src="${contentItem.data.src}" alt="${contentItem.data.alt || ''}">
                    ${contentItem.data.caption ? `<figcaption>${contentItem.data.caption}</figcaption>` : ''}
                </figure>
            `;
            break;
            
        case 'video':
            container.innerHTML = `
                <div class="video-content">
                    <iframe 
                        width="100%" 
                        height="400" 
                        src="${contentItem.data.src}" 
                        title="${contentItem.data.title || 'Video'}" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen
                    ></iframe>
                </div>
            `;
            break;
            
        case 'quiz':
            container.innerHTML = `<div class="quiz-container" data-quiz-id="${contentItem.data.questions[0].id.split('-')[0]}"></div>`;
            // Quiz wordt geïnitialiseerd in initQuizzes functie
            container.dataset.quiz = JSON.stringify(contentItem.data);
            break;
            
        default:
            container.innerHTML = `<div class="unknown-content">Onbekend inhoudstype: ${contentItem.type}</div>`;
    }
    
    return container;
}

/**
 * Rendert lesknoppen (volgende/vorige les, notities, etc.)
 */
function renderLessonControls(container, courseId, lessonId) {
    const controlsContainer = document.createElement('div');
    controlsContainer.className = 'lesson-controls';
    
    // Notities sectie
    const notesSection = document.createElement('div');
    notesSection.className = 'notes-section';
    notesSection.innerHTML = `
        <h3>Mijn notities</h3>
        <div class="notes-list"></div>
        <div class="add-note-form">
            <textarea placeholder="Voeg een notitie toe..."></textarea>
            <button class="btn btn-primary save-note-btn">Opslaan</button>
        </div>
    `;
    
    // Laad bestaande notities
    const notes = CourseDataManager.getNotes(courseId, lessonId);
    const notesList = notesSection.querySelector('.notes-list');
    
    if (notes && notes.length > 0) {
        notes.forEach(note => {
            const noteElement = document.createElement('div');
            noteElement.className = 'note-item';
            
            // Formateer datum
            const date = new Date(note.timestamp);
            const formattedDate = `${date.getDate()}-${date.getMonth() + 1}-${date.getFullYear()} ${date.getHours()}:${date.getMinutes().toString().padStart(2, '0')}`;
            
            noteElement.innerHTML = `
                <div class="note-text">${note.text}</div>
                <div class="note-meta">${formattedDate}</div>
            `;
            
            notesList.appendChild(noteElement);
        });
    } else {
        notesList.innerHTML = '<p class="no-notes">Nog geen notities voor deze les.</p>';
    }
    
    // Voeg event listener toe voor notities opslaan
    notesSection.querySelector('.save-note-btn').addEventListener('click', function() {
        const textarea = notesSection.querySelector('textarea');
        const noteText = textarea.value.trim();
        
        if (noteText) {
            // Sla notitie op
            CourseDataManager.saveNote(courseId, lessonId, noteText);
            
            // Voeg notitie toe aan lijst
            const noteElement = document.createElement('div');
            noteElement.className = 'note-item';
            
            const now = new Date();
            const formattedDate = `${now.getDate()}-${now.getMonth() + 1}-${now.getFullYear()} ${now.getHours()}:${now.getMinutes().toString().padStart(2, '0')}`;
            
            noteElement.innerHTML = `
                <div class="note-text">${noteText}</div>
                <div class="note-meta">${formattedDate}</div>
            `;
            
            // Verwijder "geen notities" bericht indien aanwezig
            const noNotes = notesList.querySelector('.no-notes');
            if (noNotes) {
                noNotes.remove();
            }
            
            notesList.appendChild(noteElement);
            textarea.value = '';
        }
    });
    
    // Voeg notities sectie toe
    controlsContainer.appendChild(notesSection);
    
    // Volgende/Vorige knoppen
    const navigationButtons = document.createElement('div');
    navigationButtons.className = 'lesson-navigation-buttons';
    
    // Zoek huidige lesindex en module voor navigatie
    const module = findModuleByLessonId(courseId, lessonId);
    if (module) {
        const lessonIndex = module.lessons.findIndex(l => l.id === lessonId);
        
        // Vorige les knop (indien niet eerste les)
        if (lessonIndex > 0) {
            const prevLesson = module.lessons[lessonIndex - 1];
            const prevButton = document.createElement('button');
            prevButton.className = 'btn btn-secondary prev-lesson-btn';
            prevButton.innerHTML = `← Vorige: ${prevLesson.title}`;
            prevButton.addEventListener('click', function() {
                document.querySelector(`.lesson-link[data-lesson-id="${prevLesson.id}"]`).click();
            });
            navigationButtons.appendChild(prevButton);
        }
        
        // Volgende les knop (indien niet laatste les)
        if (lessonIndex < module.lessons.length - 1) {
            const nextLesson = module.lessons[lessonIndex + 1];
            const nextButton = document.createElement('button');
            nextButton.className = 'btn btn-primary next-lesson-btn';
            nextButton.innerHTML = `Volgende: ${nextLesson.title} →`;
            nextButton.addEventListener('click', function() {
                document.querySelector(`.lesson-link[data-lesson-id="${nextLesson.id}"]`).click();
            });
            navigationButtons.appendChild(nextButton);
        } else {
            // Als dit de laatste les is, toon een "Les voltooien" knop
            const completeButton = document.createElement('button');
            completeButton.className = 'btn btn-success complete-lesson-btn';
            completeButton.innerHTML = 'Les voltooien';
            completeButton.addEventListener('click', function() {
                CourseDataManager.markLessonAsCompleted(courseId, lessonId);
                
                // Markeer les als voltooid in de navigatie
                const lessonItem = document.querySelector(`.lesson-link[data-lesson-id="${lessonId}"]`).closest('.lesson-item');
                lessonItem.classList.add('completed');
                
                // Als dit de laatste les in de module is, check of dit ook de laatste module is
                const moduleIndex = getCourseById(courseId).modules.findIndex(m => m.id === module.id);
                const isLastModule = moduleIndex === getCourseById(courseId).modules.length - 1;
                
                if (isLastModule) {
                    // Toon certificaatbericht als dit de allerlaatste les is
                    const contentContainer = document.querySelector('.lesson-content-container');
                    
                    const completionMessage = document.createElement('div');
                    completionMessage.className = 'course-completion-message';
                    completionMessage.innerHTML = `
                        <h2>Gefeliciteerd!</h2>
                        <p>Je hebt de cursus "${getCourseById(courseId).title}" voltooid!</p>
                        <button class="btn btn-primary get-certificate-btn">Download certificaat</button>
                    `;
                    
                    contentContainer.appendChild(completionMessage);
                    
                    // Genereer certificaat
                    completionMessage.querySelector('.get-certificate-btn').addEventListener('click', function() {
                        const certificateUrl = CourseDataManager.generateCertificate(courseId);
                        if (certificateUrl) {
                            window.open(certificateUrl, '_blank');
                        }
                    });
                }
                
                // Verberg de completeButton na klikken
                this.style.display = 'none';
            });
            navigationButtons.appendChild(completeButton);
        }
    }
    
    controlsContainer.appendChild(navigationButtons);
    container.appendChild(controlsContainer);
}

/**
 * Initialiseert quizzen in de lesinhoud
 */
function initQuizzes(courseId, lessonId) {
    document.querySelectorAll('.quiz-container').forEach(quizContainer => {
        if (!quizContainer.dataset.quiz) return;
        
        try {
            const quizData = JSON.parse(quizContainer.dataset.quiz);
            renderQuiz(quizContainer, quizData, courseId, lessonId);
        } catch (error) {
            console.error('Fout bij het verwerken van quizgegevens:', error);
        }
    });
}

/**
 * Rendert een quiz
 */
function renderQuiz(container, quizData, courseId, lessonId) {
    if (!quizData || !quizData.questions || quizData.questions.length === 0) {
        container.innerHTML = '<p>Geen quizvragen beschikbaar.</p>';
        return;
    }
    
    // Haal bestaande resultaten op, indien beschikbaar
    const progress = CourseDataManager.getUserProgressForCourse(courseId);
    const quizResults = progress && progress.quizResults && progress.quizResults[lessonId];
    
    // Als quiz al voltooid is, toon resultaten
    if (quizResults && quizResults.completed) {
        renderQuizResults(container, quizData, quizResults);
        return;
    }
    
    const quizForm = document.createElement('form');
    quizForm.className = 'quiz-form';
    
    quizData.questions.forEach((question, index) => {
        const questionElement = document.createElement('div');
        questionElement.className = 'quiz-question';
        questionElement.dataset.questionId = question.id;
        
        questionElement.innerHTML = `
            <h3>Vraag ${index + 1}: ${question.question}</h3>
            <div class="quiz-options">
                ${question.options.map((option, i) => `
                    <div class="quiz-option">
                        <input type="radio" name="quiz-${question.id}" id="option-${question.id}-${i}" value="${i}">
                        <label for="option-${question.id}-${i}">${option}</label>
                    </div>
                `).join('')}
            </div>
        `;
        
        quizForm.appendChild(questionElement);
    });
    
    // Knop om quiz in te dienen
    const submitButton = document.createElement('button');
    submitButton.type = 'submit';
    submitButton.className = 'btn btn-primary submit-quiz-btn';
    submitButton.textContent = 'Controleer antwoorden';
    quizForm.appendChild(submitButton);
    
    // Event listener voor indienen
    quizForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const results = {
            score: 0,
            completed: true,
            answers: {}
        };
        
        let correctAnswers = 0;
        
        // Controleer elk antwoord
        quizData.questions.forEach(question => {
            const selectedOption = document.querySelector(`input[name="quiz-${question.id}"]:checked`);
            const userAnswer = selectedOption ? parseInt(selectedOption.value) : -1;
            
            results.answers[question.id] = userAnswer;
            
            if (userAnswer === question.correctAnswer) {
                correctAnswers++;
            }
        });
        
        // Bereken score als percentage
        results.score = Math.round((correctAnswers / quizData.questions.length) * 100);
        
        // Sla resultaten op
        CourseDataManager.saveQuizResults(courseId, lessonId, results);
        
        // Toon resultaten
        renderQuizResults(container, quizData, results);
    });
    
    container.innerHTML = '';
    container.appendChild(quizForm);
}

/**
 * Rendert quizresultaten
 */
function renderQuizResults(container, quizData, results) {
    const resultsElement = document.createElement('div');
    resultsElement.className = 'quiz-results';
    
    resultsElement.innerHTML = `
        <h3>Quiz resultaten</h3>
        <div class="quiz-score">
            <div class="score-circle ${results.score >= 70 ? 'pass' : 'fail'}">
                <span class="score-value">${results.score}%</span>
            </div>
            <p class="score-message">
                ${results.score >= 70 ? 'Gefeliciteerd! Je hebt de quiz gehaald.' : 'Je hebt de quiz niet gehaald. Bekijk de stof nog eens en probeer opnieuw.'}
            </p>
        </div>
    `;
    
    // Toon feedback per vraag
    const questionsList = document.createElement('div');
    questionsList.className = 'questions-feedback';
    
    quizData.questions.forEach((question, index) => {
        const userAnswer = results.answers[question.id];
        const isCorrect = userAnswer === question.correctAnswer;
        
        const questionFeedback = document.createElement('div');
        questionFeedback.className = `question-feedback ${isCorrect ? 'correct' : 'incorrect'}`;
        questionFeedback.innerHTML = `
            <h4>Vraag ${index + 1}: ${question.question}</h4>
            <p>
                <strong>Jouw antwoord:</strong> 
                ${userAnswer >= 0 ? question.options[userAnswer] : 'Geen antwoord gegeven'}
            </p>
            ${!isCorrect ? `
                <p><strong>Juiste antwoord:</strong> ${question.options[question.correctAnswer]}</p>
            ` : ''}
            <p><strong>Uitleg:</strong> ${question.explanation || 'Geen uitleg beschikbaar.'}</p>
        `;
        
        questionsList.appendChild(questionFeedback);
    });
    
    resultsElement.appendChild(questionsList);
    
    // Knop om quiz opnieuw te doen
    const retryButton = document.createElement('button');
    retryButton.className = 'btn btn-secondary retry-quiz-btn';
    retryButton.textContent = 'Quiz opnieuw maken';
    retryButton.addEventListener('click', function() {
        // Reset quiz en render opnieuw
        renderQuiz(container, quizData, courseId, lessonId);
    });
    
    resultsElement.appendChild(retryButton);
    
    container.innerHTML = '';
    container.appendChild(resultsElement);
}

/**
 * Hulpfunctie om de module te vinden waarin een les zich bevindt
 */
function findModuleByLessonId(courseId, lessonId) {
    const course = CourseDataManager.getCourseById(courseId);
    if (!course) return null;
    
    return course.modules.find(module => 
        module.lessons.some(lesson => lesson.id === lessonId)
    );
}

// Exporteer alle functies voor gebruik in andere bestanden
window.ContentRenderer = {
    init: initContentRenderer,
    renderCourse,
    loadLessonContent
}; 