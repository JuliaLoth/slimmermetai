<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Learning Platform | Slimmer met AI</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/style-fix.css?v=2.0">
    <link rel="stylesheet" href="/e-learning/css/e-learning-styles.css?v=1.0">
    
    <!-- Favicon -->
    <link rel="icon" href="/images/favicon.ico" type="image/x-icon">
</head>
<body class="secure-page">
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <a href="/" class="logo">
                <img src="/images/Logo.svg" alt="Slimmer met AI">
            </a>
            <nav class="main-nav">
                <ul>
                    <li><a href="/index.php">Home</a></li>
                    <li><a href="/e-learnings.php" class="active">E-Learnings</a></li>
                    <li><a href="/tools.php">Tools</a></li>
                    <li><a href="/over-mij.php">Over mij</a></li>
                    <li><a href="/nieuws.php">Nieuws</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <a href="/winkelwagen.php" class="cart-btn">
                    <span class="cart-icon">🛒</span>
                    <span class="cart-count">0</span>
                </a>
                <a href="/login.php" class="login-btn">Login</a>
                <div class="account-btn">
                    <span class="user-avatar">J</span>
                    <div class="account-dropdown">
                        <a href="/mijn-account.php">Mijn Account</a>
                        <a href="/mijn-cursussen.php">Mijn Cursussen</a>
                        <a href="#" class="logout-link">Uitloggen</a>
                    </div>
                </div>
            </div>
            <button class="mobile-menu-toggle" title="Menu openen">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <!-- Hoofd Content -->
    <main>
        <div class="hero-banner e-learning-banner">
            <div class="container">
                <h1>Slimmer met AI E-Learning Platform</h1>
                <p>Ontwikkel je AI-vaardigheden met onze interactieve cursussen</p>
            </div>
        </div>

        <div class="container">
            <div class="breadcrumbs">
                <a href="/index.php">Home</a>
                <span>E-learning Platform</span>
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
                        <a href="#available-courses" class="card-link">Ontdekken</a>
                    </div>
                </div>
            </div>
            
            <div id="my-courses" class="my-courses-section">
                <h2>Mijn Cursussen</h2>
                <div class="my-courses-grid">
                    <!-- Wordt dynamisch ingevuld met JavaScript -->
                    <div class="loading-courses">Cursussen laden...</div>
                </div>
            </div>
            
            <div id="certificates" class="certificates-section">
                <h2>Mijn Certificaten</h2>
                <div class="certificates-grid">
                    <!-- Wordt dynamisch ingevuld met JavaScript -->
                </div>
            </div>
            
            <div id="available-courses" class="available-courses-section">
                <h2>Beschikbare Cursussen</h2>
                <div class="available-courses-grid">
                    <!-- Wordt dynamisch ingevuld met JavaScript -->
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-columns">
                <div class="footer-column">
                    <h3>Slimmer met AI</h3>
                    <p>Leer hoe je AI effectief kunt inzetten voor je dagelijkse werkzaamheden.</p>
                </div>
                <div class="footer-column">
                    <h4>Cursussen</h4>
                    <ul class="footer-links">
                        <li><a href="courses/ai-basics/index.php">AI Basics</a></li>
                        <li><a href="/e-learnings.php">Alle Cursussen</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact</h3>
                    <p>Email: info@slimmermetai.com</p>
                    <div class="social-icons">
                        <a href="#" class="social-icon">
                            <img src="/images/linkedin-icon.svg" alt="LinkedIn">
                        </a>
                        <a href="#" class="social-icon">
                            <img src="/images/twitter-icon.svg" alt="Twitter">
                        </a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Slimmer met AI. Alle rechten voorbehouden.</p>
                <div class="footer-links">
                    <a href="/privacy-policy.php">Privacybeleid</a>
                    <a href="/terms-of-service.php">Gebruiksvoorwaarden</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="/js/main.js"></script>
    <script src="/js/cart.js"></script>
    <script src="/e-learning/js/course-data-manager.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            // Initialiseer de course data manager
            const initialized = await CourseDataManager.init();
            if (!initialized) {
                console.error('Kon de cursusgegevens niet initialiseren');
                return;
            }
            
            // Laad gebruikersgegevens en cursussen
            loadUserCourses();
            loadUserCertificates();
            loadAvailableCourses();
        });
        
        // Functie om de cursussen van de gebruiker te laden
        function loadUserCourses() {
            const coursesGrid = document.querySelector('.my-courses-grid');
            if (!coursesGrid) return;
            
            // Haal gekochte cursussen op
            const userJson = localStorage.getItem('currentUser');
            if (!userJson) {
                coursesGrid.innerHTML = `
                    <div class="no-courses-message">
                        <h3>Log in om je cursussen te zien</h3>
                        <p>Je moet ingelogd zijn om je gekochte cursussen te bekijken.</p>
                        <a href="/login.php" class="btn btn-primary">Inloggen</a>
                    </div>
                `;
                return;
            }
            
            const user = JSON.parse(userJson);
            const purchasedCourseIds = user.purchasedCourses || [];
            
            if (purchasedCourseIds.length === 0) {
                coursesGrid.innerHTML = `
                    <div class="no-courses-message">
                        <h3>Je hebt nog geen cursussen</h3>
                        <p>Bekijk onze beschikbare cursussen en begin met leren!</p>
                        <a href="#available-courses" class="btn btn-primary">Bekijk cursussen</a>
                    </div>
                `;
                return;
            }
            
            // Leeg de container
            coursesGrid.innerHTML = '';
            
            // Haal alle cursussen op
            const allCourses = CourseDataManager.getAllCourses();
            
            // Filter op gekochte cursussen
            const purchasedCourses = allCourses.filter(course => 
                purchasedCourseIds.includes(course.id)
            );
            
            // Voeg elke cursus toe aan de grid
            purchasedCourses.forEach(course => {
                // Haal voortgang op
                const progress = CourseDataManager.getUserProgressForCourse(course.id);
                const progressPercentage = progress ? progress.overallProgress : 0;
                
                const courseCard = document.createElement('div');
                courseCard.className = 'course-card';
                courseCard.innerHTML = `
                    <div class="course-image">
                        <img src="${course.thumbnail}" alt="${course.title}">
                        <div class="course-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${progressPercentage}%"></div>
                            </div>
                            <span class="progress-text">${progressPercentage}% voltooid</span>
                        </div>
                    </div>
                    <div class="course-content">
                        <h3>${course.title}</h3>
                        <p>${course.description}</p>
                        <div class="course-meta">
                            <span class="course-level">${course.level}</span>
                            <span class="course-duration">${course.duration}</span>
                        </div>
                        <a href="/e-learning/course-${course.id}.php" class="btn btn-primary">Doorgaan met leren</a>
                    </div>
                `;
                coursesGrid.appendChild(courseCard);
            });
        }
        
        // Functie om certificaten van de gebruiker te laden
        function loadUserCertificates() {
            const certificatesGrid = document.querySelector('.certificates-grid');
            if (!certificatesGrid) return;
            
            // Haal gebruikersgegevens op
            const userJson = localStorage.getItem('currentUser');
            if (!userJson) {
                certificatesGrid.innerHTML = `
                    <div class="no-certificates-message">
                        <p>Log in om je certificaten te bekijken.</p>
                    </div>
                `;
                return;
            }
            
            const user = JSON.parse(userJson);
            const userId = user.id;
            
            // Zoek de gebruiker in de data
            const userData = CourseDataManager.getUserData(userId);
            
            if (!userData || !userData.certificates || userData.certificates.length === 0) {
                certificatesGrid.innerHTML = `
                    <div class="no-certificates-message">
                        <p>Je hebt nog geen certificaten behaald. Rond een cursus volledig af om een certificaat te ontvangen.</p>
                    </div>
                `;
                return;
            }
            
            // Leeg de container
            certificatesGrid.innerHTML = '';
            
            // Haal alle cursussen op voor informatie
            const allCourses = CourseDataManager.getAllCourses();
            
            // Voeg elk certificaat toe aan de grid
            userData.certificates.forEach(certificate => {
                // Zoek cursusgegevens
                const course = allCourses.find(c => c.id === certificate.courseId);
                
                if (!course) return;
                
                const certificateCard = document.createElement('div');
                certificateCard.className = 'certificate-card';
                certificateCard.innerHTML = `
                    <div class="certificate-icon">🏆</div>
                    <div class="certificate-content">
                        <h3>${course.title}</h3>
                        <p>Uitgereikt op: ${formatDate(certificate.issueDate)}</p>
                        <a href="${certificate.certificateUrl}" class="btn btn-secondary" target="_blank">Bekijk certificaat</a>
                    </div>
                `;
                certificatesGrid.appendChild(certificateCard);
            });
        }
        
        // Functie om beschikbare cursussen te laden
        function loadAvailableCourses() {
            const coursesGrid = document.querySelector('.available-courses-grid');
            if (!coursesGrid) return;
            
            // Haal alle cursussen op
            const allCourses = CourseDataManager.getAllCourses();
            
            // Leeg de container
            coursesGrid.innerHTML = '';
            
            // Voeg elke cursus toe aan de grid
            allCourses.forEach(course => {
                const hasAccess = CourseDataManager.hasAccessToCourse(course.id);
                
                const courseCard = document.createElement('div');
                courseCard.className = 'course-card';
                courseCard.innerHTML = `
                    <div class="course-image">
                        <img src="${course.thumbnail}" alt="${course.title}">
                    </div>
                    <div class="course-content">
                        <h3>${course.title}</h3>
                        <p>${course.description}</p>
                        <div class="course-meta">
                            <span class="course-level">${course.level}</span>
                            <span class="course-duration">${course.duration}</span>
                            <span class="course-price">€${course.price.toFixed(2)}</span>
                        </div>
                        ${hasAccess ? 
                            `<a href="/e-learning/course-${course.id}.php" class="btn btn-primary">Ga naar cursus</a>` :
                            `<button class="btn btn-primary add-to-cart" 
                                data-product-id="${course.id}" 
                                data-product-name="${course.title}" 
                                data-product-price="${course.price.toFixed(2)}">
                                In winkelwagen
                            </button>`
                        }
                    </div>
                `;
                coursesGrid.appendChild(courseCard);
            });
            
            // Initialiseer winkelwagenknoppen
            initializeCartButtons();
        }
        
        // Functie om datums te formatteren
        function formatDate(dateString) {
            const date = new Date(dateString);
            return `${date.getDate()}-${date.getMonth() + 1}-${date.getFullYear()}`;
        }
        
        // Functie om winkelwagenknoppen te initialiseren
        function initializeCartButtons() {
            document.querySelectorAll('.add-to-cart').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    const productName = this.dataset.productName;
                    const productPrice = this.dataset.productPrice;
                    
                    // Voeg toe aan winkelwagen
                    if (typeof addToCart === 'function') {
                        addToCart({
                            id: productId,
                            name: productName,
                            price: productPrice,
                            quantity: 1,
                            type: 'course'
                        });
                        
                        // Toon melding
                        alert(`${productName} is toegevoegd aan je winkelwagen!`);
                    }
                });
            });
        }
    </script>
</body>
</html> 

