/**
 * Course Data Manager
 * 
 * Beheert alle functionaliteit gerelateerd aan cursussen, modules en voortgang.
 * Dit bestand verwerkt de JSON-datastructuur en biedt functies voor het ophalen en bijwerken van gegevens.
 */

// Variabele om de volledige datastructuur op te slaan nadat het is geladen
let courseData = null;

/**
 * Initialiseert de course data manager
 * Laadt alle cursusgegevens en gebruikersvoortgang
 */
async function initCourseDataManager() {
    try {
        // Laad de cursusgegevens vanuit de JSON-bestand (in een echte applicatie via API)
        const response = await fetch('/e-learning/js/courses-data-structure.json');
        if (!response.ok) {
            throw new Error('Kon cursusgegevens niet laden');
        }
        
        courseData = await response.json();
        console.log('Cursusgegevens succesvol geladen');
        
        // Gebruikersgegevens opvragen (in een echte applicatie via API)
        loadUserData();
        
        return true;
    } catch (error) {
        console.error('Fout bij het initialiseren van de course data manager:', error);
        return false;
    }
}

/**
 * Laadt gebruikersgegevens en combineert deze met cursusgegevens
 * In een echte applicatie zou dit via een API gaan
 */
function loadUserData() {
    // Controleer login status
    const isLoggedIn = localStorage.getItem('loggedIn') === 'true';
    const userEmail = localStorage.getItem('userEmail');
    
    if (!isLoggedIn || !userEmail) {
        console.log('Gebruiker is niet ingelogd, geen gebruikersgegevens geladen');
        return;
    }
    
    // In een echte app zouden we hier de gebruikersgegevens ophalen via een API
    // Nu gebruiken we de voorbeeld gebruiker uit de JSON
    const user = courseData.users.find(u => u.email === userEmail) || courseData.users[0];
    
    // Sla gebruiker op in localStorage voor snelle toegang (in een echte app niet voor gevoelige gegevens)
    localStorage.setItem('currentUser', JSON.stringify({
        id: user.id,
        email: user.email,
        firstName: user.firstName,
        lastName: user.lastName,
        purchasedCourses: user.purchasedCourses
    }));
    
    console.log('Gebruikersgegevens geladen voor:', user.email);
}

/**
 * Haalt alle beschikbare cursussen op
 */
function getAllCourses() {
    if (!courseData) return [];
    return courseData.courses;
}

/**
 * Haalt een specifieke cursus op op basis van ID
 */
function getCourseById(courseId) {
    if (!courseData) return null;
    return courseData.courses.find(course => course.id === courseId);
}

/**
 * Haalt alle modules op voor een specifieke cursus
 */
function getModulesByCourseId(courseId) {
    const course = getCourseById(courseId);
    if (!course) return [];
    return course.modules;
}

/**
 * Haalt een specifieke module op
 */
function getModuleById(courseId, moduleId) {
    const modules = getModulesByCourseId(courseId);
    return modules.find(module => module.id === moduleId);
}

/**
 * Haalt alle lessen op voor een specifieke module
 */
function getLessonsByModuleId(courseId, moduleId) {
    const module = getModuleById(courseId, moduleId);
    if (!module) return [];
    return module.lessons;
}

/**
 * Haalt een specifieke les op
 */
function getLessonById(courseId, moduleId, lessonId) {
    const lessons = getLessonsByModuleId(courseId, moduleId);
    return lessons.find(lesson => lesson.id === lessonId);
}

/**
 * Haalt de gebruikersvoortgang op voor een specifieke cursus
 */
function getUserProgressForCourse(courseId) {
    if (!courseData) return null;
    
    const userJson = localStorage.getItem('currentUser');
    if (!userJson) return null;
    
    const user = JSON.parse(userJson);
    const userId = user.id;
    
    // In een echte app zou dit via een API gaan
    const userInData = courseData.users.find(u => u.id === userId);
    if (!userInData || !userInData.progress || !userInData.progress[courseId]) {
        return {
            completed: [],
            current: null,
            quizResults: {},
            overallProgress: 0,
            lastAccessed: new Date().toISOString()
        };
    }
    
    return userInData.progress[courseId];
}

/**
 * Werkt de gebruikersvoortgang bij
 * In een echte applicatie zou dit via een API gaan om gegevens op de server bij te werken
 */
function updateUserProgress(courseId, progressData) {
    if (!courseData) return false;
    
    const userJson = localStorage.getItem('currentUser');
    if (!userJson) return false;
    
    const user = JSON.parse(userJson);
    const userId = user.id;
    
    // Update lokaal in courseData (in een echte app: API call)
    const userIndex = courseData.users.findIndex(u => u.id === userId);
    if (userIndex === -1) return false;
    
    if (!courseData.users[userIndex].progress) {
        courseData.users[userIndex].progress = {};
    }
    
    // Update voortgang voor deze cursus
    courseData.users[userIndex].progress[courseId] = {
        ...getUserProgressForCourse(courseId),
        ...progressData,
        lastAccessed: new Date().toISOString()
    };
    
    console.log('Voortgang bijgewerkt voor cursus:', courseId);
    return true;
}

/**
 * Markeert een les als voltooid
 */
