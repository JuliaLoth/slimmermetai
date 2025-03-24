// E-Learning Platform JS

document.addEventListener('DOMContentLoaded', function() {
    // Check login status en laad cursussen
    checkUserLogin();
    loadUserCourses();
    initializeCartButtons();
});

/**
 * Controleert of gebruiker is ingelogd
 * Zo niet, redirect naar login voor beveiligde pagina's
 */
function checkUserLogin() {
    const isLoggedIn = localStorage.getItem('loggedIn') === 'true';
    const userEmail = localStorage.getItem('userEmail');
    
    // Update UI op basis van login status
    updateLoginUI(isLoggedIn, userEmail);
    
    // Voor beveiligde pagina's, redirect naar login als niet ingelogd
    const isSecurePage = document.body.classList.contains('secure-page');
    if (isSecurePage && !isLoggedIn) {
        // Construct returnUrl to come back after login
        const returnUrl = encodeURIComponent(window.location.pathname);
        window.location.href = '/login.html?returnUrl=' + returnUrl;
    }
}

/**
 * Update UI elementen gebaseerd op login status
 */
function updateLoginUI(isLoggedIn, userEmail) {
    const loginBtn = document.querySelector('.login-btn');
    const accountBtn = document.querySelector('.account-btn');
    const courseAccessElements = document.querySelectorAll('.requires-login');
    
    if (isLoggedIn && userEmail) {
        // Gebruiker is ingelogd
        if (loginBtn) loginBtn.style.display = 'none';
        if (accountBtn) {
            accountBtn.style.display = 'flex';
            // Eerste letter van email als avatar
            const avatarEl = accountBtn.querySelector('.user-avatar');
            if (avatarEl) {
                avatarEl.textContent = userEmail.charAt(0).toUpperCase();
            }
        }
        
        // Toon beschermde content
        courseAccessElements.forEach(el => {
            el.classList.remove('locked');
        });
    } else {
        // Gebruiker is niet ingelogd
        if (loginBtn) loginBtn.style.display = 'flex';
        if (accountBtn) accountBtn.style.display = 'none';
        
        // Verberg beschermde content
        courseAccessElements.forEach(el => {
            el.classList.add('locked');
        });
    }
}

/**
 * Laadt gekochte cursussen van gebruiker
 */
function loadUserCourses() {
    const isLoggedIn = localStorage.getItem('loggedIn') === 'true';
    const coursesGrid = document.querySelector('.my-courses-grid');
    
    if (!coursesGrid || !isLoggedIn) return;
    
    // Toon laad animatie
    coursesGrid.innerHTML = '<div class="loading-courses">Cursussen laden...</div>';
    
    // In een echte applicatie zou dit een API call zijn
    // Nu simuleren we een vertraging en gebruiken lokale data
    setTimeout(() => {
        const purchasedCourses = getUserPurchasedCourses();
        
        if (purchasedCourses.length === 0) {
            coursesGrid.innerHTML = `
                <div class="no-courses-message">
                    <h3>Je hebt nog geen cursussen</h3>
                    <p>Bekijk onze beschikbare cursussen en begin met leren!</p>
                    <a href="courses.html" class="btn btn-primary">Bekijk cursussen</a>
                </div>
            `;
            return;
        }
        
        // Reset grid voordat we cursussen toevoegen
        coursesGrid.innerHTML = '';
        
        // Voeg elke cursus toe aan het grid
        purchasedCourses.forEach(course => {
            addCourseToGrid(coursesGrid, course);
        });
    }, 800);
}

/**
 * Haal gekochte cursussen van gebruiker op
 * In een echte applicatie zou dit van een API komen
 */
function getUserPurchasedCourses() {
    // Simuleer gekochte cursussen (in een echte app zou dit van server komen)
    // Op dit moment lege array, kan bijgewerkt worden met voorbeelddata wanneer nodig
    return [
        {
            id: 'ai-basics',
            title: 'AI Basics',
            description: 'Introductie tot de basisprincipes van kunstmatige intelligentie.',
            image: 'img/courses/ai-basics.jpg',
            progress: 35,
            url: 'course-ai-basics.html'
        },
        {
            id: 'prompt-engineering',
            title: 'Prompt Engineering',
            description: 'Leer effectieve prompts schrijven voor AI tools en generatieve AI.',
            image: 'img/courses/prompt-engineering.jpg',
            progress: 10,
            url: 'course-prompt-engineering.html'
        }
    ];
}

/**
 * Voegt een cursuskaart toe aan het opgegeven grid
 */
function addCourseToGrid(grid, course) {
    const courseCard = document.createElement('div');
    courseCard.className = 'course-card';
    courseCard.innerHTML = `
        <div class="course-image">
            <img src="${course.image}" alt="${course.title}">
            ${course.progress ? `
                <div class="course-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${course.progress}%"></div>
                    </div>
                    <span class="progress-text">${course.progress}% voltooid</span>
                </div>
            ` : ''}
        </div>
        <div class="course-content">
            <h3>${course.title}</h3>
            <p>${course.description}</p>
            <a href="${course.url}" class="btn btn-primary">Doorgaan met leren</a>
        </div>
    `;
    
    grid.appendChild(courseCard);
}

/**
 * Initialiseert de "Toevoegen aan winkelwagen" knoppen
 */
function initializeCartButtons() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Haal product informatie op van data attributen
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            const productPrice = this.dataset.productPrice;
            
            // Voeg toe aan winkelwagen (functie gedefinieerd in cart.js)
            if (typeof addToCart === 'function') {
                addToCart({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    quantity: 1
                });
                
                // Toon notificatie
                showNotification(`${productName} is toegevoegd aan je winkelwagen!`);
            }
        });
    });
}

/**
 * Toont een notificatie aan de gebruiker
 */
function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Toon de notificatie (met CSS transitie)
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Verwijder de notificatie na een paar seconden
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
} 