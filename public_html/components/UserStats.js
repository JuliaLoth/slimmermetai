/**
 * UserStats Component - Statistieken weergave voor gebruikers
 * Versie: 1.0.0 - 25 maart 2025
 * 
 * Gebruik:
 * <slimmer-user-stats></slimmer-user-stats>
 * 
 * Standaard opties (kunnen worden aangepast via attributen):
 * - show-active-courses="true"      - Toon aantal actieve cursussen
 * - show-average-progress="true"    - Toon gemiddelde voortgang
 * - show-badges="true"              - Toon aantal behaalde badges
 * - show-streak="true"              - Toon login streak
 */

class UserStats extends HTMLElement {
    constructor() {
        super();
        
        // Configuratie opties
        this.showActiveCourses = true;
        this.showAverageProgress = true;
        this.showBadges = true;
        this.showStreak = true;
        
        // Standaard waarden
        this.activeCourses = 0;
        this.averageProgress = 0;
        this.badges = 0;
        this.streak = 0;
        
        // Shadow DOM aanmaken
        this.attachShadow({ mode: 'open' });
    }
    
    static get observedAttributes() {
        return [
            'show-active-courses',
            'show-average-progress',
            'show-badges',
            'show-streak'
        ];
    }
    
    attributeChangedCallback(name, oldValue, newValue) {
        switch (name) {
            case 'show-active-courses':
                this.showActiveCourses = newValue !== 'false';
                break;
            case 'show-average-progress':
                this.showAverageProgress = newValue !== 'false';
                break;
            case 'show-badges':
                this.showBadges = newValue !== 'false';
                break;
            case 'show-streak':
                this.showStreak = newValue !== 'false';
                break;
        }
        
        if (this.isConnected) {
            this.render();
        }
    }
    
    connectedCallback() {
        // CSS stijlen toevoegen
        this.addStyles();
        
        // Initiële render
        this.render();
        
        // Data laden en streak listener toevoegen
        this.initStats();
        
        // Event listener voor focus op window (login streak update)
        window.addEventListener('focus', () => this.checkAndUpdateDailyLogin());
    }
    
    // Load required scripts and initialize stats
    async initStats() {
        await this.loadCourseDataManagerScript();
        
        if (typeof CourseDataManager !== 'undefined') {
            try {
                await CourseDataManager.initCourseDataManager();
                this.updateStats();
                this.checkAndUpdateDailyLogin();
            } catch (error) {
                console.error('Fout bij initialiseren CourseDataManager:', error);
            }
        } else {
            console.log('CourseDataManager niet gevonden, gebruik standaard waarden');
            this.render(); // Render met standaard waarden
        }
    }
    
    // Laad het CourseDataManager script als het nog niet geladen is
    loadCourseDataManagerScript() {
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
                resolve(); // Resolve toch om verder te gaan met standaardwaarden
            };
            