function markLessonAsCompleted(courseId, lessonId) {
    const progress = getUserProgressForCourse(courseId);
    if (!progress) return false;
    
    // Voeg lessonId toe aan voltooide lessen als het nog niet bestaat
    if (!progress.completed.includes(lessonId)) {
        const updatedCompleted = [...progress.completed, lessonId];
        
        // Bereken nieuwe voortgangspercentage
        const course = getCourseById(courseId);
        const totalLessons = course.modules.reduce((total, module) => 
            total + module.lessons.length, 0);
        const overallProgress = Math.round((updatedCompleted.length / totalLessons) * 100);
        
        // Update de voortgang
        return updateUserProgress(courseId, {
            completed: updatedCompleted,
            overallProgress
        });
    }
    
    return true;
}

/**
 * Slaat quizresultaten op
 */
function saveQuizResults(courseId, lessonId, results) {
    const progress = getUserProgressForCourse(courseId);
    if (!progress) return false;
    
    if (!progress.quizResults) {
        progress.quizResults = {};
    }
    
    progress.quizResults[lessonId] = {
        ...results,
        lastAttempt: new Date().toISOString()
    };
    
    return updateUserProgress(courseId, {
        quizResults: progress.quizResults
    });
}

/**
 * Bijwerkt de huidige les voor de gebruiker
 */
function setCurrentLesson(courseId, lessonId) {
    return updateUserProgress(courseId, {
        current: lessonId
    });
}

/**
 * Slaat een notitie op voor een specifieke les
 */
function saveNote(courseId, lessonId, noteText) {
    if (!courseData) return false;
    
    const userJson = localStorage.getItem('currentUser');
    if (!userJson) return false;
    
    const user = JSON.parse(userJson);
    const userId = user.id;
    
    // Update lokaal in courseData (in een echte app: API call)
    const userIndex = courseData.users.findIndex(u => u.id === userId);
    if (userIndex === -1) return false;
    
    // Initialiseer notes-structuur indien nodig
    if (!courseData.users[userIndex].notes) {
        courseData.users[userIndex].notes = {};
    }
    
    if (!courseData.users[userIndex].notes[courseId]) {
        courseData.users[userIndex].notes[courseId] = {};
    }
    
    if (!courseData.users[userIndex].notes[courseId][lessonId]) {
        courseData.users[userIndex].notes[courseId][lessonId] = [];
    }
    
    // Voeg nieuwe notitie toe
    courseData.users[userIndex].notes[courseId][lessonId].push({
        text: noteText,
        timestamp: new Date().toISOString()
    });
    
    console.log('Notitie opgeslagen voor les:', lessonId);
    return true;
}

/**
 * Haalt alle notities op voor een specifieke les
 */
function getNotes(courseId, lessonId) {
    if (!courseData) return [];
    
    const userJson = localStorage.getItem('currentUser');
    if (!userJson) return [];
    
    const user = JSON.parse(userJson);
    const userId = user.id;
    
    const userInData = courseData.users.find(u => u.id === userId);
    if (!userInData || !userInData.notes || !userInData.notes[courseId] || !userInData.notes[courseId][lessonId]) {
        return [];
    }
    
    return userInData.notes[courseId][lessonId];
}

/**
 * Controleert of de gebruiker toegang heeft tot een cursus
 */
function hasAccessToCourse(courseId) {
    const userJson = localStorage.getItem('currentUser');
    if (!userJson) return false;
    
    const user = JSON.parse(userJson);
    return user.purchasedCourses && user.purchasedCourses.includes(courseId);
}

/**
 * Genereert een certificaat voor een voltooide cursus
 */
function generateCertificate(courseId) {
    const progress = getUserProgressForCourse(courseId);
    if (!progress || progress.overallProgress < 100) {
        console.error('Kan geen certificaat genereren: cursus niet voltooid');
        return false;
    }
    
    // In een echte applicatie zou dit een API-aanroep zijn om een certificaat te genereren
    // en de URL terug te geven
    const certificateUrl = `/certificates/user-${getUserId()}-${courseId}.pdf`;
    
    // Update certificaten in gebruikersgegevens
    if (!courseData) return false;
    
    const userJson = localStorage.getItem('currentUser');
    if (!userJson) return false;
    
    const user = JSON.parse(userJson);
    const userId = user.id;
    
    const userIndex = courseData.users.findIndex(u => u.id === userId);
    if (userIndex === -1) return false;
    
    if (!courseData.users[userIndex].certificates) {
        courseData.users[userIndex].certificates = [];
    }
    
    // Voeg certificaat toe als het nog niet bestaat
    if (!courseData.users[userIndex].certificates.some(cert => cert.courseId === courseId)) {
        courseData.users[userIndex].certificates.push({
            courseId,
            issueDate: new Date().toISOString().split('T')[0],
            certificateUrl
        });
    }
    
    console.log('Certificaat gegenereerd voor cursus:', courseId);
    return certificateUrl;
}

/**
 * Haalt de gebruiker ID op
 */
function getUserId() {
    const userJson = localStorage.getItem('currentUser');
    if (!userJson) return null;
    
    const user = JSON.parse(userJson);
    return user.id;
}

// Exporteer alle functies voor gebruik in andere bestanden
window.CourseDataManager = {
    init: initCourseDataManager,
    getAllCourses,
    getCourseById,
    getModulesByCourseId,
    getModuleById,
    getLessonsByModuleId,
    getLessonById,
    getUserProgressForCourse,
    markLessonAsCompleted,
    saveQuizResults,
    setCurrentLesson,
    saveNote,
    getNotes,
    hasAccessToCourse,
    generateCertificate
}; 