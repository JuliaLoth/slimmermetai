/**
 * User Stats Manager
 * Dit script beheert de gebruikersstatistieken op de profielpagina
 * Het haalt data op uit course-data-manager.js en toont werkelijke voortgang
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiseer gebruikersstatistieken
    initUserStats();
    
    // Registreer event listener voor pagina verversing
    window.addEventListener('focus', checkAndUpdateDailyLogin);
});

/**
 * Initialiseer gebruikersstatistieken op de profielpagina
 */
async function initUserStats() {
    // Laad het CourseDataManager script als het nog niet geladen is
    await loadCourseDataManagerScript();
    
    // Wacht tot CourseDataManager is geïnitialiseerd
    if (typeof CourseDataManager !== 'undefined') {
        await CourseDataManager.initCourseDataManager();
        
        // Update de metrics op de profielpagina
        updateProfileMetrics();
        
        // Controleer en update de dagelijkse login streak
        checkAndUpdateDailyLogin();
    } else {
        console.error('CourseDataManager niet gevonden');
    }
}

/**
 * Laad het CourseDataManager script als het nog niet geladen is
 */
function loadCourseDataManagerScript() {
    return new Promise((resolve, reject) => {
        // Controleer of het script al geladen is
        if (typeof CourseDataManager !== 'undefined') {
            resolve();
            return;
        }
        
        // Maak een nieuw script element aan
        const script = document.createElement('script');
        script.src = '/e-learning/js/course-data-manager.js';
        script.async = true;
        
        // Wanneer het script geladen is
        script.onload = () => {
            console.log('CourseDataManager script geladen');
            resolve();
        };
        
        // Als het script niet kan worden geladen
        script.onerror = () => {
            console.error('Kon CourseDataManager script niet laden');
            reject(new Error('Kon CourseDataManager script niet laden'));
        };
        
        // Voeg script toe aan de pagina
        document.head.appendChild(script);
    });
}

/**
 * Update de metrics op de profielpagina met data uit CourseDataManager
 */
function updateProfileMetrics() {
    // Haal gebruikersinformatie op uit localStorage
    const userJson = localStorage.getItem('currentUser');
    if (!userJson) {
        console.log('Geen gebruiker ingelogd, gebruik standaard waarden');
        return;
    }
    
    const user = JSON.parse(userJson);
    
    // 1. Aantal actieve cursussen - direct ophalen uit gebruikersdata
    const activeCourses = user.purchasedCourses ? user.purchasedCourses.length : 0;
    updateMetricCard('Actieve cursussen', activeCourses, activeCourses * 10);
    
    // 2. Gemiddelde voortgang berekenen - altijd real-time gegevens ophalen
    const coursesProgress = calculateAverageProgress(user.purchasedCourses);
    updateMetricCard('Gemiddelde voortgang', coursesProgress + '%', coursesProgress);
    
    // 3. Behaalde badges (op basis van voltooide lessen) - altijd real-time berekenen
    const badgesCount = calculateEarnedBadges(user.purchasedCourses);
    updateMetricCard('Behaalde badges', badgesCount, (badgesCount / 100) * 100);
    
    // 4. Dagen streak (aantal dagen actief na elkaar) - uit localstorage met validatie
    const streakDays = calculateLoginStreak();
    updateMetricCard('Dagen streak', streakDays, (streakDays / 10) * 100);
}

/**
 * Bereken de gemiddelde voortgang van alle cursussen
 */
function calculateAverageProgress(courses) {
    if (!courses || !Array.isArray(courses) || courses.length === 0) {
        return 0;
    }
    
    let totalProgress = 0;
    let validCourses = 0;
    
    courses.forEach(courseId => {
        const progress = CourseDataManager.getUserProgressForCourse(courseId);
        if (progress && typeof progress.overallProgress === 'number') {
            totalProgress += progress.overallProgress;
            validCourses++;
        }
    });
    
    return validCourses > 0 ? Math.round(totalProgress / validCourses) : 0;
}