            // Voeg script toe aan de pagina
            document.head.appendChild(script);
        });
    }
    
    // Update alle statistieken
    updateStats() {
        // Haal gebruikersinformatie op uit localStorage
        const userJson = localStorage.getItem('currentUser');
        if (!userJson) {
            console.log('Geen gebruiker ingelogd, gebruik standaard waarden');
            return;
        }
        
        const user = JSON.parse(userJson);
        
        // 1. Aantal actieve cursussen - direct ophalen uit gebruikersdata
        this.activeCourses = user.purchasedCourses ? user.purchasedCourses.length : 0;
        
        // 2. Gemiddelde voortgang berekenen - altijd real-time gegevens ophalen
        this.averageProgress = this.calculateAverageProgress(user.purchasedCourses);
        
        // 3. Behaalde badges (op basis van voltooide lessen) - altijd real-time berekenen
        this.badges = this.calculateEarnedBadges(user.purchasedCourses);
        
        // 4. Dagen streak (aantal dagen actief na elkaar) - uit localstorage met validatie
        this.streak = this.calculateLoginStreak();
        
        // Update de UI
        this.render();
    }
    
    // Bereken de gemiddelde voortgang van alle cursussen
    calculateAverageProgress(courses) {
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
    
    // Bereken het aantal behaalde badges op basis van voltooide lessen
    calculateEarnedBadges(courses) {
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
    
    // Bereken het aantal dagen achter elkaar dat de gebruiker heeft ingelogd
    calculateLoginStreak() {
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
    
    // Controleert en update de dagelijkse login streak
    checkAndUpdateDailyLogin() {
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
            this.streak = 1;
            this.render();
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
        
        // Update de streak data in localStorage
        const newStreakData = {
            streak: newStreak,
            lastLoginDate: today
        };
        localStorage.setItem('loginStreakData', JSON.stringify(newStreakData));
        
        // Update de UI
        this.streak = newStreak;
        this.render();
    }
    
    // Stijlen toevoegen aan de shadow DOM
    addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            :host {
                display: block;
                font-family: 'Inter', sans-serif;
                width: 100%;
            }
            
            .stats-row {
                display: flex;
                flex-wrap: wrap;
                gap: 1rem;
                justify-content: center;
                margin: 2rem 0;
                width: 100%;
            }
            
            .stat-card {
                background-color: white;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                display: flex;
                flex-direction: column;
                flex: 1;
                min-width: 200px;
                padding: 1.5rem;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            
            .stat-card:hover {
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
                transform: translateY(-4px);
            }
            
            .stat-1 { border-top: 4px solid #4263EB; }
            .stat-2 { border-top: 4px solid #00BFA6; }
            .stat-3 { border-top: 4px solid #FF9100; }
            .stat-4 { border-top: 4px solid #E91E63; }
            
            .stat-number {
                color: #1E293B;
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 0.5rem;
            }
            
            .stat-label {
                color: #64748B;
                font-size: 1rem;
                font-weight: 500;
                margin-bottom: 1rem;
            }
            
            .progress-container {
                margin-top: auto;
            }
            
            .progress-bar {
                background-color: #F1F5F9;
                border-radius: 8px;
                height: 8px;
                overflow: hidden;
                width: 100%;
            }
            
            .progress-fill {
                height: 100%;
                transition: width 0.5s ease-in-out;
            }
            
            .stat-1 .progress-fill { background-color: #4263EB; }
            .stat-2 .progress-fill { background-color: #00BFA6; }
            .stat-3 .progress-fill { background-color: #FF9100; }
            .stat-4 .progress-fill { background-color: #E91E63; }
            
            @media (max-width: 768px) {
                .stats-row {
                    flex-direction: column;
                }
                
                .stat-card {
                    width: 100%;
                }
            }
        `;
        
        this.shadowRoot.appendChild(style);
    }
    
    // Component renderen
    render() {
        // HTML voor de statistieken
        this.shadowRoot.innerHTML = '';
        
        // Stijlen toevoegen
        this.addStyles();
        
        // Container voor de stats
        const statsRow = document.createElement('div');
        statsRow.className = 'stats-row';
        
        // Alleen de gevraagde statistieken tonen
        if (this.showActiveCourses) {
            statsRow.appendChild(this.createStatCard(
                'stat-1',
                this.activeCourses,
                'Actieve cursussen',
                this.activeCourses * 10
            ));
        }
        
        if (this.showAverageProgress) {
            statsRow.appendChild(this.createStatCard(
                'stat-2',
                this.averageProgress + '%',
                'Gemiddelde voortgang',
                this.averageProgress
            ));
        }
        
        if (this.showBadges) {
            statsRow.appendChild(this.createStatCard(
                'stat-3',
                this.badges,
                'Behaalde badges',
                (this.badges / 100) * 100
            ));
        }
        
        if (this.showStreak) {
            statsRow.appendChild(this.createStatCard(
                'stat-4',
                this.streak,
                'Dagen streak',
                (this.streak / 10) * 100
            ));
        }
        
        this.shadowRoot.appendChild(statsRow);
    }
    
    // Helper methode voor het aanmaken van een stat card
    createStatCard(className, number, label, progressPercentage) {
        const safePercentage = Math.min(Math.max(progressPercentage, 0), 100);
        
        const card = document.createElement('div');
        card.className = `stat-card ${className} profile-stat`;
        
        const numberEl = document.createElement('div');
        numberEl.className = 'stat-number profile-stat-number';
        numberEl.textContent = number;
        
        const labelEl = document.createElement('div');
        labelEl.className = 'stat-label profile-stat-label';
        labelEl.textContent = label;
        
        const progressContainer = document.createElement('div');
        progressContainer.className = 'progress-container';
        
        const progressBar = document.createElement('div');
        progressBar.className = 'progress-bar';
        
        const progressFill = document.createElement('div');
        progressFill.className = 'progress-fill';
        progressFill.style.width = `${safePercentage}%`;
        
        progressBar.appendChild(progressFill);
        progressContainer.appendChild(progressBar);
        
        card.appendChild(numberEl);
        card.appendChild(labelEl);
        card.appendChild(progressContainer);
        
        return card;
    }
}

// Component registreren
customElements.define('slimmer-user-stats', UserStats);

console.log('✅ UserStats component geladen'); 