/**
 * Bereken het aantal behaalde badges op basis van voltooide lessen
 */
function calculateEarnedBadges(courses) {
    if (!courses || !Array.isArray(courses) || courses.length === 0) {
        return 0;
    }
    
    let totalCompleted = 0;
    
    courses.forEach(courseId => {
        const progress = CourseDataManager.getUserProgressForCourse(courseId);
        if (progress && Array.isArray(progress.completed)) {
            totalCompleted += progress.completed.length;
        }
    });
    
    // Elke 3 voltooide lessen = 1 badge
    return Math.floor(totalCompleted / 3);
}

/**
 * Bereken het aantal dagen achter elkaar dat de gebruiker heeft ingelogd
 * Gebruikt real-time datum gegevens
 */
function calculateLoginStreak() {
    // Haal de huidige loginstreak op
    const streakData = JSON.parse(localStorage.getItem('loginStreakData') || '{}');
    
    if (!streakData.streak) {
        // Als er nog geen streak is, initialiseer deze
        const newStreakData = {
            streak: 1,
            lastLoginDate: new Date().toISOString().split('T')[0] // Huidige datum in YYYY-MM-DD formaat
        };
        localStorage.setItem('loginStreakData', JSON.stringify(newStreakData));
        return 1;
    }
    
    return streakData.streak;
}

/**
 * Controleert en update de dagelijkse login streak
 * Wordt opgeroepen bij page focus en bij initialisatie
 */
function checkAndUpdateDailyLogin() {
    // Haal de huidige gebruiker op
    const userJson = localStorage.getItem('currentUser');
    if (!userJson) return;
    
    // Haal de streak data op
    const streakData = JSON.parse(localStorage.getItem('loginStreakData') || '{}');
    const today = new Date().toISOString().split('T')[0]; // Huidige datum in YYYY-MM-DD formaat
    
    if (!streakData.lastLoginDate) {
        // Eerste login ooit
        const newStreakData = {
            streak: 1,
            lastLoginDate: today
        };
        localStorage.setItem('loginStreakData', JSON.stringify(newStreakData));
        updateMetricCard('Dagen streak', 1, 10);
        return;
    }
    
    // Bereken het verschil in dagen tussen de laatste login en vandaag
    const lastLoginDate = new Date(streakData.lastLoginDate);
    const currentDate = new Date(today);
    const diffTime = Math.abs(currentDate - lastLoginDate);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    let newStreak = streakData.streak;
    
    if (diffDays === 1) {
        // Opeenvolgende dag, verhoog streak
        newStreak += 1;
    } else if (diffDays > 1) {
        // Meer dan één dag overgeslagen, reset streak
        newStreak = 1;
    }
    // Als diffDays === 0, dan is het dezelfde dag, de streak blijft hetzelfde
    
    // Update de streak data in localStorage
    const newStreakData = {
        streak: newStreak,
        lastLoginDate: today
    };
    localStorage.setItem('loginStreakData', JSON.stringify(newStreakData));
    
    // Update de UI
    updateMetricCard('Dagen streak', newStreak, (newStreak / 10) * 100);
}

/**
 * Update een specifieke metric card met nieuwe waarde
 */
function updateMetricCard(label, value, progressPercentage) {
    // Zoek alle statistiek elements
    const statElements = document.querySelectorAll('.profile-stat');
    
    // Vind de juiste element met de gegeven label
    statElements.forEach(stat => {
        const labelElement = stat.querySelector('.profile-stat-label');
        if (labelElement && labelElement.textContent === label) {
            // Update de waarde
            const numberElement = stat.querySelector('.profile-stat-number');
            if (numberElement) {
                numberElement.textContent = value;
            }
            
            // Update de voortgangsbalk
            const progressFill = stat.querySelector('.progress-fill');
            if (progressFill) {
                // Zorg dat de percentage tussen 0 en 100 ligt
                const safePercentage = Math.min(Math.max(progressPercentage, 0), 100);
                progressFill.style.width = `${safePercentage}%`;
            }
        }
    });
} 